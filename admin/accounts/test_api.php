<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config.php';

function testDropboxApi($accessToken) {
    $url = 'https://api.dropboxapi.com/2/users/get_space_usage';
    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);  // Use POST method
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([])); // Send an empty JSON object
    curl_setopt($ch, CURLOPT_FAILONERROR, false); // Disable auto error
    curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose mode for debugging
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);  // Get cURL error if any

    curl_close($ch);

    if ($httpCode != 200) {
        return [
            "error" => "HTTP error: $httpCode",
            "response" => $response,
            "curl_error" => $curlError  // Include cURL error in the response
        ];
    }

    return json_decode($response, true);
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM dropbox_accounts WHERE id = '$id'");

if ($result && $result->num_rows > 0) {
    $account = $result->fetch_assoc();
    $accessToken = $account['access_token'];

    $spaceInfo = testDropboxApi($accessToken);

    if (isset($spaceInfo['error'])) {
        echo "API is not working. Error: " . json_encode($spaceInfo);
    } else {
        echo "API is working. Space info: " . json_encode($spaceInfo);
    }
} else {
    echo "Account not found or database error.";
}
?>
