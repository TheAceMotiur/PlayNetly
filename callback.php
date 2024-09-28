<?php
require_once 'config.php';

if (isset($_GET['auth_key'])) {
    $auth_key = $_GET['auth_key'];
    $app_id = "8746867909080";
    $app_secret = "7eb99480472bf3bda9bf6d27254df514";
    $get = file_get_contents("https://onenetly.com/api/authorize?app_id=$app_id&app_secret=$app_secret&auth_key=$auth_key");
    $json = json_decode($get, true);

    if (!empty($json['access_token'])) {
        $access_token = $json['access_token'];
        $user_info = file_get_contents("https://onenetly.com/api/get_user_info?access_token=$access_token");
        $user_info = json_decode($user_info, true);

        session_start();
        $_SESSION['user_id'] = $user_info['user_info']['user_id'];
        $_SESSION['user_name'] = $user_info['user_info']['user_name'];
        $_SESSION['user_email'] = $user_info['user_info']['user_email'];

        $user_id = $user_info['user_info']['user_id'];
        $user_name = $user_info['user_info']['user_name'];
        $user_email = $user_info['user_info']['user_email'];

        // Check if user already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? OR user_name = ?");
        $stmt->bind_param("ss", $user_email, $user_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User exists, update the existing record
            $stmt = $conn->prepare("UPDATE users SET user_name = ?, user_email = ? WHERE user_email = ? OR user_name = ?");
            $stmt->bind_param("ssss", $user_name, $user_email, $user_email, $user_name);
        } else {
            // User does not exist, insert a new record
            $stmt = $conn->prepare("INSERT INTO users (user_id, user_name, user_email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $user_id, $user_name, $user_email);
        }

        $stmt->execute();

        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: Unable to retrieve access token.";
    }
} else {
    echo "Error: No auth_key received.";
}
?>