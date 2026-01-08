<!DOCTYPE html>
<html lang="en">

<?php
session_start();

require 'db_connection.php';
if (!isset($_SESSION['userID']) || $_SESSION['jobID'] != 2) {
    header('Location: Login.php');
    exit;
}

function getUsers($searchBy = '', $searchTerm = '', $page = 1, $usersPerPage = 10, $sortBy = '', $sortOrder = 'ASC') {
    $db = new SQLite3('../data/ABC_DBA.db');

    $sql = "SELECT u.*, j.job AS job_name
        FROM users u
        LEFT JOIN jobs j ON u.jobID = j.jobID";

    $whereClause = '';
    if (!empty($searchBy) && !empty($searchTerm)) {
        if ($searchBy == 'fullName') {
            $whereClause = " WHERE (u.fName || ' ' || u.lName) LIKE :searchTerm";
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
    $totalCases = $countResult->fetchArray(SQLITE3_ASSOC)['total'];

    $totalPages = ceil($totalCases / $usersPerPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $usersPerPage;

    $sql = $sql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':limit', $usersPerPage, SQLITE3_INTEGER);
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
            'totalCases' => $totalCases,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ],
    ];
}

function getAllDropdownOptions() {
    $db = new SQLite3('../data/ABC_DBA.db');
    $options = [
        'userID' => [],
        'fName' => [],
        'lName' => [],
        'fullName' => [],
        'email' => [],
        'job_name' => []
    ];

    $result = $db->query("SELECT DISTINCT userID FROM users ORDER BY userID");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['userID'][] = $row['userID'];
    }

    $result = $db->query("SELECT DISTINCT fName FROM users ORDER BY fName");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['fName'][] = $row['fName'];
    }

    $result = $db->query("SELECT DISTINCT lName FROM users ORDER BY lName");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['lName'][] = $row['lName'];
    }

    $result = $db->query("SELECT DISTINCT (fName || ' ' || lName) as full_name FROM users ORDER BY full_name");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['fullName'][] = $row['full_name'];
    }

    $result = $db->query("SELECT DISTINCT email FROM users ORDER BY email");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['email'][] = $row['email'];
    }

    $result = $db->query("SELECT DISTINCT job FROM jobs ORDER BY job");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $options['job_name'][] = $row['job'];
    }

    return $options;
}

$searchBy = isset($_GET['searchBy']) ? $_GET['searchBy'] : '';
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : '';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';

$allDropdownOptions = getAllDropdownOptions();

$result = getUsers($searchBy, $searchTerm, $page, 10, $sortBy, $sortOrder);
$users = $result['data'];
$totalPages = $result['pagination']['totalPages'];
$currentPage = $result['pagination']['currentPage'];
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users</title>
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
    <h2>View All Users</h2>
    <form method="GET" action="" id="searchForm">
        <label for="searchBy">Search By:</label>
        <select name="searchBy" id="searchBy">
            <option value="userID" <?php echo ($searchBy == 'userID') ? 'selected' : ''; ?>>User ID</option>
            <option value="fullName" <?php echo ($searchBy == 'Name') ? 'selected' : ''; ?>>Name</option>
            <option value="email" <?php echo ($searchBy == 'email') ? 'selected' : ''; ?>>Email</option>
            <option value="job_name" <?php echo ($searchBy == 'job_name') ? 'selected' : ''; ?>>Job</option>
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

    <table id="casesTable">
        <thead>
            <tr>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=userID&sortOrder=<?php echo ($sortBy == 'userID' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">User ID <?php if ($sortBy == 'userID') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=fName&sortOrder=<?php echo ($sortBy == 'fName' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">First Name <?php if ($sortBy == 'fName') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th>Middle Name</th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=lName&sortOrder=<?php echo ($sortBy == 'lName' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Last Name <?php if ($sortBy == 'lName') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=email&sortOrder=<?php echo ($sortBy == 'email' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Email <?php if ($sortBy == 'email') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th><a href="?searchBy=<?php echo urlencode($searchBy); ?>&searchTerm=<?php echo urlencode($searchTerm); ?>&sortBy=job_name&sortOrder=<?php echo ($sortBy == 'job_name' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Job <?php if ($sortBy == 'job_name') echo $sortOrder == 'ASC' ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>'; ?></a></th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user) : ?>
            <tr>
                <td><?php echo $user['userID']; ?></td>
                <td><?php echo $user['fName']; ?></td>
                <td><?php echo $user['mName']; ?></td>
                <td><?php echo $user['lName']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td><?php echo $user['job_name']; ?></td>
                <td>
                    <?php if ($user['jobID'] >= 0) : ?>
                        <a href="ViewUser.php?uid=<?php echo $user['userID']; ?>">View</a>
                    <?php else : ?>
                        <a href="ViewUser.php?uid=<?php echo $user['userID']; ?>">View</a>
                    <?php endif; ?>
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