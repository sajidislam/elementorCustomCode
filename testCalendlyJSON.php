<?php

// API Token
$apiToken = 'YOUR_API_TOKEN';

// First cURL request to fetch user data
$ch1 = curl_init();

curl_setopt($ch1, CURLOPT_URL, 'https://api.calendly.com/users/me');
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch1, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
));

$response1 = curl_exec($ch1);

if(curl_errno($ch1)) {
    echo 'Error fetching user data:' . curl_error($ch1);
    exit;
}

curl_close($ch1);

$userData = json_decode($response1, true);
$userURI = $userData['resource']['uri'];

if (!$userURI) {
    echo "Error: User URI not found in the response.";
    exit;
}

// Second cURL request to fetch scheduled events using user URI
$ch2 = curl_init();

curl_setopt($ch2, CURLOPT_URL, 'https://api.calendly.com/scheduled_events?user=' . $userURI);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
));

$response2 = curl_exec($ch2);

if(curl_errno($ch2)) {
    echo 'Error fetching scheduled events:' . curl_error($ch2);
    exit;
}

curl_close($ch2);

// Print the raw response for debugging purposes
echo "Scheduled Events: <pre>";
print_r($response2);
echo "</pre>";

?>
