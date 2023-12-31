<?php
function validateForm($record,$ajax_handler){
	
	// Ensure our custom functions are loaded.
   // require_once get_stylesheet_directory() . '/elementor-form-custom-functions.php';

   //error_log('Entered validateForm');

    // Extract submitted form fields.
    $raw_fields = $record->get('fields');
    $fields = [];
    foreach ($raw_fields as $id => $field) {
        $fields[$id] = $field['value'];
    }

   // Name is a required field so it will be entered. Still, sanitize and validate name.
   // The regex preg_match('/^[a-zA-Z\s\'-]+$/', $name) is checking for a very conservative 
   // set of characters: 
   // letters, spaces, apostrophes, and hyphens, which are common in names.
    
	if (isset($fields['name'])) {
        // Strip tags to prevent XSS, trim to remove whitespace, and sanitize text field.
        $name = sanitize_text_field(trim(strip_tags($fields['name'])));

        // Check if name is empty after sanitization or contains only valid characters.
        if (empty($name)) {
            $ajax_handler->add_error('name', 'Name cannot be blank.');
        } elseif (!preg_match('/^[a-zA-Z\s\'\-.]+$/', $name)) { // Allow letters, apostrophes, hyphens, and spaces.
            $ajax_handler->add_error('name', 'Name contains invalid characters.');
        }
    }

	
    // Validate email.
    if (!isset($fields['email']) || empty(trim($fields['email']))) {
        $ajax_handler->add_error('email', 'Email cannot be blank.');
        return;
    }
	
	//Even though we perform a deeper validation in another function,
	//nevertheless, i'm using the PHP built-in filter_var function along with 
	//FILTER_VALIDATE_EMAIL constant to filter the 
	//email address to determine if it's a valid email address format.
	//P.s: filter_var can also be used URLs, IP addresses, and to sanitize strings, among other things.

	if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $ajax_handler->add_error('email', 'Invalid email address.');
		return;
    }
	
    if (!validateEmail($fields['email'])) {
        $table_name = 'failedEmails';
	  }
	else {
		$table_name = 'successEmails';
	}
    $additional_data = [
       'IP_Address' => captureIPAddress(),
       'Date' => current_time('mysql'),
       'Referrer' => captureReferrer(),
       'User_Agent' => captureBrowserDetails(),
     ];
    $utm_data = getAllUTMParameters();

    $data_to_insert = array_merge($fields, $additional_data, $utm_data);
	//error_log("Table Name - $table_name : " . print_r($data_to_insert, true));
	
    // Insert the data into the database.
  	insertEventToDB($table_name, $data_to_insert);

	if ($table_name == 'failedEmails') {
        $ajax_handler->add_error('email', 'Invalid email address.');		
	}
	else {
	    setcookie('scLeadName', $fields['name'], time() + (86400 * 90), "/");
		setcookie('scLeadEmail', $fields['email'], time() + (86400 * 90), "/");
	}
}
function getAllUTMParameters() {
    $utm_keys = ['wbraid','gbraid','gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content'];
    $values = [];

	//$param_lower = strtolower($param);
    foreach ($utm_keys as $param) {
        $values[$param] = readUTMParameters($param);
		//error_log("$param: $values[$param]");
    }

    return $values;
}

function readUTMParameters($param) {
	//error_log("Entered readUTMParam with: $param");
	//PHP's $_GET array is case-sensitive. 
	//This means that $_GET['gclid'] and $_GET['GCLID'] are considered different variables
	//
    // Check if the UTM parameter is present in the URL
    // 
	if (isset($_GET[$param])) {
        $value = sanitize_text_field($_GET[$param]);
         //error_log("Value readUTMParameters - $value");
        // Set the cookie if the value is found in the URL
        setUTMParameters($param, $value, 90);
    } else {
	    // If not present in URL, check for the cookie
	    // This will mean that it is a returning user
        $value = isset($_COOKIE[$param]) ? sanitize_text_field($_COOKIE[$param]) : '';
    }
    return $value;
}

function setUTMParameters($param, $value, $duration) {
    // Setting the cookie for 90 days (duration is in seconds)
    setcookie($param, $value, time() + (86400 * $duration), "/"); // 86400 seconds in a day
}

function validateEmail($email) {
    $parts = explode('@', $email);
    $domain = array_pop($parts);
    return filter_var($email, FILTER_VALIDATE_EMAIL) && checkdnsrr($domain, 'MX');
}


function captureReferrer() {
    return isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
}

function captureBrowserDetails() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
}

function captureIPAddress() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER)) {
			//error_log("$key : " . $_SERVER[$key]);
            return sanitize_text_field($_SERVER[$key]);
        }
	//error_log("$key :" );
    }
    return '';
}

/**
 * Send a notification email to the WordPress admin.
 *
 * @param string $message The message content for the email.
 * @return void
 */

function notifyAdmin($message) {
    $admin_email = get_option('admin_email');
    $subject = 'Database Insertion Error Alert!';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    if (!$admin_email) {
        // Handle cases where the admin email is not set or accessible.
        error_log("Failed to fetch WordPress admin email for sending notifications.");
        return;
    }

    wp_mail($admin_email, $subject, $message, $headers);
}

/**
 * Insert an event record to the specified database table.
 *
 * Before attempting the insert operation, check if the table exists.
 * If the table doesn't exist, create it using the WordPress dbDelta function, which is a WordPress function that can examine the current table structure, compare it to the desired table structure, and either create the table or alter the table to match, as necessary.
 * Proceed with the insert operation.
 *
 * @param string $table_name The name of the table.
 * @param array $fields Associative array of field names and their respective values.
 * @return bool True if insert is successful, false otherwise.
 */
function insertEventToDB($table_name, $fields) {
    global $wpdb;

    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

    // If the table doesn't exist, create it
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            Name text NOT NULL,
            Email text NOT NULL,
            IP_Address text,
            Date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            Referrer text,
            User_Agent text,
	    wbraid text,
	    gbraid text,
            gclid text,
            utm_source text,
            utm_medium text,
            utm_campaign text,
            utm_content text,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Insert or update data in the table
    $success = $wpdb->replace($table_name, $fields);

    // Check and handle database errors
    // email wp admin and inform of the error
    if (!$success) {
        $error = $wpdb->last_error;
	notifyAdmin("Failed to insert event into $table_name. Error: $error \n Data: " . json_encode($data_to_insert) );
	error_log('notify Admin: ' ."Failed to insert data into " . $table_name . ". Data: " . json_encode($data_to_insert));
	return false;
    }
	return true;
}

?>
