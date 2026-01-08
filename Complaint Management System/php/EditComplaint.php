<?php

session_start();
require 'db_connection.php';
$db = connectToDatabase();


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: Homepage.php");
    exit;
}

$complaintID = isset($_GET['uid']) ? $_GET['uid'] : null;
$errorMessage = '';
$successMessage = '';
$complaintData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = isset($_POST['complaintStatus']) ? 0 : 1;
    $complaintID = $_POST['complaintID'];
    $oldStatus = $_POST['oldStatus'];
    $userID = $_SESSION['userID'];

    $closedDate = null;
    if ($oldStatus == 1 && $newStatus == 0) {
        $closedDate = date('Y-m-d H:i:s');
        $stmt = $db->prepare("UPDATE complaints SET status = :status, closed = :closedDate WHERE complaintID = :complaintID");
        $stmt->bindValue(':closedDate', $closedDate, SQLITE3_TEXT);
    } elseif ($newStatus == 1 && $oldStatus == 0) {
        $stmt = $db->prepare("UPDATE complaints SET status = :status, closed = NULL WHERE complaintID = :complaintID");
    } else {
        $stmt = $db->prepare("UPDATE complaints SET status = :status WHERE complaintID = :complaintID");
    }
    
    $stmt->bindValue(':status', $newStatus, SQLITE3_INTEGER);
    $stmt->bindValue(':complaintID', $complaintID, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        $successMessage = "Complaint updated successfully!";
    } else {
        $errorMessage = "Failed to update Complaint: " . $db->lastErrorMsg();
    }
}

if ($complaintID) { 
    $stmt = $db->prepare("SELECT c.*, 
                        d.companyName AS company_name, 
                        r.reason AS reason_name,
                        cu.name AS customer_name
                        FROM complaints c
                        LEFT JOIN reasons r ON c.reasonID = r.reasonID
                        LEFT JOIN company d ON c.companyID = d.companyID
                        LEFT JOIN customers cu ON c.customerID = cu.customerID
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
    <title>Edit Complaint</title>
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
            <h1>Edit Complaint</h1>
            
            <?php if ($errorMessage): ?>
                <div class="error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <?php if ($successMessage): ?>
                <div class="success-message"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if ($complaintData): ?>
                <form id="editComplaintForm" method="POST">
                    <input type="hidden" name="complaintID" value="<?php echo $complaintData['complaintID']; ?>">
                    <input type="hidden" name="oldStatus" value="<?php echo $complaintData['status']; ?>">
                    
                    <label for="companyName">Company:</label>
                    <input type="text" id="companyName" name="companyName" value="<?php echo $complaintData['company_name']; ?>" readonly>
                    
                    <label for="reasonName">Reason:</label>
                    <input type="text" id="reasonName" name="reasonName" value="<?php echo $complaintData['reason_name']; ?>" readonly>

                    <label for="customerName">Customer Name:</label>
                    <input type="text" id="customerName" name="customerName" value="<?php echo $complaintData['customer_name']; ?>" readonly>

                    <label for="openedDate">Opened Date:</label>
                    <input type="text" id="openedDate" name="openedDate" value="<?php echo $complaintData['created']; ?>" readonly>
                    
                    <label for="complaintNotes">Complaint Notes:</label>
                    <textarea id="complaintNotes" name="complaintNotes" rows="4" readonly><?php echo $complaintData['description']; ?></textarea>
                    
                    <div class="toggle-container">
                    <span class="toggle-label">Complaint Status:</span>
                    <span id="statusText" class="toggle-status"><?php echo $complaintData['status'] == 1 ? 'Open' : 'Closed'; ?></span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="complaintStatus" name="complaintStatus" <?php echo $complaintData['status'] == 0 ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    
                    </div>

                    <button type="submit">Save Changes</button>

                    <a href="ViewAllComplaints.php" class="button">Back to All Complaints</a>
                </form>
            <?php else: ?>
                <p>No Complaint found or invalid Complaint ID.</p>
                <a href="ViewAllComplaints.php">Back to All Complaints</a>
            <?php endif; ?>
        </div>
    </main>
    <script>
        document.getElementById("year").innerHTML = new Date().getFullYear();
        
        document.getElementById('complaintStatus').addEventListener('change', function() {
            document.getElementById('statusText').textContent = this.checked ? 'Closed' : 'Open';
        });
    </script>
</body>
</html>