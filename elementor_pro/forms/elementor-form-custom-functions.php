<?php

function readUTMParameters() {
    $utm_parameters = ['gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content'];
    $values = [];
    foreach ($utm_parameters as $param) {
        $values[$param] = isset($_GET[$param]) ? sanitize_text_field($_GET[$param]) : '';
    }
    return $values;
}

function setUTMParameters($duration) {
    $utm_values = readUTMParameters();
    foreach ($utm_values as $key => $value) {
        setcookie($key, $value, time() + (86400 * $duration), "/");  // 86400 = 1 day
    }
}

function validateEmail($email) {
    $domain = array_pop(explode('@', $email));
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
            return sanitize_text_field($_SERVER[$key]);
        }
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
 * @param string $table_name The name of the table.
 * @param array $fields Associative array of field names and their respective values.
 * @return bool True if insert is successful, false otherwise.
 */
function insertEventToDB($table_name, $fields) {
    global $wpdb;

    $result = $wpdb->insert($table_name, $fields);

    if (!$result) {
        // If the insertion failed, log the error and notify the admin.
        $error_message = $wpdb->last_error;
        error_log("Database insertion error in table {$table_name}: " . $error_message);

        // Notify the admin about the insertion error.
        $notification_message = "A database insertion error occurred on your WordPress site:<br><br>"
            . "Table: {$table_name}<br>"
            . "Error: {$error_message}<br>"
            . "Data: " . json_encode($fields);
        notifyAdmin($notification_message);

        return false;
    }

    return true;
}


?>
