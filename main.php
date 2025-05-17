<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Utilizator';
$role = $_SESSION['role'] ?? 'user';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>CyberGames - Acasă</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header>
    <div class="logo">CyberGames</div>
    <nav>
      <a href="main.php">Home</a>
      <a href="competitii.php">Competiții</a>
      <a href="teams.php">Echipe</a>
      <?php if ($role === 'admin'): ?>
        <a href="admin_dashboard.php">Admin Dashboard</a>
      <?php endif; ?>
      <a href="profile.php" title="Profil">
  <img src="uploads/<?= $_SESSION['profile_image'] ?? 'default.png' ?>" alt="Profil" style="width: 30px; height: 30px; border-radius: 50%; vertical-align: middle;">
</a>

      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <main>
    <h1>Bine ai venit, <?= htmlspecialchars($username) ?>!</h1>
    <p>Ești logat cu rolul: <strong><?= $role ?></strong></p>
  </main>
</body>
</html>
