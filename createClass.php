<?php

	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);
	$UID = getUserByToken($conn, $token);

	// Get class name entered
	$class = $_POST['class'];

	$SID = getSchoolID($conn, $UID);
	addClass($conn, $SID, $UID, $class);

	$conn->close();

	$response = ["success" => "Class added"];
	echo json_encode($response);

	die();
?>