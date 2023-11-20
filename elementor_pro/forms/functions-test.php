<?php
/*This file is part of ChildHelloElementor, hello-elementor child theme.

All functions of this file will be loaded before of parent theme functions.
Learn more at https://codex.wordpress.org/Child_Themes.

Note: this function loads the parent stylesheet before, then child theme stylesheet
(leave it in place unless you know what you are doing.)
*/

if ( ! function_exists( 'suffice_child_enqueue_child_styles' ) ) {
	function ChildHelloElementor_enqueue_child_styles() {
	    // loading parent style
	    wp_register_style(
	      'parente2-style',
	      get_template_directory_uri() . '/style.css'
	    );

	    wp_enqueue_style( 'parente2-style' );
	    // loading child style
	    wp_register_style(
	      'childe2-style',
	      get_stylesheet_directory_uri() . '/style.css'
	    );
	    wp_enqueue_style( 'childe2-style');
	 }
}
add_action( 'wp_enqueue_scripts', 'ChildHelloElementor_enqueue_child_styles' );

/*Write here your own functions */





// Cleaner code. Moved a lot of the common functions to elementor-form-custom-functions.php 
// make sure that the elementor-form-custom-functions.php exists in the same directory 
// as the functions.php of the (child)theme.

/*
function my_custom_scripts() {
//    wp_enqueue_script('my-custom-script', get_template_directory_uri() . '/custom-script.js', array('jquery'), '1.0.0', true);
    //template_directory_url will get the parent theme. get_stylesheet_directory retrieves the child theme path 
    //$script_path = get_template_directory_uri() . '/custom-script.js';

    $script_path = get_stylesheet_directory_uri() . '/custom-script.js';

        // Log the script path
        error_log('Script path: ' . $script_path);

        wp_enqueue_script('my-custom-script', $script_path, array(), '1.0.0', true);
}

add_action('wp_enqueue_scripts', 'my_custom_scripts');

*/

add_action('elementor_pro/forms/validation', function($record, $ajax_handler) {

	//before uncommenting the debug code below, make sure you turn on WP_DEBUG in wp-config.php
	//define('WP_DEBUG', true);
	//define('WP_DEBUG_LOG', true);
	//define('WP_DEBUG_DISPLAY', false);
	//@ini_set('display_errors', 0);

  
   error_log('This is a debug message from forms validation');
   $form_name = $record->get_form_settings('form_name');
   error_log('Form Name: ' . $form_name);	
/*
   $setting_keys = ['form_name', 'form_id'];
   foreach ($setting_keys as $key) {
        // Retrieve each setting
        $setting_value = $record->get_form_settings($key);
        // Log the setting
        error_log($key . ': ' . print_r($setting_value, true));
    }
  */	
	
    // Ensure our custom functions are loaded.
    require_once get_stylesheet_directory() . '/elementor-form-custom-functions.php';

    // Get form settings
    $form_settings = $record->get_form_settings('form_name'); // or use another relevant key

/*
    // Get form fields
    $fields = $record->get_field( 'field_id' ); // Replace 'field_id' with the actual ID, or another method to retrieve fields
	if (!empty($fields)) {
		error_log('Field type: ' . gettype($fields) . '; value: ' . print_r($fields, true));
	}
	else {
    error_log('No data in fields for field_id');
    // Handle the case when there are no fields or data is empty
     }
	
*/
	 $form_name = $record->get_form_settings('form_name');
	 if ($form_name == "Book A Call") {
        validateForm($record,$ajax_handler);
    }
	else {
  		// Add an error message
    	$ajax_handler->add_error_message('Error: Form name is not valid or a match was not found');
       }	
	 return;


    // Extract submitted form fields.
    $raw_fields = $record->get('fields');
    $fields = [];
    foreach ($raw_fields as $id => $field) {
        $fields[$id] = $field['value'];
    // 	error_log('Extracting raw field: ' . $fields[$id] . ' - ' . $field['value']);
    }

	
   // Name is a required field so it will be entered. Still, sanitize and validate name.
   // The regex preg_match('/^[a-zA-Z\s\'-]+$/', $name) is checking for a very conservative set of characters: letters, spaces,
   // apostrophes, and hyphens, which are common in names.
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
	//Even though we perform a deeper validation in elementor-form-custom-functions.php
	//nevertheless, i'm using the PHP built-in filter_var function along with FILTER_VALIDATE_EMAIL constant to filter the 
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

        // Insert the data into the database.
   insertEventToDB($table_name, $data_to_insert);

	/*
	 // 31Oct23 - Line 76 - 84 is obsolete since we handle all the errors in the insertEventToDB function.
    $insert_result = insertEventToDB($table_name, $data_to_insert);
 
	
    if (!$insert_result) {
//           notifyAdmin("Failed to insert failed email data into database.");
//          notifyAdmin("Failed to insert data into " . $table_name . ". Data: " . json_encode($data_to_insert));
	  error_log('notify Admin: ' ."Failed to insert data into " . $table_name . ". Data: " . json_encode($data_to_insert) );

       }
*/	
	if ($table_name == 'failedEmails') {
        $ajax_handler->add_error('email', 'Invalid email address.');		
	}
	else {
	    setcookie('scLeadName', $fields['name'], time() + (86400 * 90), "/");
		setcookie('scLeadEmail', $fields['email'], time() + (86400 * 90), "/");
	}
}, 10, 2);



