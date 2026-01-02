<?php
session_start();
include "includes/db.php";

$msg = "";

/* =====================
   REGISTER
===================== */
if (isset($_POST['register'])) {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT id FROM admins WHERE email='$email'");

    if ($check->num_rows > 0) {
        $msg = "Email already registered!";
    } else {
        $conn->query("INSERT INTO admins (name,email,password)
                      VALUES ('$name','$email','$pass')");
        $msg = "Registration successful. Now login.";
    }
}

/* =====================
   LOGIN
===================== */
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass  = $_POST['password'];

    $result = $conn->query("SELECT * FROM admins WHERE email='$email'");

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($pass, $row['password'])) {
            $_SESSION['admin_id']   = $row['id'];
            $_SESSION['admin_name'] = $row['name'];

            // ✅ correct path
            header("Location: admin/dashboard.php");
            exit;
        } else {
            $msg = "Wrong password!";
        }
    } else {
        $msg = "Account not found! Please register first.";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Login / Register</title>
    <style>
        body{font-family:Arial;}
        .box{width:300px;margin:50px auto;}
        input,button{width:100%;padding:8px;margin:5px 0;}
        .btns button{width:49%;}
    </style>
</head>
<body>

<div class="box">
    <h2>Admin Panel</h2>

    <!-- BUTTONS -->
    <div class="btns">
        <button onclick="showLogin()">Login</button>
        <button onclick="showRegister()">Register</button>
    </div>

    <!-- LOGIN FORM -->
    <form method="POST" id="loginForm">
        <h3>Login</h3>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button name="login">Login</button>
    </form>

    <!-- REGISTER FORM -->
    <form method="POST" id="registerForm" style="display:none;">
        <h3>Register</h3>
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button name="register">Register</button>
    </form>

    <p style="color:red;"><?php echo $msg; ?></p>
</div>

<script>
function showLogin(){
    document.getElementById("loginForm").style.display="block";
    document.getElementById("registerForm").style.display="none";
}
function showRegister(){
    document.getElementById("loginForm").style.display="none";
    document.getElementById("registerForm").style.display="block";
}
</script>

</body>
</html>
