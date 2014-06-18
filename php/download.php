<?php
session_start();

//echo 'Welcome to page #2<br />';
//
//echo $_SESSION['results'];
//
//echo date('Y m d H:i:s', $_SESSION['time']);
//
//// You may want to use SID here, like we did in page1.php
//echo '<br /><a href="page1.php">page 1</a>';

$filename = 'Prioritized_results.txt';
header("Content-Type: text/plain");
header('Content-Disposition: attachment; filename="'.$filename.'"');
header("Content-Length: " . strlen($_SESSION['results']));
echo $_SESSION['results'];
exit;
?>