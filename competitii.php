<?php
session_start();

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "cybergames";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Citire competiții din baza de date
$result = $conn->query("SELECT id, name, description, start_date, end_date, is_open FROM competitions");

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <title>Competiții - CyberGames</title>
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body>
<header>
  <div class="logo">CyberGames</div>
  <nav>
    <a href="main.php">Home</a>
    <a href="profile.php">Profil</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="admin_dashboard.php">Dashboard</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
  </nav>
</header>
  <style>
    .container {
      max-width: 900px;
      margin: auto;
      padding: 40px 20px;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 2.5em;
    }
    .competition-card {
      background-color:rgb(0, 89, 70);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0, 114, 135, 0.4);
    }
    .competition-card h2 {
      margin: 0 0 10px;
    }
    .competition-card p {
      margin: 5px 0;
    }
    .competition-card form button {
      margin-top: 10px;
      background-color: #3498db;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .competition-card form button:hover {
      background-color: #2980b9;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Competiții CyberGames</h1>
    <?php if ($result && $result->num_rows > 0): ?>
  <?php while($row = $result->fetch_assoc()): ?>
    <div class="competition-card">
      <h2><?= htmlspecialchars($row['name']) ?></h2>
      <p>Perioadă Înscrieri: <?= htmlspecialchars($row['start_date']) ?> - <?= htmlspecialchars($row['end_date']) ?></p>
      <p>Status: <?= $row['is_open'] ? 'Înscrieri deschise' : 'Înscrieri închise' ?></p>
      <form method="post" action="competition_details.php">
        <input type="hidden" name="competition_id" value="<?= $row['id'] ?>">
        <button type="submit" <?= !$row['is_open'] ? 'disabled' : '' ?>>
          <?= $row['is_open'] ? 'Vezi detalii / Înscrie-te' : 'Înscrierile s-au încheiat' ?>
        </button>
      </form>
    </div>
  <?php endwhile; ?>
<?php else: ?>
  <p style="text-align:center;">Momentan nu există competiții.</p>
<?php endif; ?>

  </div>
</body>
</html>
<?php $conn->close(); ?>
