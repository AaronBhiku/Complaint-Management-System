<?php
session_start();
require 'db_connection.php';
$db = connectToDatabase();


if (!isset($_SESSION['user_type'])) {
    header("Location: LoginPage.php");
    exit;
}

$userType = $_SESSION['user_type'];


$company = [];
$compResult = $db->query("SELECT companyID, companyName FROM company;");
while ($row = $compResult->fetchArray(SQLITE3_ASSOC)) {
    $company[] = $row;
}


$reasons = [];
$reasonResult = $db->query("SELECT reasonID, reason FROM reasons;");
while ($row = $reasonResult->fetchArray(SQLITE3_ASSOC)) {
    $reasons[] = $row;
}


$customers = [];
if ($userType == 'user') {
    $cResult = $db->query("SELECT customerID, name FROM customers;");
    while ($row = $cResult->fetchArray(SQLITE3_ASSOC)) {
        $customers[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitComplaint'])) {
    
    $companyID    = !empty($_POST['companyID']) ? (int)$_POST['companyID'] : null;
    $reasonID     = !empty($_POST['reasonID']) ? (int)$_POST['reasonID'] : null;
    $description  = $_POST['description']    ?? '';

    
    if ($userType == 'user') {
        $userID = $_SESSION['userID'];
        $customerID = $_POST['customerID'] ?? null;

        
        if ($customerID === 'addNew') {
            $customerName = $_POST['newCustomerName'] ?? null;
            $customerEmail = $_POST['newCustomerEmail'] ?? null;

            if ($customerName && $customerEmail) {
                
                $sql = "INSERT INTO customers (name, email) VALUES (:name, :email);";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':name', $customerName, SQLITE3_TEXT);
                $stmt->bindValue(':email', $customerEmail, SQLITE3_TEXT);
                $stmt->execute();

                $customerID = $db->lastInsertRowID();
            } else {
                
                $_SESSION['error'] = "Customer name and email are required.";
                header('Location: ComplaintCreation.php');
                exit;
            }
        }
    } else {
        
        $userID = 0;
        
        if (isset($_SESSION['customerID'])) {
            $customerID = $_SESSION['customerID'];
        } else {
            
            $_SESSION['error'] = "Customer session not found. Please log in again.";
            header('Location: LoginPage.php');
            exit;
        }
    }

    
    $createdTime = date('Y-m-d H:i:s');
    
    
    if (empty($companyID) || empty($reasonID) || empty($customerID)) {
        $_SESSION['error'] = "Missing required fields. Please ensure Company and Reason are selected.";
        header('Location: ComplaintCreation.php');
        exit;
    }
    
    $sql = "INSERT INTO complaints 
            (userID, companyID, reasonID, description, status, created, closed, customerID)
            VALUES 
            (:userID, :companyID, :reasonID, :description, :status, :created, :closed, :customerID)
    ";
    $stmt = $db->prepare($sql);
    
    
    if (!$stmt) {
        $_SESSION['error'] = "Database error: Failed to prepare statement.";
        header('Location: ComplaintCreation.php');
        exit;
    }
    
    
    $stmt->bindValue(':userID',      $userID,       SQLITE3_INTEGER);
    $stmt->bindValue(':companyID',    $companyID,     SQLITE3_INTEGER);
    $stmt->bindValue(':reasonID',    $reasonID,     SQLITE3_INTEGER);
    $stmt->bindValue(':description', $description,  SQLITE3_TEXT);
    $stmt->bindValue(':status',      1,             SQLITE3_INTEGER);
    $stmt->bindValue(':created',     $createdTime,  SQLITE3_TEXT);
    $stmt->bindValue(':closed',      null,          SQLITE3_NULL);
    $stmt->bindValue(':customerID',  $customerID,   SQLITE3_INTEGER);
    
    
    $result = $stmt->execute();
    
    if (!$result) {
        
        $errorMsg = $db->lastErrorMsg();
        $_SESSION['error'] = "Failed to create complaint: " . $errorMsg;
        header('Location: ComplaintCreation.php');
        exit;
    }

    $newComplaintID = $db->lastInsertRowID();
    $_SESSION['complaintID'] = $newComplaintID;



    header('Location: complaintCreated.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Complaint</title>
    <link rel="stylesheet" href="../css/ComplaintCreation.css">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
    />
    <style>
        #newCustomerFields {
            display: none;
            margin-top: 10px;
        }
    </style>
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
        <h2>Create a Complaint</h2>
        <?php 
        if (isset($_SESSION['error'])) {
            echo "<p style='color: red;'>" . htmlspecialchars($_SESSION['error']) . "</p>";
            unset($_SESSION['error']);
        }
        ?>
        <form method="POST" action="ComplaintCreation.php" id="complaintForm">
            
            <label for="companyID">Company:</label>
            <select id="companyID" name="companyID" required>
                <option value="">-- Select Company --</option>
                <?php foreach ($company as $comp): ?>
                    <option value="<?php echo $comp['companyID']; ?>">
                        <?php echo htmlspecialchars($comp['companyName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="reasonID">Reason:</label>
            <select id="reasonID" name="reasonID" required>
                <option value="">-- Select Reason --</option>
                <?php foreach ($reasons as $reason): ?>
                    <option value="<?php echo $reason['reasonID']; ?>">
                        <?php echo htmlspecialchars($reason['reason']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($userType == 'user'): ?>
            <label for="customerID">Customer:</label>
            <select id="customerID" name="customerID" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['customerID']; ?>">
                        <?php echo htmlspecialchars($customer['name']); ?>
                    </option>
                <?php endforeach; ?>
                <option value="addNew">+ Add New Customer</option>
            </select>

            <div id="newCustomerFields">
                <label for="newCustomerName">Customer Full Name:</label>
                <input type="text" id="newCustomerName" name="newCustomerName">

                <label for="newCustomerEmail">Customer Email:</label>
                <input type="email" id="newCustomerEmail" name="newCustomerEmail">
            </div>
            <?php endif; ?>

            <label for="description">Notes:</label>
            <textarea id="description" name="description" rows="4"></textarea>

            <button type="submit" name="submitComplaint">Submit Complaint</button>
        </form>
    </main>

    <script>
    document.getElementById("year").innerHTML = new Date().getFullYear();

    <?php if ($userType == 'user'): ?>
    const customerSelect = document.getElementById('customerID');
    const newCustomerFields = document.getElementById('newCustomerFields');
    const newCustomerName = document.getElementById('newCustomerName');
    const newCustomerEmail = document.getElementById('newCustomerEmail');
    customerSelect.addEventListener('change', function() {
        if (this.value === 'addNew') {
            newCustomerFields.style.display = 'block';
            newCustomerName.required = true;
            newCustomerEmail.required = true;
        } else {
            newCustomerFields.style.display = 'none';
            newCustomerName.required = false;
            newCustomerEmail.required = false;
        }
    });

    document.getElementById('complaintForm').addEventListener('submit', function(event) {
        if (customerSelect.value === 'addNew') {
            if (!newCustomerName.value || !newCustomerEmail.value) {
                event.preventDefault();
                alert('Please fill in the customer name and email.');
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>