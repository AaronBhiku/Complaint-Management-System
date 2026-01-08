<?php

$customerID = isset($_GET['cid']) ? $_GET['cid'] : null;
$errorMessage = '';
$successMessage = '';

$db = new SQLite3('../data/ABC_DBA.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $customerID = $_POST['customerID'];

    $stmt = $db->prepare("UPDATE customers SET name = :name, email = :email WHERE customerID = :customerID");
    
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':customerID', $customerID, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        $successMessage = "Customer updated successfully!";
    } else {
        $errorMessage = "Failed to update Customer: " . $db->lastErrorMsg();
    }
}

if ($customerID) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE customerID = :customerID");
    $stmt->bindValue(':customerID', $customerID, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $customerData = $result->fetchArray(SQLITE3_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer</title>

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
            <h1>Edit Customer</h1>
            
            <?php if ($errorMessage): ?>
                <div class="error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <?php if ($successMessage): ?>
                <div class="success-message"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if ($customerData): ?>
                <form id="editCustomerForm" method="POST">
                    <input type="hidden" name="customerID" value="<?php echo $customerData['customerID']; ?>">
                    
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $customerData['name']; ?>" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $customerData['email']; ?>" required>

                    <button type="submit">Save Changes</button>

                    
                    <a href="ViewAllCustomers.php" class="button">Back to All Customers</a>
                </form>
            <?php else: ?>
                <p>No Customer found or invalid Customer ID.</p>
                <a href="CustomerManagement.php">Back to All Customers</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>