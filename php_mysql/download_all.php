<?php
session_start();

$filename = 'All_results.txt';
header("Content-Type: text/plain");
header('Content-Disposition: attachment; filename="'.$filename.'"');
header("Content-Length: " . strlen($_SESSION['allresults']));
echo $_SESSION['allresults'];
exit;
?>