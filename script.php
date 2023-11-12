<?php

header('Content-Type: application/json');
// Fetch data from Calendly's API here...
// Access Token from your Calendly account
$accessToken = 'INSERT_API_KEY';

// User UUID (replace with the UUID you provided earlier)
$userUUID = 'https://api.calendly.com/users/XXX_SECRET'; // replace 'XXX_SECRET' with the appropriate UUID

if (isset($_COOKIE['scLeadEmail']) && !empty($_COOKIE['scLeadEmail'])) {
    $emailToSearch = $_COOKIE['scLeadEmail']; // Retrieve the value of 'scLeadEmail' cookie
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
            //hmm.. we are here because the 1FPC exists but the curl command did not retrieve any data. hmm. either the cookie modified by hand or email changed inflight
            $yourMessage =  $yourMessage = array('message' => "Check your email for the calendar invite.");
        }
    }
} else {
        //something happened here and 1FPC cookies are missing or someone become null. 
        //in the future do some logging here
        #$yourMessage = "Hello.. User email cookie not found!";
        $yourMessage = array('message' => "An email has been sent with the calendar invite!");
}
echo json_encode($yourMessage);
?>
