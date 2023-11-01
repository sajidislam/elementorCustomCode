<?php
// Your API token
$apiToken = 'YOUR_API_TOKEN';

// Email of the invitee you're looking for
$inviteeEmail = 'example@example.com';

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, 'https://api.calendly.com/scheduled_events');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$headers = array();
$headers[] = 'Authorization: Bearer ' . $apiToken;
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute cURL session and get the response
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

// Close the cURL session
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);

// Loop through the scheduled events to find the invitee's email
foreach ($data['data'] as $event) {
    foreach ($event['invitees'] as $invitee) {
        if ($invitee['email'] == $inviteeEmail) {
            // Here you can access the event details for the matched invitee
            echo "Event date & time for " . $inviteeEmail . ": " . $event['start_time'];
        }
    }
}
?>
