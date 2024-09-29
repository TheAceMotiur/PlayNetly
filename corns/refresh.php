<?php
require_once '../config.php';

function refreshDropboxAccessToken($accountId, $refreshToken, $clientId, $clientSecret) {
    $url = 'https://api.dropboxapi.com/oauth2/token';
    $data = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        return $responseData;
    } else {
        throw new Exception("Failed to refresh access token for account ID {$accountId}. HTTP Code: {$httpCode}");
    }
}

// Fetch all accounts with refresh tokens from the database
$result = $conn->query("SELECT id, app_key, app_secret, refresh_token FROM dropbox_accounts WHERE refresh_token IS NOT NULL");
if (!$result) {
    die("Database query failed: " . $conn->error);
}

while ($account = $result->fetch_assoc()) {
    $accountId = $account['id'];
    $refreshToken = $account['refresh_token'];
    $clientId = $account['app_key'];
    $clientSecret = $account['app_secret'];

    try {
        $tokens = refreshDropboxAccessToken($accountId, $refreshToken, $clientId, $clientSecret);
        $newAccessToken = $tokens['access_token'];
        $newRefreshToken = isset($tokens['refresh_token']) ? $tokens['refresh_token'] : $refreshToken;

        // Update the access token and refresh token in the database
        $stmt = $conn->prepare("UPDATE dropbox_accounts SET access_token = ?, refresh_token = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newAccessToken, $newRefreshToken, $accountId);
        $stmt->execute();

        echo "Access token refreshed successfully for account ID {$accountId}.\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>