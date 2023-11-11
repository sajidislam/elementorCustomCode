<?php
// Fetch data from Calendly's API here...
// For demonstration purposes, I'm just sending a test message:
if (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email'])) {
    $emailToSearch = $_COOKIE['user_email']; // Retrieve the value of 'user_email' cookie
} else {
    $yourMessage = "User email cookie not found!";
    exit; // Exit the script if the cookie is not found
}

// Access Token from your Calendly account
$accessToken = '<ACCESS_TOKEN>'; //replace ACCESS_TOKEN with your access token

// User UUID (replace with the UUID you provided earlier)
$userUUID = 'https://api.calendly.com/users/XXX_SECRET'; // replace 'XXX_SECRET' with the appropriate UUID
$eventsUrl = "https://api.calendly.com/scheduled_events?user=" . urlencode($userUUID) . "&invitee_email=" . urlencode($emailToSearch) . "&status=active";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $eventsUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $accessToken,
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    $yourMessage = array('message' =>  "cURL Error #:  $err");
} else {
    $data = json_decode($response, true);
    if (isset($data['collection']) && !empty($data['collection'])) {

        $currentDate = new DateTime(); // Current date and time
        $currentDate->setTimezone(new DateTimeZone('America/New_York')); // Set current date timezone to Eastern Time

        foreach ($data['collection'] as $event) {
            $eventStart = new DateTime($event['start_time'], new DateTimeZone('UTC'));
            $eventStart->setTimezone(new DateTimeZone('America/New_York')); // Convert the event start time to US Eastern Time

            if ($eventStart > $currentDate) { // Check if the event is in the future
                $eventName = $event['name'];
                $eventStatus = $event['status'];
                $eventStartDate = $eventStart->format('Y-m-d');
                $eventStartTime = $eventStart->format('g:i A');
                $yourMessage = array('message' => "You are scheduled for a call on: ".$eventStartDate." at ".$eventStartTime." Eastern");
            }
        }

    } else {
        $yourMessage =  $yourMessage = array('message' => "No active scheduled events found for the given email.");
    }
}
header('Content-Type: application/json');
echo json_encode($yourMessage);
?>
