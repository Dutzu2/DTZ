<?php
// Conectare la baza de date
$servername = "localhost";
$dbname = "cybergames";
$username_db = "root";
$password_db = "";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!$username || !$email || !$password || !$confirm_password) {
    die("Completează toate câmpurile.");
}

// Validare email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email invalid.");
}

if ($password !== $confirm_password) {
    die("Parolele nu coincid.");
}

// Verifică dacă există deja utilizator cu username-ul sau email-ul
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    die("Utilizatorul sau email-ul există deja.");
}
$stmt->close();

// Creează contul
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
$insert->bind_param("sss", $username, $email, $password_hash);

if ($insert->execute()) {
    // Redirecționare cu mesaj de succes
    header("Location: login.html?success=1");
    exit();
} else {
    echo "Eroare la înregistrare.";
}

$insert->close();
$conn->close();
?>
