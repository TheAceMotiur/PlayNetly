<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../../config.php';

$id = $_GET['id'];
$conn->query("DELETE FROM dropbox_accounts WHERE id = $id");
header("Location: index.php");
exit();
?>