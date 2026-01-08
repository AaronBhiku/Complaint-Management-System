<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/LoginPage.css">
    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
  />
</head>
<body>
    <header>
        <a href=""><img class="logo" src="../abc.png" alt="ABC Logo"></a>
        <nav>
            <ul class="left-menu">
                <li><a href="../html/Contact.html"><i class="fa-solid fa-envelope"></i> Contact</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <form action="login.php" method="post">
            <h2>Welcome to the login page</h2>

            <div>
                <?php
                    if (isset($_GET["Login_Error"])) {
                        include_once("LoginError.php");
                }?>
            </div>

            <label for="email"><b>Email</b></label>
            <input type="text" id="email" name="email" required>
            <label for="password"><b>Password</b></label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>

        </form>
    </main>
</body>
</html>