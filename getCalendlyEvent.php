<?php
$emailToSearch = "target_email@example.com"; // Replace this with the email you're searching for

// Access Token from your Calendly account
$accessToken = 'YOUR_PERSONAL_ACCESS_TOKEN';

// User UUID endpoint
$userUrl = 'https://api.calendly.com/users/me';

// Initialize a new cURL session and set the options
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
));

$response = curl_exec($ch);
curl_close($ch);

$userData = json_decode($response, true);
$userUUID = isset($userData['resource']['uri']) ? $userData['resource']['uri'] : null;

if (!$userUUID) {
    echo "Error: UUID not found in the response";
    exit;
}

// Scheduled Events endpoint with user UUID
$eventsUrl = "https://api.calendly.com/scheduled_events?user=$userUUID";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $eventsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
));

$response = curl_exec($ch);
$scheduledEvents = json_decode($response, true);
curl_close($ch);

// Search for the email in scheduled events
$eventFound = false;
if (isset($scheduledEvents['collection']) && is_array($scheduledEvents['collection'])) {
    foreach ($scheduledEvents['collection'] as $event) {
        if (isset($event['invitees']) && is_array($event['invitees'])) {
            foreach ($event['invitees'] as $invitee) {
                if (isset($invitee['email']) && $invitee['email'] === $emailToSearch) {
                    $eventFound = true;
                    echo "Found event for email: " . $emailToSearch . "<br>";
                    echo "Event start time: " . $event['start_time'] . "<br>";
                    echo "Event end time: " . $event['end_time'] . "<br>";
                    break 2; // Break out of both loops once a match is found
                }
            }
        }
    }
}

if (!$eventFound) {
    echo "No scheduled events found for email: " . $emailToSearch;
}

?>
