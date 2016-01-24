<?php
    
    function validateNewUser($username, $name, $school, $password){
        // Validate inputed fields
        if($username == ""){
            $response = ["error" => "Username required"];
            echo json_encode($response);
            die();
        }
        if($name == ""){
            $response = ["error" => "Name required"];
            echo json_encode($response);
            die();
        }
        if($school == ""){
            $response = ["error" => "School required"];
            echo json_encode($response);
            die();
        }
        if($password == ""){
            $response = ["error" => "Password required"];
            echo json_encode($response);
            die();
        }
    }

    function checkUniqueUsername($conn, $username){
        // Validate uniqueness of user being registered
        $theSql = "SELECT count(*) FROM users WHERE Username=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("s", $username);
        $statement->bind_result($result);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        return $result;
    }

    function registerUser($conn, $username, $name, $school, $picture, $hash, $description){
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
    }

    function crypto_rand_secure($min, $max){
        $range = $max - $min;
        if($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    function createToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[crypto_rand_secure(0, $max)];
        }
        return $token;
    }

    function checkEmptyToken($token){
        // Validate token
        if($token == ""){
            $response = ["error" => "Invalid token"];
            echo json_encode($response);
            die();
        }
    }

    function checkExpiredToken($conn, $token){
        // Check if token is expired
        $theSql = "SELECT count(*) FROM tokens WHERE Token=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("s", $token);
        $statement->bind_result($result);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        if($result == 0){
            $response = ["error" => "Expired token"];
            echo json_encode($response);
            die();
        }
    }

    function getUserByToken($conn, $token){
        // Token is valid and unexpired
        // Get the user corresponding to the token
        $theSql = "SELECT UID FROM users WHERE UID=(SELECT UID FROM tokens WHERE Token=?)";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("s", $token);
        $statement->bind_result($UID);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        return $UID;
    }

    function filterSchoolByName($conn, $school){
        $theSql = "SELECT Name FROM schools WHERE lower(Name) LIKE lower(CONCAT(?, '%'))";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("s", $school);
        $statement->bind_result($name);
        $statement->execute();

        $schoolArr = array();
        $schoolArr['schools'] = array();

        while($statement->fetch()){
            array_push($schoolArr['schools'], $name);
        }

        $statement->close();

        return $schoolArr;
    }

    function deleteToken($conn, $token){
        $theSql = "DELETE FROM tokens WHERE Token=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("s", $token);
        $statement->execute();
        $statement->fetch();
        $statement->close();
    }

    function getSchoolID($conn, $UID){
        $theSql = "SELECT SID FROM users WHERE UID=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("i", $UID);
        $statement->bind_result($SID);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        return $SID;
    }

    function addClass($conn, $SID, $UID, $class){
        // Check if the class exists
        $theSql = "SELECT count(*) FROM classes WHERE Name=upper(?)";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("s", $class);
        $statement->bind_result($result);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        if($result == 0){
            // Register the class in the schools
            $theSql = "INSERT INTO classes (Name, SID) VALUES (upper(?), ?)";
            $statement = $conn->prepare($theSql);
            $statement->bind_param("si", $class, $SID);
            $statement->execute();
            $statement->close();
        }

        // Get the class ID
        $theSql = "SELECT CID FROM classes WHERE Name=? AND SID=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("si", $class, $SID);
        $statement->bind_result($CID);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        // Check if the student already has the class
        $theSql = "SELECT count(*) FROM user_classes WHERE CID=? and UID=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("ii", $CID, $UID);
        $statement->bind_result($result);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        if($result == 0){
            // Register the class for the student
            $theSql = "INSERT INTO user_classes (CID, UID) VALUES (?, ?)";
            $statement = $conn->prepare($theSql);
            $statement->bind_param("ii", $CID, $UID);
            $statement->execute();
            $statement->close();
        }

        $arr = array();
        $arr['id'] = $CID;
        $arr['class'] = strtoupper($class);

        return $arr;
    }

    function getUserClasses($conn, $SID, $UID){
        $theSql = "SELECT classes.Name, classes.CID FROM classes RIGHT JOIN user_classes ON classes.CID=user_classes.CID
                   WHERE classes.SID=? AND user_classes.UID=? ORDER BY classes.Name ASC";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("ii", $SID, $UID);
        $statement->bind_result($class, $CID);
        $statement->execute();

        $arr = array(); // stores class names
        $i = 0;
        while($statement->fetch()){
            $arr[$i] = array(
                'id' => $CID,
                'name' => $class
            );
            $i++;
        }
        $statement->close();

        return $arr;
    }

    function getUserClassesID($conn, $SID, $UID){
        $theSql = "SELECT classes.CID FROM classes RIGHT JOIN user_classes ON classes.CID=user_classes.CID
                   WHERE classes.SID=? AND user_classes.UID=? ORDER BY classes.Name ASC";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("ii", $SID, $UID);
        $statement->bind_result($CID);
        $statement->execute();

        $arr = array(); // stores class IDs
        while($statement->fetch()){
            array_push($arr, $CID);
        }
        $statement->close();

        return $arr;
    }

    function getCommonClasses($conn, $classes, $UID){
        $ids = implode(', ', $classes);
        $theSql = "SELECT UID FROM user_classes WHERE CID IN ($ids) AND UID!=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("i", $UID);
        $statement->bind_result($userID);
        $statement->execute();

        $arr = array(); // stores class names
        $i = 0;
        while($statement->fetch()){
            $arr[$i] = $userID;
            $i++;
        }
        $statement->close();

        return $arr;
    }

    function getUserDetails($conn, $common, $UID){
        $ids = implode(', ', $common);
        $theSql = "SELECT UID, Username, Name, Picture, Description FROM users WHERE UID IN ($ids)";
        $statement = $conn->prepare($theSql);
        $statement->bind_result($userID, $username, $name, $picture, $description);
        $statement->execute();

        while($statement->fetch()){
            $oneRow['id']=$userID;
            $oneRow['username']=$username;
            $oneRow['name']=$name;
            $oneRow['picture']=$picture;
            $oneRow['description']=$description;
            $allRows[]=$oneRow;
        }
        $statement->close();

        return $allRows;
    }

    function getUserSwipeDetails($conn, $UID){
        $theSql = "SELECT UID, Username, Name, Picture, Description FROM users WHERE UID=?";
        $statement = $conn->prepare($theSql);
        $statement->bind_param("i", $UID);
        $statement->bind_result($userID, $username, $name, $picture, $description);
        $statement->execute();
        $statement->fetch();
    
        $allRows = array();
        
        $oneRow['id']=$userID;
        $oneRow['username']=$username;
        $oneRow['name']=$name;
        $oneRow['picture']=$picture;
        $oneRow['description']=$description;
        $allRows[]=$oneRow;

        $statement->close();

        return $allRows;
    }

    function getSchoolClasses($conn, $SID){
        $sql = "SELECT CID, Name FROM classes WHERE SID=? ORDER BY Name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $SID);
        $stmt->execute();
        $stmt->bind_result($cid, $course);

        $arr = array(); // stores course names
        $i = 0;
        while($stmt->fetch()){
            $arr[$i] = array(
                'id' => $cid,
                'name' => $course
            );
            $i++;
        }
        $stmt->close();

        return $arr;
    }

    function insertSwipeResult($conn, $UID, $swipee, $value, $CID){  
        $sql = "SELECT count(*) FROM swipes WHERE UID=? AND RID=? AND CID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $UID, $swipee, $CID);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        $stmt->close();

        if($result == 0){
            $sql = "INSERT INTO swipes (UID, RID, Value, CID) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $UID, $swipee, $value, $CID);
            $stmt->execute();
            $stmt->close();
        }else{
            $response = ["error" => "Double swipe"];
            echo json_encode($response);
            die();
        }
    }

    function getSwipeeValue($conn, $UID, $swipee, $CID){
        $sql = "SELECT value FROM swipes WHERE RID=? AND CID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $swipee, $CID);
        $stmt->execute();
        $stmt->bind_result($valSwipee);
        $stmt->fetch();
        $stmt->close();

        return $valSwipee;
    }

    function getClassValue($conn, $UID, $swipee, $CID){
        $sql = "SELECT CID FROM swipes WHERE RID=? AND CID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $swipee, $CID);
        $stmt->execute();
        $stmt->bind_result($cidSwipee);
        $stmt->fetch();
        $stmt->close();

        return $cidSwipee;
    }

    function checkIfGroupExists($conn, $UID, $swipee, $CID){
        $sql = "SELECT count(*) FROM memberships WHERE UID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $UID);
        $stmt->execute();
        $stmt->bind_result($userVal);
        $stmt->fetch();
        $stmt->close();

        $sql = "SELECT count(*) FROM memberships WHERE UID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $swipee);
        $stmt->execute();
        $stmt->bind_result($swipeeVal);
        $stmt->fetch();
        $stmt->close();

        if($userVal > 0){
            // get user's groups' IDs
            $sql = "SELECT GID FROM memberships WHERE UID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $UID);
            $stmt->execute();
            $stmt->bind_result($gid);

            $gidUserArr = array();
            while($stmt->fetch()){
                array_push($gidArr, $gid);
            }
            $stmt->close();

            $userGroups = implode(', ', $gidArr);

            // check if existing group is for desired class
            $sql = "SELECT count(*) FROM groups WHERE GID IN ($userGroups) AND CID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID);
            $stmt->execute();
            $stmt->bind_result($userGroupCount);
            $stmt->fetch();
            $stmt->close();
        }

        if($swipeeVal > 0){
            // get swipee's groups' IDs
            $sql = "SELECT GID FROM memberships WHERE UID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $swipee);
            $stmt->execute();
            $stmt->bind_result($gid);

            $gidArr = array();
            while($stmt->fetch()){
                array_push($gidSwipeeArr, $gid);
            }
            $stmt->close();

            $swipeeGroups = implode(', ', $gidSwipeeArr);

            // check if existing group is for desired class
            $sql = "SELECT count(*) FROM groups WHERE GID IN ($swipeeGroups) AND CID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID);
            $stmt->execute();
            $stmt->bind_result($swipeeGroupCount);
            $stmt->fetch();
            $stmt->close();
        }

        if($userGroupCount == 0 && $swipeeGroupCount == 0){
            // create group
            $sql = "INSERT INTO groups (CID) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID);
            $stmt->execute();
            $stmt->close();

            // get group id
            $lastID = $conn->insert_id;

            // add members to group
            $sql = "INSERT INTO memberships (GID, UID) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $lastID, $UID);
            $stmt->execute();
            $stmt->close();

            $sql = "INSERT INTO memberships (GID, UID) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $lastID, $swipee);
            $stmt->execute();
            $stmt->close();
        }elseif($userGroupCount == 0 && $swipeeGroupCount == 1){
            // get swipee's group id
            $sql = "SELECT memberships.GID FROM memberships RIGHT JOIN groups ON memberships.GID=groups.GID WHERE groups.CID=? AND memberships.UID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID, $swipee);
            $stmt->execute();
            $stmt->bind_result($gid);
            $stmt->fetch();
            $stmt->close();

            // add user to group
            $sql = "INSERT INTO memberships (GID, UID) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $gid, $UID);
            $stmt->execute();
            $stmt->close();
        }elseif($userGroupCount == 1 && $swipeeGroupCount == 0){
            // get users's group id
            $sql = "SELECT memberships.GID FROM memberships RIGHT JOIN groups ON memberships.GID=groups.GID WHERE groups.CID=? AND memberships.UID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID, $UID);
            $stmt->execute();
            $stmt->bind_result($gid);
            $stmt->fetch();
            $stmt->close();

            // add swipee to group
            $sql = "INSERT INTO memberships (GID, UID) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $gid, $swipee);
            $stmt->execute();
            $stmt->close();
        }elseif($userGroupCount == 1 && $swipeeGroupCount == 1){
            // get users's group id
            $sql = "SELECT memberships.GID FROM memberships RIGHT JOIN groups ON memberships.GID=groups.GID WHERE groups.CID=? AND memberships.UID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID, $UID);
            $stmt->execute();
            $stmt->bind_result($gid);
            $stmt->fetch();
            $stmt->close();

            // add swipee to group
            $sql = "INSERT INTO memberships (GID, UID) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $gid, $swipee);
            $stmt->execute();
            $stmt->close();

            // get swipee's group id
            $sql = "SELECT memberships.GID FROM memberships RIGHT JOIN groups ON memberships.GID=groups.GID WHERE groups.CID=? AND memberships.UID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $CID, $swipee);
            $stmt->execute();
            $stmt->bind_result($gid);
            $stmt->fetch();
            $stmt->close();

            // add user to group
            $sql = "INSERT INTO memberships (GID, UID) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $gid, $UID);
            $stmt->execute();
            $stmt->close();
        }
    }
?>