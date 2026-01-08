<?php


    $db = new SQLITE3('../data/ABC_DBA.db');  
    $sql = "SELECT fName || ' ' || COALESCE(mName,'') || ' ' || lName AS name, password, jobID, userID FROM users WHERE email = :email";   
    $stmt = $db->prepare($sql); 
    $stmt->bindParam(':email', $_POST['email'], SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $arrayResult = [];


    while ($row = $result->fetchArray(SQLITE3_ASSOC)){  
        $arrayResult[] = $row;
    }

    
    if (!empty($arrayResult)) {
        if (password_verify($_POST["password"], $arrayResult[0]['password'])) {
            session_start();
            $_SESSION['name'] = $arrayResult[0]['name'];
            $_SESSION['jobID'] = $arrayResult[0]['jobID'];
            $_SESSION['userID'] = $arrayResult[0]['userID'];
            $_SESSION['user_type'] = 'user';
            echo "true";
            header("Location: Homepage.php");
        } else {
            echo "no match";
            header("Location: LoginPage.php?Login_Error=1");
        }
    } else {
        $sql = "SELECT name, password, customerID FROM customers WHERE email = :email";   
        $stmt = $db->prepare($sql); 
        $stmt->bindParam(':email', $_POST['email'], SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $arrayResult = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)){  
            $arrayResult[] = $row;
        }

        if (!empty($arrayResult)) {
            if (password_verify($_POST["password"], $arrayResult[0]['password'])) {
                session_start();
                $_SESSION['name'] = $arrayResult[0]['name'];
                $_SESSION['customerID'] = $arrayResult[0]['customerID'];
                $_SESSION['user_type'] = 'customer';
                echo "true";
                header("Location: Homepage.php");
            } else {
                echo "no match";
                header("Location: LoginPage.php?Login_Error=1");
            }
        } else {
            echo "no user found";
            header("Location: LoginPage.php?Login_Error=1");
        }
    }
    ?>

