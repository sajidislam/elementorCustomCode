<?php

// Check the URL for UTM Parameters
function readUTMParameters() {
    $utm_parameters = array('gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content');
    $found_parameters = array();

    foreach ($utm_parameters as $param) {
        if (isset($_GET[$param])) {
            $found_parameters[$param] = sanitize_text_field($_GET[$param]);
        }
    }

    if (!empty($found_parameters)) {
        setUTMParameters($found_parameters, 30); // Assuming 30 days as default duration
    }

    return $found_parameters;
}

// Set UTM parameters as first-party cookies
function setUTMParameters($parameters, $duration) {
    foreach ($parameters as $key => $value) {
        setcookie($key, $value, time() + (86400 * $duration), "/"); // 86400 = 1 day in seconds
    }
}

// Validate email format and domain
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = explode('@', $email)[1];
    if (!checkdnsrr($domain, 'MX')) {
        return false;
    }

    return true;
}

// Capture the referrer
function captureReferrer() {
    return isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
}

// Capture the browser details
function captureBrowserDetails() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
}

// Capture the IP Address
function captureIPAddress() {
    // This method of capturing IP address considers proxies and other factors. It's not foolproof but covers most common cases.
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return sanitize_text_field($ip);
                }
            }
        }
    }
}

?>
