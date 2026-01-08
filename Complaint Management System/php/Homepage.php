<?php

session_start();
require 'db_connection.php';
$db = connectToDatabase();


if (!isset($_SESSION['user_type'])) {
    header("Location: LoginPage.php");
    exit;
}

$userType = $_SESSION['user_type'];
$userData = [];


if ($userType == 'user') {
    $userID = $_SESSION['userID'];
    
    $stmt = $db->prepare("SELECT fName, mName, lName, email, jobID
                          FROM users
                          WHERE userID = :userID");
    $stmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $userData = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$userData) {
        echo "User not found in the database.";
        exit;
    }
} elseif ($userType == 'customer') {
    $customerID = $_SESSION['customerID'];
    
    $stmt = $db->prepare("SELECT name, email
                          FROM customers
                          WHERE customerID = :customerID");
    $stmt->bindValue(':customerID', $customerID, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $userData = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$userData) {
        echo "Customer not found in the database.";
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Homepage</title>
    <link rel="stylesheet" href="../css/Homepage.css">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
</head>

<body>
    <header>
        <a href="Homepage.php"><img class="logo" src="../abc.png" alt="ABC Logo"></a>
        <nav>
            <ul class="left-menu">
                <li><a href="Homepage.php"><i class="fa-solid fa-house"></i> ABC Home</a></li>
                <li><a href="../html/Contact.html"><i class="fa-solid fa-envelope"></i> Contact</a></li>
            </ul>
            <ul class="right-menu">
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn"><i class="fa-solid fa-circle-user"></i> MyAccount</a>
                    <div class="dropdown-content">
                        <a href="logOut.php">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>
<main>
        <section class="welcome-section">
            <h1>Welcome, <?php 
                if ($userType == 'user') {
                    echo htmlspecialchars($userData['fName'] . ' ' . ($userData['mName'] ?: '') . ' ' . $userData['lName']);
                } else {
                    echo htmlspecialchars($userData['name']);
                }
            ?></h1>
            <h2>
                <?php
                if ($userType == 'customer') {
                    echo "Customer";
                } elseif ($userData['jobID'] == 1) {
                    echo "Help Desk Agent";
                } elseif ($userData['jobID'] == 2) {
                    echo "Admin";
                } else {
                    echo "Unknown Role";
                }
                ?>
            </h2>
        </section>
        <section class="quick-links">
            <h2>Quick Links</h2>
            <div class="links-container <?php
                if ($userType == 'customer') {
                    echo 'customer';
                } elseif (isset($_SESSION['jobID']) && $_SESSION['jobID'] == 2) {
                    echo 'admin';
                } else {
                    echo 'Help Desk Agent';
                }
            ?>">
                <a href="../php/ComplaintCreation.php" class="link-box">Create New Complaint</a>
                <a href="../php/ViewAllComplaints.php" class="link-box">View All Complaints</a>
                <?php if ($userType == 'user') { ?>
                    <a href="ViewAllCustomers.php" class="link-box">View All Customers</a>
                    <?php if ($_SESSION['jobID'] == 2) { ?>
                        <a href="UserManagement.php" class="link-box user-management">Manage Users</a>
                    <?php } ?>
                <?php } ?>
            </div>
        </section>
    </main>

    <script>
        document.getElementById("year").innerHTML = new Date().getFullYear();
        document.getElementById("currentDate").innerHTML = new Date().toLocaleDateString();
    </script>
</body>

</html>