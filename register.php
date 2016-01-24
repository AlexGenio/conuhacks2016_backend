<?php
	// Get fileds entered by user
	$username = stripslashes(trim($_POST['username']));
	$name = stripslashes(trim($_POST['name']));
	$school = stripslashes(trim($_POST['school']));
	$picture = stripslashes(trim($_POST['picture']));
	$password = stripslashes(trim($_POST['password']));
	$description = stripslashes(trim($_POST['description']));
	
	require_once 'functions.php';

	// Validate inputed fields
	validateNewUser($username, $name, $school, $password);

	// Set default icon
	if($picture == ""){
		$picture = '/pictures/icon_default.png';
	}
	
	require_once 'connect.php';
	require_once 'passwordLib.php';

	// Encrypt the password
	$hash = password_hash($password, PASSWORD_BCRYPT);

	// Create connection
	global $conn;

	$result = checkUniqueUsername($conn, $username);

	if($result == 0){
		// Unique user
		// Register the user
		registerUser($conn, $username, $name, $school, $picture, $hash, $description);

		$response = ["success" => "User registered"];
		echo json_encode($response);
	}else{
		$response = ["Error" => "Username taken"];
		echo json_encode($response);
	}
	$conn->close();
	die();
?>