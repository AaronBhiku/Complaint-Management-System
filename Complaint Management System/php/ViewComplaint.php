<?php

session_start();

$complaintID = isset($_GET['uid']) ? $_GET['uid'] : null;
$complaintData = null;
$db = new SQLite3('../data/ABC_DBA.db');

if ($complaintID) {
    $stmt = $db->prepare("SELECT c.*, 
                    co.companyName AS company_name, 
                    r.reason AS reason_name,
                    cu.name AS customer_name,
                    u.fname || ' ' || u.lname AS user_name, 
                    CASE WHEN c.status = 1 THEN 'Open' ELSE 'Closed' END AS status_text
                FROM complaints c
                LEFT JOIN reasons r ON c.reasonID = r.reasonID
                LEFT JOIN company co ON c.companyID = co.companyID
                LEFT JOIN customers cu ON c.customerID = cu.customerID
                LEFT JOIN users u ON c.userID = u.userID
                WHERE c.complaintID = :complaintID");
    $stmt->bindValue(':complaintID', $complaintID, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $complaintData = $result->fetchArray(SQLITE3_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint</title>
    <link rel="stylesheet" href="../css/EditComplaint.css">
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
    <main>
        <div class="container">
            <h1>View Complaint</h1>
            
            <?php if ($complaintData): ?>
                <div id="viewComplaintForm">
                    <div class="form-group">
                        <label>Complaint ID:</label>
                        <p><?php echo $complaintData['complaintID']; ?></p>
                    </div>
                    <div class="form-group">
                        <label>Help Desk Agent:</label>
                        <p><?php echo $complaintData['user_name']; ?></p>
                    </div>
                    <div class="form-group">
                        <label>Company:</label>
                        <p><?php echo $complaintData['company_name']; ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason:</label>
                        <p><?php echo $complaintData['reason_name']; ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Customer Name:</label>
                        <p><?php echo $complaintData['customer_name']; ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Complaint Status:</label>
                        <p><?php echo $complaintData['status_text']; ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Created Date:</label>
                        <p><?php echo $complaintData['created']; ?></p>
                    </div>
                    
                    <?php if ($complaintData['closed']): ?>
                    <div class="form-group">
                        <label>Closed Date:</label>
                        <p><?php echo $complaintData['closed']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Complaint Notes:</label>
                        <div class="notes-container">
                            <?php echo nl2br(htmlspecialchars($complaintData['description'])); ?>
                        </div>
                    </div>
                    
                    <a href="ViewAllComplaints.php" class="button">Back to All Complaints</a>

                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user'): ?>
                        <a href="EditComplaint.php?uid=<?php echo $complaintData['complaintID']; ?>" class="button">Edit Complaint</a>                            
                    <?php endif; ?>
                    
                </div>
            <?php else: ?>
                <p>No complaint found or invalid complaint ID.</p>
                <a href="ViewAllComplaints.php">Back to All Complaints</a>
            <?php endif; ?>
        </div>
    </main>


</body>
</html>