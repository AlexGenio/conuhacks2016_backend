<?php
	// Get fileds entered by user
	if(isset($_POST['btn_register'])){
		$username = stripslashes(trim($_POST['_username']));
		$name = stripslashes(trim($_POST['_name']));
		$school = stripslashes(trim($_POST['_school']));
		$picture = stripslashes(trim($_POST['_picture']));
		$password = stripslashes(trim($_POST['_password']));
		$description = stripslashes(trim($_POST['_description']));
	}
	
	// Validate inputed fields
	if($username == ""){
		$response = ["error" => "Username required"]
		echo json_encode($response);
		die();
	}
	if($name == ""){
		$response = ["error" => "Name required"]
		echo json_encode($response);
		die();
	}
	if($school == ""){
		$response = ["error" => "School required"]
		echo json_encode($response);
		die();
	}
	if($password == ""){
		$response = ["error" => "Username required")]
		echo json_encode($response);
		die();
	}
	
	// Encrypt the password
	$hash = password_hash($password, PASSWORD_BCRYPT);

	// Create connection
	require_once 'connect.php';
	global $conn;

	// Validate uniqueness of user being registered
	$theSql = "SELECT count(*) FROM users WHERE Username=?";
	$statement = $conn->prepare($theSql);
	$statement->bind_param("s", $username);
	$statement->bind_result($result);
	$statement->execute();
	$statement->fetch();
	$statement->close();

	if($result == 0){
		// Unique user
		// Check if the school is registered
		$theSql = "SELECT count(*) FROM schools WHERE Name=?";
		$statement = $conn->prepare($theSql);
		$statement->bind_param("s", $school);
		$statement->bind_result($result);
		$statement->execute();
		$statement->fetch();
		$statement->close();

		if($result == 0){
			// Register the school
			$theSql = "INSERT INTO schools (Name) VALUES (?)";
			$statement = $conn->prepare($theSql);
			$statement->bind_param("s", $school);
			$statement->execute();
			$statement->close();
		}

		// Get the school's ID
		$theSql = "SELECT SID FROM schools WHERE Name=?";
		$statement = $conn->prepare($theSql);
		$statement->bind_param("s", $school);
		$statement->bind_result($SID);
		$statement->execute();
		$statement->fetch();
		$statement->close();

		// Register the user
		$theSql = "INSERT INTO users (Username, Name, SID, Picture, Password, Description) 
				VALUES (?, ?, ?, ?, ?, ?)";
		$statement = $conn->prepare($theSql);
		$statement->bind_param("ssisss", $username, $name, $SID, $picture, $hash, $description);
		$statement->execute();
		$statement->close();

		$response = ["success" => "User registered")]
		echo json_encode($response);
	}else{
		$response = ($response);
	}
	$conn->close();
	die();
?>