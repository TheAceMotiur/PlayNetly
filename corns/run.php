<?php
// Include each cron job script
require_once 'refresh.php';
require_once 'last.php';
require_once 'cleanup.php';
// Add more as needed

echo "All cron jobs executed successfully.";
?>