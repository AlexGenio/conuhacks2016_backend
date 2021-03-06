<?php

	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);
	$UID = getUserByToken($conn, $token);

	// Get school name entered
	$school = stripslashes(trim($_POST['school']));

	// Filter schools starting with what was entered
	$schoolList = filterSchoolByName($conn, $school);

	echo json_encode($schoolList);

	$conn->close();
?>