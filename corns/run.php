<?php
// Include each cron job script
require_once 'refresh.php';
require_once 'last.php';
require_once 'cleanup.php';
require_once 'temp.php';

echo "All cron jobs executed successfully.";
?>