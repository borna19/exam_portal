<?php 
session_start();
include "includes/db.php";
$msg = "";

/* =====================
   LOGIN
===================== */
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $result = $conn->query("SELECT * FROM admins WHERE email='$email' LIMIT 1");

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($pass, $row['password'])) {

            // ✅ SET SESSION
            $_SESSION['admin_id']   = $row['id'];
            $_SESSION['admin_name'] = $row['name'];

            // ✅ REDIRECT (NO OUTPUT BEFORE THIS)
            header("Location: admin/dashboard.php");
            exit;

        } else {
            $msg = "Wrong password!";
        }
    } else {
        $msg = "Account not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: 'Segoe UI', sans-serif;
}

body{
    background:#f4f6f9;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.auth-box{
    width:380px;
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

.auth-box h2{
    text-align:center;
    margin-bottom:20px;
    color:#333;
}

/* Tabs */
.tabs{
    display:flex;
    margin-bottom:20px;
    border-bottom:2px solid #eee;
}

.tabs button{
    flex:1;
    padding:10px;
    border:none;
    background:none;
    font-size:15px;
    cursor:pointer;
    color:#777;
}

.tabs button.active{
    color:#007bff;
    border-bottom:3px solid #007bff;
    font-weight:600;
}

/* Forms */
form{
    display:none;
}

form.active{
    display:block;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:12px;
    border:1px solid #ccc;
    border-radius:6px;
    outline:none;
    font-size:14px;
}

input:focus{
    border-color:#007bff;
}

/* Button */
.submit-btn{
    width:100%;
    padding:12px;
    background:#007bff;
    color:#fff;
    border:none;
    border-radius:6px;
    font-size:15px;
    cursor:pointer;
}

.submit-btn:hover{
    background:#0056b3;
}

.msg{
    margin-top:12px;
    text-align:center;
    color:red;
    font-size:14px;
}
</style>
</head>

<body>

<div class="auth-box">
    <h2>Admin Panel</h2>

    <div class="tabs">
        <button id="loginTab" class="active" onclick="showLogin()">Login</button>
        <button id="registerTab" onclick="showRegister()">Register</button>
    </div>

    <!-- LOGIN -->
    <form method="POST" id="loginForm" class="active">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="submit-btn" name="login">Login</button>
    </form>

    <!-- REGISTER -->
    <form method="POST" id="registerForm">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="submit-btn" name="register">Register</button>
    </form>
<?php if(!empty($msg)): ?>
    <div class="msg"><?php echo $msg; ?></div>
<?php endif; ?>

</div>

<script>
function showLogin(){
    loginForm.classList.add("active");
    registerForm.classList.remove("active");
    loginTab.classList.add("active");
    registerTab.classList.remove("active");
}

function showRegister(){
    registerForm.classList.add("active");
    loginForm.classList.remove("active");
    registerTab.classList.add("active");
    loginTab.classList.remove("active");
}
</script>

</body>
</html>
