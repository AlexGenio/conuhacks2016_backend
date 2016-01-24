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

	// retrieve the name of all classes that the user is taking
	$arr = getUserClasses($conn, $sid);

	$response = ["courses" => $arr];
	echo json_encode($response);
?>