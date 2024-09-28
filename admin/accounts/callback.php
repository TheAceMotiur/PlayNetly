<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../../config.php';

if (isset($_GET['code']) && isset($_GET['state'])) {
    $code = $_GET['code'];
    $id = $_GET['state'];

    // Fetch the account details from the database securely
    $stmt = $conn->prepare("SELECT * FROM dropbox_accounts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();

    if (!$account) {
        die("No account found with ID: " . $id);
    }

    $clientId = $account['app_key'];
    $clientSecret = $account['app_secret'];
    $redirectUri = 'http://localhost/admin/accounts/callback.php';

    // Exchange authorization code for access token and refresh token
    $tokenEndpoint = 'https://api.dropboxapi.com/oauth2/token';
    $params = [
        'code' => $code,
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri
    ];

    $ch = curl_init($tokenEndpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // Handle cURL errors
        die("cURL Error: " . curl_error($ch));
    }

    curl_close($ch);

    $json = json_decode($response, true);
    if (isset($json['refresh_token'])) {
        $refresh_token = $json['refresh_token'];

        // Store the refresh token in the database
        $stmt = $conn->prepare("UPDATE dropbox_accounts SET refresh_token = ? WHERE id = ?");
        $stmt->bind_param("si", $refresh_token, $id);
        $stmt->execute();

        header("Location: index.php");
        exit();
    } else {
        echo "Error: Unable to retrieve refresh token.<br>";
        echo "Response: <pre>" . print_r($json, true) . "</pre>";
    }
} else {
    echo "Error: No authorization code received.";
}
?>
