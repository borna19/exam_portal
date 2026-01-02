<?php
include "includes/db.php";
$msg = "";

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
        $msg = "Registration successful. Please login.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Admin Register</h2>

<form method="POST">
    <input type="text" name="name" placeholder="Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <button name="register">Register</button>
</form>

<p><?php echo $msg; ?></p>

<a href="index.php">Already have account? Login</a>

</body>
</html>
