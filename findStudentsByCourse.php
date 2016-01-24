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
	$classes = getUserClassesID($conn, $SID, $UID);
	
	// Get users that have classes in common with the user
	$common = getCommonClasses($conn, $classes, $UID);

	// Get user info
	$arr = getUserDetails($conn, $common, $UID);

	$conn->close();
	$response = ["students" => $arr];
	echo json_encode($response);
	die();
?>