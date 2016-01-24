<?php

	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);
	$UID = getUserByToken($conn, $token);

	// Get swipee info entered
	$swipeeID = stripslashes(trim($_POST['swipee']));
	$value = stripslashes(trim($_POST['value']));
	$classID = stripslashes(trim($_POST['cid']));

	insertSwipeResult($conn, $UID, $swipeeID, $value, $classID);
	$swipeeVal = getSwipeeValue($conn, $swipeeID, $UID, $value);
	$cidSwipee = getClassValue($conn, $swipeeID, $UID, $classID);

	if(($swipeeVal == 1) && ($value == 1) && ($cidSwipee == $classID)){

		checkIfGroupExists($conn, $UID, $swipeeID, $classID);

		// Get user info
		$arr = getUserSwipeDetails($conn, $UID);
		$arr['status'] = "matched";
		$response = ["student" => $arr];
		echo json_encode($response);
	}elseif (($swipeeVal == 0 && $value == 1) || ($swipeeVal == 0 && $value == 1)) {
		$response = ["status" => "waiting"];
		echo json_encode($response);
	}else{
		$response = ["status" => "rejected"];
		echo json_encode($response);
	}

	$conn->close();
	die();
?>