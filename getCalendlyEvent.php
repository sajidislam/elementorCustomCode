<?php

// API Token
$apiToken = 'YOUR_API_TOKEN';

// Invitee email you're searching for
$inviteeEmail = 'example@email.com';

// cURL request to fetch scheduled events from Calendly API
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.calendly.com/scheduled_events');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
));

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    exit;
}

curl_close($ch);

// Decode the response
$data = json_decode($response, true);

$found = false;

if (isset($data['data'])) {
    foreach ($data['data'] as $event) {
        if (isset($event['invitees']) && is_array($event['invitees'])) {
            foreach ($event['invitees'] as $invitee) {
                if (isset($invitee['email']) && $invitee['email'] === $inviteeEmail) {
                    echo "Event date & time for " . $inviteeEmail . ": " . $event['start_time'] . "<br>";
                    $found = true;
                    break;
                }
            }
        }
        if ($found) {
            break;
        }
    }
}

if (!$found) {
    echo "No matching events found for " . $inviteeEmail . ".";
}

?>
