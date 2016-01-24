<?php
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
?>