<?php

	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);

	// Token still exists
	// Delete the user's token
	deleteToken($token);

	$conn->close();
	die();
?>