<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../../config.php';

// Fetch the account details from the database
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM dropbox_accounts WHERE id = $id");
if (!$result) {
    die("Database query failed: " . $conn->error);
}
$account = $result->fetch_assoc();
$clientId = $account['app_key'];
$redirectUri = 'https://fileswith.com/brotherhood/accounts/callback.php'; // Ensure this matches the URI in Dropbox App Console

// Dropbox OAuth 2.0 authorization endpoint
$authEndpoint = 'https://www.dropbox.com/oauth2/authorize';
$params = [
    'response_type' => 'code',            // Set response_type to 'code' to get the authorization code
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'state' => $id,                       // Pass the account ID as state for security
    'token_access_type' => 'offline'      // Request for a refresh token
];
$authUrl = $authEndpoint . '?' . http_build_query($params);

// Redirect to Dropbox authorization URL
header("Location: $authUrl");
exit();
?>
