<?php
	$token = $_POST['token'];
	$cid = $_POST['CID'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);

	$uid = getUserByToken($conn, $token);

	// delete user's course
	$sql = "DELETE FROM user_classes WHERE UID=? AND CID=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ii", $uid, $cid);
	$stmt->execute();
	$stmt->close();
	$conn->close();

	$response = ["success" => "Class removed"];
	echo json_encode($response);
?>