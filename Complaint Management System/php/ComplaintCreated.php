<?php

session_start();
$newComplaintID = isset($_SESSION['complaintID']) ? $_SESSION['complaintID'] : null;
unset($_SESSION['complaintID']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Created</title>
    <link rel="stylesheet" href="../css/ComplaintCreated.css">
    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
  />
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
    <div class="container">
        <div class="message">Complaint successfully created.</div>
        <div class="buttons">
            <button onclick="window.location.href='ViewComplaint.php?uid=<?php echo $newComplaintID; ?>'">View Complaint</button>
            <button onclick="window.location.href='ComplaintCreation.php'">Create Another Complaint</button>
            <button onclick="window.location.href='ViewAllComplaints.php'">View All Complaints</button>
        </div>
    </div>
</body>
</html>