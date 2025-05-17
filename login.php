<?php
session_start();
$servername = "localhost";
$dbname = "cybergames";
$username_db = "root";
$password_db = "";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

$identifier = $_POST['username'] ?? '';  // aici pui username sau email
$password = $_POST['password'] ?? '';

if (!$identifier || !$password) {
    header("Location: index.html?error=empty_fields");
    exit();
}

// Caută user după username SAU email
$stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // Nu există user cu username sau email dat
    header("Location: index.html?error=user_not_found");
    exit();
}

$stmt->bind_result($id, $username_db, $password_hash, $role);
$stmt->fetch();

if (!password_verify($password, $password_hash)) {
    header("Location: index.html?error=wrong_password");
    exit();
}

// Login reușit
$_SESSION['user_id'] = $id;
$_SESSION['username'] = $username_db;  // username-ul real din baza de date
$_SESSION['role'] = $role;

header("Location: main.php");
exit();
?>
