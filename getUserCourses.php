<?php
	$token = $_POST['token'];

	require_once 'functions.php';
	checkEmptyToken($token);

	// Instantiate a connection
	require_once 'connect.php';
	global $conn;

	checkExpiredToken($conn, $token);

	$uid = getUserByToken($conn, $token);

	$arr = array();	// stores class names

	// retrieve the name of all classes that the user is taking
	$sql = "SELECT classes.Name, classes.CID FROM classes LEFT JOIN user_classes ON classes.CID=user_classes.CID";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$stmt->bind_result($class, $cid);

	$i = 0;
	while($stmt->fetch()){
		$arr[$i] = array(
			'id' => $cid,
			'name' => $class
		);
		$i++;
	}
	$stmt->close();
	$conn->close();

	$response = ["courses" => $arr];
	echo json_encode($response);
?>