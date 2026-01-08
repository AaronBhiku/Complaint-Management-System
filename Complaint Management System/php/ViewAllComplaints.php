<html lang="en">

<?php
session_start();
require 'db_connection.php';


if (!isset($_SESSION['user_type'])) {
    header("Location: LoginPage.php");
    exit;
}

$userType = $_SESSION['user_type'];
$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : null;
$customerID = isset($_SESSION['customerID']) ? $_SESSION['customerID'] : null;
$jobID = isset($_SESSION['jobID']) ? $_SESSION['jobID'] : null;

function getComplaints($searchBy = '', $searchTerm = '', $page = 1, $complaintsPerPage = 10, $sortBy = '', $sortOrder = 'ASC', $userType = '', $userID = null, $customerID = null, $jobID = null) {
    $db = new SQLite3('../data/ABC_DBA.db');

    $sql = "SELECT c.*, 
               comp.companyName AS company_name, 
               r.reason AS reason_name,
               cu.name AS customer_name,
               u.fname || ' ' || u.lname AS user_name, 
               CASE WHEN c.status = 1 THEN 'Open' ELSE 'Closed' END AS status_text
            FROM complaints c
            LEFT JOIN reasons r ON c.reasonID = r.reasonID
            LEFT JOIN company comp ON c.companyID = comp.companyID
            LEFT JOIN customers cu ON c.customerID = cu.customerID
            LEFT JOIN users u ON c.userID = u.userID"; 

    $whereClause = '';
    $whereClauses = [];
    
    
    if ($userType == 'customer' && $customerID) {
        $whereClauses[] = "c.customerID = :customerID";
    } elseif ($userType == 'user' && $jobID == 1 && $userID) {
        
        $whereClauses[] = "c.companyID IN (SELECT companyID FROM users WHERE userID = :userID)";
    }
    
    
    if (!empty($searchBy) && !empty($searchTerm)) {
        if ($searchBy == 'user_name') {
            $whereClauses[] = "(u.fname || ' ' || u.lname) LIKE :searchTerm";
        } elseif ($searchBy == 'company_name') {
            $whereClauses[] = "comp.companyName LIKE :searchTerm";
        } else {
            $whereClauses[] = "$searchBy LIKE :searchTerm";
        }
    }
    
    if (!empty($whereClauses)) {
        $whereClause = " WHERE " . implode(' AND ', $whereClauses);
    }

    $orderBy = '';
    if (!empty($sortBy)) {
        $orderBy = " ORDER BY $sortBy $sortOrder";
    }

    $countSql = "SELECT COUNT(*) as total FROM ($sql $whereClause)";
    $countStmt = $db->prepare($countSql);
    
    if ($userType == 'customer' && $customerID) {
        $countStmt->bindValue(':customerID', $customerID, SQLITE3_INTEGER);
    } elseif ($userType == 'user' && $jobID == 1 && $userID) {
        $countStmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
    }
    
    if (!empty($searchBy) && !empty($searchTerm)) {
        $countStmt->bindValue(':searchTerm', "%$searchTerm%", SQLITE3_TEXT);
    }
    
    $countResult = $countStmt->execute();
    $totalComplaints = $countResult->fetchArray(SQLITE3_ASSOC)['total'];
    
    $totalPages = ceil($totalComplaints / $complaintsPerPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $complaintsPerPage;
    
    $sql = $sql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    
    $stmt->bindValue(':limit', $complaintsPerPage, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    

    if ($userType == 'customer' && $customerID) {
        $stmt->bindValue(':customerID', $customerID, SQLITE3_INTEGER);
    } elseif ($userType == 'user' && $jobID == 1 && $userID) {
        $stmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
    }
    
    if (!empty($searchBy) && !empty($searchTerm)) {
        $stmt->bindValue(':searchTerm', "%$searchTerm%", SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $arrayResult = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $arrayResult[] = $row;
    }

    return [
        'complaints' => $arrayResult,
        'totalComplaints' => $totalComplaints,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ];
}

function getAllDropdownOptions($userType, $userID, $customerID, $jobID) {
    $db = new SQLite3('../data/ABC_DBA.db');
    $options = [
        'complaintID' => [],
        'status_text' => ['Open', 'Closed'],
        'company_name' => [],
        'reason_name' => [],
        'customer_name' => [],
        'user_name' => []
    ];


    $accessFilter = '';
    if ($userType == 'customer' && $customerID) {
        $accessFilter = " WHERE c.customerID = $customerID";
    } elseif ($userType == 'user' && $jobID == 1 && $userID) {
        $accessFilter = " WHERE c.companyID IN (SELECT companyID FROM users WHERE userID = $userID)";
    }
    


    $result = $db->query("SELECT DISTINCT c.complaintID FROM complaints c 
                          $accessFilter ORDER BY c.complaintID");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['complaintID'][] = $row['complaintID'];
    }

    
    $customerFilter = '';
    if ($userType == 'customer' && $customerID) {
        $customerFilter = " WHERE customerID = $customerID";
    } elseif ($userType == 'user' && $jobID == 1 && $userID) {
        $customerFilter = " WHERE customerID IN (SELECT DISTINCT customerID FROM complaints 
                            WHERE companyID IN (SELECT companyID FROM users WHERE userID = $userID))";
    }
    
    
    $result = $db->query("SELECT DISTINCT name FROM customers $customerFilter ORDER BY name");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['customer_name'][] = $row['name'];
    }

    
    $companyFilter = '';
    if ($userType == 'user' && $jobID == 1 && $userID) {
        $companyFilter = " WHERE companyID IN (SELECT companyID FROM users WHERE userID = $userID)";
    } elseif ($userType == 'customer' && $customerID) {
        $companyFilter = " WHERE companyID IN (SELECT DISTINCT companyID FROM complaints WHERE customerID = $customerID)";
    }
    
    
    $result = $db->query("SELECT DISTINCT companyName FROM company $companyFilter ORDER BY companyName");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['company_name'][] = $row['companyName'];
    }

    
    $reasonFilter = '';
    if ($userType == 'customer' && $customerID) {
        $reasonFilter = " WHERE reasonID IN (SELECT DISTINCT reasonID FROM complaints WHERE customerID = $customerID)";
    } elseif ($userType == 'user' && $jobID == 1 && $userID) {
        $reasonFilter = " WHERE reasonID IN (SELECT DISTINCT reasonID FROM complaints 
                          WHERE companyID IN (SELECT companyID FROM users WHERE userID = $userID))";
    }
    
    
    $result = $db->query("SELECT DISTINCT reason FROM reasons $reasonFilter ORDER BY reason");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['reason_name'][] = $row['reason'];
    }

    
    $userFilter = '';
    if ($userType == 'user' && $jobID == 1 && $userID) {
        $userFilter = " WHERE companyID IN (SELECT companyID FROM users WHERE userID = $userID)";
    } elseif ($userType == 'customer' && $customerID) {
        $userFilter = " WHERE userID IN (SELECT DISTINCT userID FROM complaints WHERE customerID = $customerID)";
    }
    
    
    $result = $db->query("SELECT DISTINCT (fname || ' ' || lname) as full_name FROM users $userFilter ORDER BY full_name");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['user_name'][] = $row['full_name'];
    }

    return $options;
}

$searchBy = isset($_GET['searchBy']) ? $_GET['searchBy'] : 'company_name';
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : '';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';

$allDropdownOptions = getAllDropdownOptions($userType, $userID, $customerID, $jobID);

$result = getComplaints($searchBy, $searchTerm, $page, 10, $sortBy, $sortOrder, $userType, $userID, $customerID, $jobID);
$complaints = $result['complaints'];
$totalPages = $result['totalPages'];
$currentPage = $result['currentPage'];
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Complaints</title>
    <link rel="stylesheet" href="../css/ViewAllComplaints.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"/>
    <script src="https://kit.fontawesome.com/e3b58c845d.js" crossorigin="anonymous"></script>
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
    <h2>View All Complaints</h2>
    
    <form method="GET" action="" id="searchForm">
        <label for="searchBy">Search By:</label>
        <select name="searchBy" id="searchBy">
            <option value="complaintID" <?php echo ($searchBy == 'complaintID') ? 'selected' : ''; ?>>Complaint ID</option>
            <option value="customer_name" <?php echo ($searchBy == 'customer_name') ? 'selected' : ''; ?>>Customer Name</option>
            <option value="company_name" <?php echo ($searchBy == 'company_name') ? 'selected' : ''; ?>>Company</option>
            <option value="reason_name" <?php echo ($searchBy == 'reason_name') ? 'selected' : ''; ?>>Reason</option>
            <option value="status_text" <?php echo ($searchBy == 'status_text') ? 'selected' : ''; ?>>Status</option>
            <option value="user_name" <?php echo ($searchBy == 'user_name') ? 'selected' : ''; ?>>Help Desk Agent</option>
        </select>

        <div class="dropdown2">
            <input type="text" name="searchTerm" id="searchTerm" 
                   value="<?php echo $searchTerm; ?>" 
                   placeholder="Enter search term" 
                   onfocus="showDropdown()" 
                   onkeyup="filterFunction()">
            
            <div id="filterDropdown" class="dropdown-content2">
            </div>
        </div>

        <input type="hidden" name="page" value="1">
        <button type="submit">Search</button>
    </form>

    <table id="complaintsTable">
        <thead>
            <tr>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=complaintID&sortOrder=<?php echo ($sortBy == 'complaintID' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Complaint ID <?php if ($sortBy == 'complaintID') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=customer_name&sortOrder=<?php echo ($sortBy == 'customer_name' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Customer Name <?php if ($sortBy == 'customer_name') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=company_name&sortOrder=<?php echo ($sortBy == 'company_name' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Company <?php if ($sortBy == 'company_name') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=reason_name&sortOrder=<?php echo ($sortBy == 'reason_name' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Reason <?php if ($sortBy == 'reason_name') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=status_text&sortOrder=<?php echo ($sortBy == 'status_text' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Status <?php if ($sortBy == 'status_text') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($complaints)) : ?>
            <tr>
                <td colspan="6" style="text-align: center;">No complaints found.</td>
            </tr>
        <?php else : ?>
            <?php foreach ($complaints as $complaint) : ?>
                <tr>
                    <td><?php echo $complaint['complaintID']; ?></td>
                    <td><?php echo $complaint['customer_name']; ?></td>
                    <td><?php echo $complaint['company_name']; ?></td>
                    <td><?php echo $complaint['reason_name']; ?></td>
                    <td><?php echo $complaint['status_text']; ?></td>
                    <td>
                        <a href="ViewComplaint.php?uid=<?php echo $complaint['complaintID']; ?>">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1) : ?>
    <div class="pagination">
        <?php if ($currentPage > 1) : ?>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=<?php echo urlencode($sortBy); ?>&sortOrder=<?php echo urlencode($sortOrder); ?>&page=1"><i class="fa-solid fa-angles-left"></i></a>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=<?php echo urlencode($sortBy); ?>&sortOrder=<?php echo urlencode($sortOrder); ?>&page=<?php echo $currentPage - 1; ?>"><i class="fa-solid fa-angle-left"></i></a>
        <?php else : ?>
            <span class="disabled"><i class="fa-solid fa-angles-left"></i></span>
            <span class="disabled"><i class="fa-solid fa-angle-left"></i></span>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $currentPage) {
                echo "<span class=\"active\">$i</span>";
            } else {
                echo "<a href=\"?searchBy=" . urlencode($searchBy) . "&searchTerm=" . urlencode($searchTerm). "&sortBy=" . urlencode($sortBy) . "&sortOrder=" . urlencode($sortOrder) . "&page=$i\">$i</a>";
            }
        }
        ?>
        
        <?php if ($currentPage < $totalPages) : ?>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=<?php echo urlencode($sortBy); ?>&sortOrder=<?php echo urlencode($sortOrder); ?>&page=<?php echo $currentPage + 1; ?>"><i class="fa-solid fa-angle-right"></i></a>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=<?php echo urlencode($sortBy); ?>&sortOrder=<?php echo urlencode($sortOrder); ?>&page=<?php echo $totalPages; ?>"><i class="fa-solid fa-angles-right"></i></a>
        <?php else : ?>
            <span class="disabled"><i class="fa-solid fa-angle-right"></i></span>
            <span class="disabled"><i class="fa-solid fa-angles-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script>
    const allDropdownOptions = <?php echo json_encode($allDropdownOptions); ?>;

    function updateDropdownOptions() {
        const searchBy = document.getElementById('searchBy').value;
        const dropdown = document.getElementById('filterDropdown');
        const searchTermInput = document.getElementById('searchTerm');
        
        dropdown.innerHTML = '';
        searchTermInput.value = '';
        
        const options = allDropdownOptions[searchBy] || [];
        
        options.forEach(value => {
            const a = document.createElement('a');
            a.href = '#';
            a.textContent = value;
            a.onclick = function(e) { 
                e.preventDefault();
                selectValue(value); 
            };
            dropdown.appendChild(a);
        });
    }

    function showDropdown() {
        const dropdown = document.getElementById('filterDropdown');
        dropdown.classList.add('show');
    }

    function filterFunction() {
        const input = document.getElementById('searchTerm');
        const filter = input.value.toUpperCase();
        const div = document.getElementById('filterDropdown');
        const a = div.getElementsByTagName('a');
        
        for (let i = 0; i < a.length; i++) {
            const txtValue = a[i].textContent || a[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                a[i].style.display = '';
            } else {
                a[i].style.display = 'none';
            }
        }
    }

    function selectValue(value) {
        document.getElementById('searchTerm').value = value;
        document.getElementById('filterDropdown').classList.remove('show');
    }

    window.onclick = function(event) {
        if (!event.target.matches('#searchTerm')) {
            const dropdown = document.getElementById('filterDropdown');
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('searchBy').addEventListener('change', updateDropdownOptions);
        
        updateDropdownOptions();
    });
</script>
</body>
</html>