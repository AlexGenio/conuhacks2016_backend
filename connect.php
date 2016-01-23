<?php
	$host = "localhost";
	$user = "root";
	$pass = "conuhacks2016";
	$db	  = "gpaplus";

	global $conn;
	$conn = new mysqli($host, $user, $pass, $db);

	// Check connection
	if ($conn->connect_error) {
    	die("Connection failed: ".$conn->connect_error);
	}
?>