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

$team_id = isset($_GET['team']) ? (int)$_GET['team'] : 0;

?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <title>Echipe - CyberGames</title>
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

<main>
  <h1>Echipe CyberGames</h1>

  <?php if ($team_id === 0): 
    // Lista echipelor
    $result = $conn->query("SELECT id, name, description FROM teams ORDER BY created_at DESC");
    if ($result->num_rows === 0) {
      echo "<p>Nu există echipe create încă.</p>";
    } else {
      echo "<ul class='team-list'>";
      while ($team = $result->fetch_assoc()) {
        echo "<li><a href='teams.php?team=" . $team['id'] . "'>" . htmlspecialchars($team['name']) . "</a></li>";
      }
      echo "</ul>";
    }
  else:
    // Detalii echipă + membri
    $stmt = $conn->prepare("SELECT name, description FROM teams WHERE id = ?");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $team = $stmt->get_result()->fetch_assoc();

    if (!$team) {
      echo "<p>Echipa nu există.</p>";
    } else {
      echo "<div class='team-details'>";
      echo "<h2>" . htmlspecialchars($team['name']) . "</h2>";
      echo "<p>" . nl2br(htmlspecialchars($team['description'])) . "</p>";

      // Membrii echipei
      $stmt = $conn->prepare("SELECT users.id, users.username, users.display_name FROM team_members JOIN users ON team_members.user_id = users.id WHERE team_members.team_id = ?");
      $stmt->bind_param("i", $team_id);
      $stmt->execute();
      $members = $stmt->get_result();

      echo "<h3>Membrii echipei:</h3>";
      if ($members->num_rows === 0) {
        echo "<p>Nu există membri în această echipă.</p>";
      } else {
        echo "<ul class='member-list'>";
        while ($member = $members->fetch_assoc()) {
          $displayName = $member['display_name'] ?: $member['username'];
          echo "<li>" . htmlspecialchars($displayName) . " (<a href='profile.php?user=" . $member['id'] . "'>profil</a>)</li>";
        }
        echo "</ul>";
      }
      echo "</div>";
    }
  endif; ?>

</main>
</body>
</html>
