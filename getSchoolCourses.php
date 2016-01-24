<?php
	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);

	$uid = getUserByToken($conn, $token);

	$sid = getSchoolID($conn, $uid);

	// retrieve all available courses at user's school
	$arr = getSchoolClasses($conn, $sid);

	$conn->close();

	$response = ["courses" => $arr];
	echo json_encode($response);
?>