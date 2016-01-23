<?php

	$username = stripslashes(trim($_POST['username']));
	$password = stripslashes(trim($_POST['password']));
	
	if($username == ""){
		$response = ["error" => "Username Required"];
		echo json_encode($response);
		die();
	}

	if($password == ""){
		$response = ["error" => "Password Required"];
		echo json_encode($response);
		die();
	}

	require_once "connect.php";
	require_once "functions.php";

	global $conn;

	// verify that account exists/information is correct
	$sql = "SELECT count(*), Password FROM users WHERE Username=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$stmt->bind_result($count, $hash);
	$stmt->fetch();
	$stmt->close();

	// no users found
	if($count == 0){
		$response = ["error" => "Username or password incorrect"];
		echo json_encode($response);
	}else{
		if(password_verify($password, $hash)){
			$arr = array();	// user's and user's groups' info

			// get user's information
			$sql = "SELECT UID, Username, Name, SID, Picture FROM users WHERE Username=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$stmt->bind_result($uid, $un, $name, $sid, $pic);
			$stmt->fetch();
			$stmt->close();

			// store user's information
			$arr['uid'] 	 = $uid;
			$arr['username'] = $un;
			$arr['name'] 	 = $name;
			$arr['sid'] 	 = $sid;
			$arr['picture']  = $pic;

			// create and insert token into database
			$token = createToken(100);
			$sql = "INSERT INTO tokens (Token, UID) VALUES (?, ?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("si", $token, $uid);
			$stmt->execute();
			$stmt->close();

			$arr['token'] = $token;

			// get user's groups' information
			$sql = "SELECT GID FROM memberships WHERE UID=(SELECT UID FROM users WHERE Username=?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$stmt->bind_result($gid);

			// stores group IDs user is part of
			$arr['groups'] = array();
			while($stmt->fetch()){
				array_push($arr['groups'], $gid);
			}
			$stmt->close();
			echo json_encode($arr);
		}else{
			echo json_encode("Username or password incorrect");
		}
	}
	$conn->close();
?>