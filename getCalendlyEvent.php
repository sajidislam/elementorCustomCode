<?php
// Check if the cookie 'user_email' is set and not empty
if (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email'])) {
    $emailToSearch = $_COOKIE['user_email']; // Retrieve the value of 'user_email' cookie
} else {
    echo "User email cookie not found!";
    exit; // Exit the script if the cookie is not found
}

//$emailToSearch = "target_email@example.com"; // Replace this with the email you're searching for

// Access Token from your Calendly account
$accessToken = 'YOUR_PERSONAL_ACCESS_TOKEN';

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
    echo "cURL Error #:" . $err;
} else {
    $data = json_decode($response, true);
    if (isset($data['collection']) && !empty($data['collection'])) {
        echo "<table border='1'>";
        echo "<tr><th>Event Name</th><th>Event Status</th><th>Event Start Date</th><th>Event Start Time</th></tr>";
        $currentDate = new DateTime(); // Current date and time
        foreach ($data['collection'] as $event) {
            $eventStart = new DateTime($event['start_time']);
            if ($eventStart > $currentDate) { // Check if the event is in the future
                $eventName = $event['name'];
                $eventStatus = $event['status'];
                $eventStartDate = $eventStart->format('Y-m-d');
                $eventStartTime = $eventStart->format('H:i');
                echo "<tr>";
                echo "<td>$eventName</td>";
                echo "<td>$eventStatus</td>";
                echo "<td>$eventStartDate</td>";
                echo "<td>$eventStartTime</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    } else {
        echo "No active scheduled events found for the given email.";
    }
}

?>
