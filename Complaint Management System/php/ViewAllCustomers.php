<!DOCTYPE html>
<html lang="en">

<?php
session_start();

require 'db_connection.php';
if (!isset($_SESSION['userID']) || $_SESSION['jobID'] != 2) {
    header('Location: Login.php');
    exit;
}

function getCustomers($searchBy = '', $searchTerm = '', $page = 1, $customersPerPage = 10, $sortBy = '', $sortOrder = 'ASC') {
    $db = new SQLite3('../data/ABC_DBA.db');

    $sql = "SELECT customerID, name, email FROM customers";

    $whereClause = '';
    if (!empty($searchBy) && !empty($searchTerm)) {
        if ($searchBy == 'fullName') {
            $whereClause = " WHERE name LIKE :searchTerm";
        } else {
            $whereClause = " WHERE $searchBy LIKE :searchTerm";
        }
    }

    $orderBy = '';
    if (!empty($sortBy)) {
        $orderBy = " ORDER BY $sortBy $sortOrder";
    }

    $countSql = "SELECT COUNT(*) as total FROM ($sql $whereClause)";
    $countStmt = $db->prepare($countSql);

    if (!empty($searchBy) && !empty($searchTerm)) {
        $countStmt->bindValue(':searchTerm', "%$searchTerm%", SQLITE3_TEXT);
    }

    $countResult = $countStmt->execute();
    $totalComplaints = $countResult->fetchArray(SQLITE3_ASSOC)['total'];

    $totalPages = ceil($totalComplaints / $customersPerPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $customersPerPage;

    $sql = $sql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':limit', $customersPerPage, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

    if (!empty($searchBy) && !empty($searchTerm)) {
        $stmt->bindValue(':searchTerm', "%$searchTerm%", SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $paginatedResult = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $paginatedResult[] = $row;
    }

    return [
        'data' => $paginatedResult,
        'pagination' => [
            'totalComplaints' => $totalComplaints,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ],
    ];
}

function getAllDropdownOptions() {
    $db = new SQLite3('../data/ABC_DBA.db');
    $options = [
        'customerID' => [],
        'name' => [],
        'email' => []
    ];

    $result = $db->query("SELECT DISTINCT customerID FROM customers ORDER BY customerID");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['customerID'][] = $row['customerID'];
    }

    $result = $db->query("SELECT DISTINCT name FROM customers ORDER BY name");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['name'][] = $row['name'];
    }

    $result = $db->query("SELECT DISTINCT email FROM customers ORDER BY email");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['email'][] = $row['email'];
    }

    return $options;
}

$searchBy = isset($_GET['searchBy']) ? $_GET['searchBy'] : '';
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : '';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';

$allDropdownOptions = getAllDropdownOptions();

$result = getCustomers($searchBy, $searchTerm, $page, 10, $sortBy, $sortOrder);
$customers = $result['data'];
$totalPages = $result['pagination']['totalPages'];
$currentPage = $result['pagination']['currentPage'];
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customers</title>
    <link rel="stylesheet" href="../css/ViewAllComplaints.css">
    <script src="https://kit.fontawesome.com/e3b58c845d.js" crossorigin="anonymous"></script>
</head>
<body>
<header>
    <i class="fa-sharp fa-light fa-arrow-up"></i>
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
    <h2>View All Customers</h2>
    <form method="GET" action="" id="searchForm">
        <label for="searchBy">Search By:</label>
        <select name="searchBy" id="searchBy">
            <option value="customerID" <?php echo ($searchBy == 'customerID') ? 'selected' : ''; ?>>Customer ID</option>
            <option value="name" <?php echo ($searchBy == 'name') ? 'selected' : ''; ?>>Name</option>
            <option value="email" <?php echo ($searchBy == 'email') ? 'selected' : ''; ?>>Email</option>
        </select>

        <div class="dropdown2">
            <input type="text" name="searchTerm" id="searchTerm" 
                   value="<?php echo htmlspecialchars($searchTerm); ?>" 
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
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=customerID&sortOrder=<?php echo ($sortBy == 'customerID' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Customer ID <?php if ($sortBy == 'customerID') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=name&sortOrder=<?php echo ($sortBy == 'name' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Name <?php if ($sortBy == 'name') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=email&sortOrder=<?php echo ($sortBy == 'email' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Email <?php if ($sortBy == 'email') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $customer) : ?>
            <tr>
                <td><?php echo $customer['customerID']; ?></td>
                <td><?php echo $customer['name']; ?></td>
                <td><?php echo $customer['email']; ?></td>
                <td>
                    <a href="ViewCustomer.php?cid=<?php echo $customer['customerID']; ?>">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1) : ?>
    <div class="pagination">
        <?php if ($currentPage > 1) : ?>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&page=1"><i class="fa-solid fa-angles-left"></i></a>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&page=<?php echo $currentPage - 1; ?>"><i class="fa-solid fa-angle-left"></i></a>
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
                echo "<a href=\"?searchBy=" . urlencode($searchBy) . "&searchTerm=" . urlencode($searchTerm) . "&page=$i\">$i</a>";
            }
        }
        ?>
        
        <?php if ($currentPage < $totalPages) : ?>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&page=<?php echo $currentPage + 1; ?>"><i class="fa-solid fa-angle-right"></i></a>
            <a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&page=<?php echo $totalPages; ?>"><i class="fa-solid fa-angles-right"></i></a>
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
        
        document.getElementById('year').textContent = new Date().getFullYear();
    });
</script>
</body>
</html>