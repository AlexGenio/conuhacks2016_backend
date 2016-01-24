<?php

	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);
	$UID = getUserByToken($conn, $token);

	$SID = getSchoolID($conn, $UID);

	// retrieve the list of all classes that the user is taking
	$classes = getUserClasses($conn, $SID);
	
	$common = getCommonClasses($conn, $classes, $UID);

	$conn->close();
	die();
?>