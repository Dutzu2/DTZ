<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$dbname = "cybergames";
$username_db = "root";
$password_db = "";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Dacă avem ?user=id, atunci e profil public
$viewing_user_id = isset($_GET['user']) ? (int)$_GET['user'] : $_SESSION['user_id'];
$edit_mode = ($viewing_user_id === $_SESSION['user_id']);

$msg = "";

// Procesare formular doar dacă e profilul propriu
if ($edit_mode && $_SERVER["REQUEST_METHOD"] === "POST") {
    $display_name = $_POST['display_name'];
    $bio = $_POST['bio'];
    $profile_image = $_FILES['profile_image']['name'] ?? '';
    $target = "";

    if ($profile_image) {
        $target = "uploads/" . basename($profile_image);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $target);
    } else {
        // Menținem imaginea actuală
        $result = $conn->query("SELECT profile_image FROM users WHERE id = $viewing_user_id");
        $current = $result->fetch_assoc();
        $profile_image = $current['profile_image'];
    }

    // Update user
    $stmt = $conn->prepare("UPDATE users SET display_name = ?, bio = ?, profile_image = ? WHERE id = ?");
    $stmt->bind_param("sssi", $display_name, $bio, $profile_image, $viewing_user_id);
    $stmt->execute();
    $msg = "Profil actualizat!";

    // Update sesiune
    $_SESSION['profile_image'] = $profile_image;
}

// Obținem datele utilizatorului
$result = $conn->query("SELECT username, display_name, bio, profile_image FROM users WHERE id = $viewing_user_id");
$user = $result->fetch_assoc();
if (!$user) {
    die("Utilizatorul nu există.");
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Profil - <?= htmlspecialchars($user['username']) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header>
    <div class="logo">CyberGames</div>
    <nav>
      <a href="main.php">Home</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <main>
    <h1>Profilul <?= $edit_mode ? 'meu' : htmlspecialchars($user['username']) ?></h1>

    <?php if ($msg): ?>
      <p style="color: #00ff99;"><?= $msg ?></p>
    <?php endif; ?>

    <div class="profile-card">
  <img src="uploads/<?= htmlspecialchars($user['profile_image'] ?? 'default.png') ?>" alt="Imagine profil">
  <div class="display-name"><?= htmlspecialchars($user['display_name'] ?? $user['username']) ?></div>
  <div class="bio"><?= htmlspecialchars($user['bio'] ?? 'Nicio descriere...') ?></div>
</div>


    <?php if ($edit_mode): ?>
      <form method="post" enctype="multipart/form-data">
        <label>Nume afișat:</label>
        <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name']) ?>">

        <label>Bio:</label>
        <textarea name="bio" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>

        <label>Imagine de profil:</label>
        <input type="file" name="profile_image">

        <button type="submit">Salvează</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
