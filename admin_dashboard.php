<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' && $_SESSION['is_owner'] ) {
  header("Location: login.php");
  exit();
}

$servername = "localhost";
$dbname = "cybergames";
$username_db = "root";
$password_db = "";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
  die("Conexiunea a eșuat: " . $conn->connect_error);
}

$selected_team_id = 0;
if (isset($_POST['team_id'])) {
    $selected_team_id = (int)$_POST['team_id'];
} elseif (isset($_GET['team_id'])) {
    $selected_team_id = (int)$_GET['team_id'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];

    if (isset($_POST['add_to_team']) && $selected_team_id > 0) {
        $stmt = $conn->prepare("INSERT IGNORE INTO team_members (team_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $selected_team_id, $userId);
        $stmt->execute();
        echo "<p style='color:green;'>User adăugat în echipa selectată.</p>";
    }

    if (isset($_POST['remove_from_team']) && $selected_team_id > 0) {
        $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $selected_team_id, $userId);
        $stmt->execute();
        echo "<p style='color:orange;'>User scos din echipa selectată.</p>";
    }

    if (isset($_POST['make_admin'])) {
        $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo "<p style='color:green;'>User făcut admin.</p>";
    }

    if (isset($_POST['remove_admin'])) {
        $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo "<p style='color:orange;'>User scos din admini.</p>";
    }

        header("Location: login.php");
    exit();

}


$selected_user_id = isset($_GET['selected_user']) ? (int)$_GET['selected_user'] : 0;
$selected_user = null;

if ($selected_user_id > 0) {
$stmt = $conn->prepare("SELECT id, username, display_name, role, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_user = $result->fetch_assoc();
    $stmt->close();
}


if ($selected_user): ?>
<div class="center-container" style="  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh; /* face containerul cât înălțimea ecranului */
  background-color: #121212; /* opțional */">
  <div class="user-card" style="
    border: 1px solid #444;
    padding: 20px;
    margin-top: 30px;
    max-width: 400px;
    background-color: #1e1e1e;
    color: #e0e0e0;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);

  ">
    <h3 style="margin-bottom: 10px;">
      <?= htmlspecialchars($selected_user['username']) ?>
      <span style="font-weight: normal; color: #aaa;">(<?= htmlspecialchars($selected_user['display_name']) ?>)</span>
    </h3>
    <p><strong>Email:</strong> <?= htmlspecialchars($selected_user['email']) ?></p>
    <p><strong>Rol:</strong> <?= $selected_user['role'] === 'admin' ? 'Admin' : 'Utilizator normal' ?></p>
    <p><strong>ID:</strong> <?= $selected_user['id'] ?></p>

    <form method="post" action="" style="margin-top: 15px;">
      <input type="hidden" name="user_id" value="<?= $selected_user['id'] ?>" />
      <input type="hidden" name="team_id" value="<?= $selected_team_id ?>" />

      <?php if ($selected_team_id > 0): ?>
        <button type="submit" name="add_to_team" style="margin-right: 10px;">Adaugă în echipă</button>
        <button type="submit" name="remove_from_team">Scoate din echipă</button>
      <?php else: ?>
        <p style="color:#999; font-style: italic;">Selectează o echipă pentru a adăuga sau scoate membri.</p>
      <?php endif; ?>

      <?php if ($selected_user['role'] === 'admin'): ?>
        <button type="submit" name="remove_admin" style="margin-top: 10px; background-color: #bb4444;">Scoate admin</button>
      <?php else: ?>
        <button type="submit" name="make_admin" style="margin-top: 10px;">Fă admin</button>
      <?php endif; ?>
    </form>
  </div>
      </div>
<?php endif;


if (isset($_POST['add_competition'])) {
    $name = trim($_POST['comp_name'] ?? '');
    $description = trim($_POST['comp_description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (!$name || !$start_date || !$end_date) {
        $_SESSION['error_message'] = "Toate câmpurile trebuie completate.";
    } else {
        $stmt = $conn->prepare("INSERT INTO competitions (name, description, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $description, $start_date, $end_date);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Competiția a fost adăugată cu succes!";
        } else {
            $_SESSION['error_message'] = "Eroare la adăugarea competiției: " . $stmt->error;
        }

        $stmt->close();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Obține lista competițiilor existente
$competitions = [];
$result = $conn->query("SELECT * FROM competitions ORDER BY start_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $competitions[] = $row;
    }
    $result->free();
}


$message = '';

// 1. Căutare useri
$search_users = [];
if (isset($_GET['search_user']) && trim($_GET['search_user']) !== '') {
    $search_term = "%" . $conn->real_escape_string(trim($_GET['search_user'])) . "%";
    $stmt = $conn->prepare("SELECT id, username, display_name FROM users WHERE username LIKE ? OR CAST(id AS CHAR) LIKE ? LIMIT 20");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}





// 2. Creare echipă
if (isset($_POST['create_team'])) {
   if (!$team_name) {
    $_SESSION['error_message'] = "Te rugăm să introduci un nume pentru echipă.";
    header("Location: admin_dashboard.php");
    exit();
}

$stmt = $conn->prepare("INSERT INTO teams (name) VALUES (?)");
$stmt->bind_param("s", $team_name);

try {
    $stmt->execute();
    $_SESSION['success_message'] = "Echipa a fost creată cu succes!";
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        $_SESSION['error_message'] = "Numele echipei este deja folosit, boss!";
    } else {
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
    }
}
header("Location: admin_dashboard.php");
exit();
}




// Adaugă membru
if (isset($_POST['add_member']) && $selected_team_id > 0) {
    $user_id_to_add = (int)($_POST['user_to_add'] ?? 0);
    if ($user_id_to_add > 0) {
        // Verific dacă deja e membru
        $stmt = $conn->prepare("SELECT id FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $selected_team_id, $user_id_to_add);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $selected_team_id, $user_id_to_add);
            if ($stmt->execute()) {
                $message = "Membru adăugat cu succes!";
            } else {
                $message = "Eroare la adăugarea membrului: " . $stmt->error;
            }
        } else {
            $message = "Utilizatorul este deja membru al acestei echipe.";
        }
        $stmt->close();
    }
}

// Elimină membru
if (isset($_POST['remove_member']) && $selected_team_id > 0) {
    $user_id_to_remove = (int)($_POST['user_to_remove'] ?? 0);
    if ($user_id_to_remove > 0) {
        $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $selected_team_id, $user_id_to_remove);
        if ($stmt->execute()) {
            $message = "Membru eliminat cu succes!";
        } else {
            $message = "Eroare la eliminarea membrului: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Listez toate echipele pentru selector
$teams = [];
$result = $conn->query("SELECT id, name FROM teams ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}
$result->close();

// Membrii echipei selectate
$team_members = [];
if ($selected_team_id > 0) {
    $stmt = $conn->prepare("SELECT users.id, users.username, users.display_name FROM team_members JOIN users ON team_members.user_id = users.id WHERE team_members.team_id = ?");
    $stmt->bind_param("i", $selected_team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $team_members[] = $row;
    }
    $stmt->close();
}

// Utilizatori disponibili pentru adăugare (nu sunt membri deja)
$available_users = [];
if ($selected_team_id > 0) {
    $stmt = $conn->prepare("SELECT id, username, display_name FROM users WHERE id NOT IN (SELECT user_id FROM team_members WHERE team_id = ?) ORDER BY username ASC");
    $stmt->bind_param("i", $selected_team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $available_users[] = $row;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - CyberGames</title>
    <link rel="stylesheet" href="assets/style.css" />
    <style>
      form { margin-bottom: 20px; }
      input[type=text], textarea, select {
        width: 100%; padding: 8px; margin: 6px 0 12px 0; border-radius: 6px; border: 1px solid #444;
        background: #222; color: #0f0;
      }
      button {
        background-color: #00ff99; border: none; padding: 10px 20px; color: #111; font-weight: 700;
        border-radius: 8px; cursor: pointer;
      }
      button:hover {
        background-color: #00cc77;
      }
      .message {
        margin: 15px 0;
        padding: 10px;
        background: #003300;
        color: #0f0;
        border-radius: 10px;
        font-weight: 700;
      }
      .container {
        max-width: 900px;
        margin: 40px auto;
        background: #1e1e2f;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 0 20px #00ff99aa;
        color: #ccc;
      }
      h2, h3 {
        color: #00ff99;
      }
      label {
        font-weight: 600;
      }
      .user-list, .member-list {
        list-style: none;
        padding-left: 0;
      }
      .user-list li, .member-list li {
        padding: 6px 10px;
        border-bottom: 1px solid #00ff9977;
      }
      .user-list li:last-child, .member-list li:last-child {
        border-bottom: none;
      }
    </style>
</head>
<body>
<div class="container">

<h1>Admin Dashboard - CyberGames</h1>
<a href="main.php" style="display:inline-block; margin-bottom: 20px; padding: 10px 15px; background: #00ff99; color: #111; border-radius: 8px; text-decoration: none; font-weight: 700;">
  &larr; Înapoi la Main
</a>

<?php if ($message): ?>
  <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- 1. Căutare useri -->
<section>
  <h2>Caută useri</h2>
  <form method="get" action="">
    <input type="text" name="search_user" placeholder="Caută după username sau ID" value="<?= isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : '' ?>" />
    <button type="submit">Caută</button>
  </form>
<?php if ($search_users): ?>
  <ul class="user-list">
  <?php foreach ($search_users as $user): ?>
    <li>
      <a href="?selected_user=<?= $user['id'] ?>">
        <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['display_name']) ?>)
      </a>
    </li>
  <?php endforeach; ?>
</ul>


<?php elseif (isset($_GET['search_user'])): ?>
  <p>Niciun user găsit.</p>
<?php endif; ?>

</section>


<hr>

<!-- 2. Creare echipă -->
<section>
  <h2>Creare echipă nouă</h2>
  <form method="post" action="">
    <label for="team_name">Nume echipă:</label>
    <input type="text" name="team_name" id="team_name" required />
    <label for="team_description">Descriere echipă:</label>
    <textarea name="team_description" id="team_description" rows="3"></textarea>
    <button type="submit" name="create_team">Creează echipă</button>
  </form>
</section>

<hr>

<!-- 3. Gestionare membri echipă -->
<section>
  <h2>Gestionare membri echipă</h2>
  <form method="get" action="">
    <label for="team_id">Selectează echipa:</label>
    <select name="team_id" id="team_id" onchange="this.form.submit()">
      <option value="">-- Selectează echipa --</option>
      <?php foreach ($teams as $team): ?>
        <option value="<?= $team['id'] ?>" <?= ($team['id'] == $selected_team_id) ? 'selected' : '' ?>>
          <?= htmlspecialchars($team['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($selected_team_id > 0): ?>

  <h3>Membri echipă "<?= htmlspecialchars(current(array_filter($teams, fn($t) => $t['id'] == $selected_team_id))['name']) ?>"</h3>

  <?php if ($team_members): ?>
    <ul class="member-list">
      <?php foreach ($team_members as $member): ?>
        <li>
          <?= htmlspecialchars($member['username']) ?> (<?= htmlspecialchars($member['display_name']) ?>)
          <form method="post" action="">
            <input type="hidden" name="user_to_remove" value="<?= $member['id'] ?>" />
            <button type="submit" name="remove_member" onclick="return confirm('Sigur dorești să elimini acest membru?')">Elimină</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>Nu există membri în această echipă.</p>
  <?php endif; ?>

  <h3>Adaugă membru nou</h3>
  <?php if ($available_users): ?>
  <form method="post" action="">
    <select name="user_to_add" required>
      <option value="">-- Selectează user --</option>
      <?php foreach ($available_users as $user): ?>
        <option value="<?= $user['id'] ?>">
          <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['display_name']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" name="add_member">Adaugă membru</button>
  </form>
  <?php else: ?>
    <p>Toți utilizatorii sunt deja membri ai acestei echipe.</p>
  <?php endif; ?>

  <?php endif; ?>
<?php


if (isset($_POST['add_competition'])) {
    $name = $_POST['comp_name'] ?? '';
    $desc = $_POST['comp_desc'] ?? '';
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';

    $stmt = $conn->prepare("INSERT INTO competitions (name, description, start_date, end_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $desc, $start, $end);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Ștergere competiție
if (isset($_POST['delete_competition'])) {
    $id = (int)($_POST['comp_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM competitions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Salvare modificări competiție
if (isset($_POST['save_competition'])) {
    $id = (int)($_POST['comp_id'] ?? 0);
    $name = $_POST['comp_name'] ?? '';
    $desc = $_POST['comp_desc'] ?? '';
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';

    $stmt = $conn->prepare("UPDATE competitions SET name = ?, description = ?, start_date = ?, end_date = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $desc, $start, $end, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?edit_competition_id=$id");
    exit;
}

// Preluare competiții pentru listă
$competitions = [];
$res = $conn->query("SELECT * FROM competitions ORDER BY start_date DESC");
while ($row = $res->fetch_assoc()) {
    $competitions[] = $row;
}

// Preluare competiție pentru editare
$edit_competition = null;
if (isset($_GET['edit_competition_id'])) {
    $id = (int)$_GET['edit_competition_id'];
    $stmt = $conn->prepare("SELECT * FROM competitions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_competition = $res->fetch_assoc();
    $stmt->close();
}
?>

<section style="margin-top: 50px;">
  <h2>Adaugă competiție nouă</h2>
  <form method="post" action="">
    <input type="text" name="comp_name" placeholder="Nume competiție" required style="width:100%; margin-bottom:10px;"><br>
    <textarea name="comp_desc" placeholder="Descriere" required style="width:100%; margin-bottom:10px;"></textarea><br>
    <label>Data început:</label>
    <input type="date" name="start_date" required><br><br>
    <label>Data sfârșit:</label>
    <input type="date" name="end_date" required><br><br>
    <button type="submit" name="add_competition">Adaugă competiție</button>
  </form>
</section>

<section style="margin-top: 50px;">
  <h2>Competiții existente</h2>
  <?php if (!empty($competitions)): ?>
    <ul>
      <?php foreach ($competitions as $comp): ?>
        <li>
          <strong><?= htmlspecialchars($comp['name']) ?></strong>
          (<?= htmlspecialchars($comp['start_date']) ?> - <?= htmlspecialchars($comp['end_date']) ?>)
          <br><small><?= htmlspecialchars($comp['description']) ?></small><br>
          <a href="?edit_competition_id=<?= $comp['id'] ?>" style="color: #3498db;">Editează</a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>Nu există competiții înregistrate momentan.</p>
  <?php endif; ?>
</section>

<?php if ($edit_competition): ?>
  <section style="margin-top: 50px;">
    <h2>Editează competiție: <?= htmlspecialchars($edit_competition['name']) ?></h2>
    <form method="post" action="" onsubmit="return confirm('Ești sigur că vrei să salvezi modificările?');">
      <input type="hidden" name="comp_id" value="<?= $edit_competition['id'] ?>">
      <input type="text" name="comp_name" placeholder="Nume competiție" value="<?= htmlspecialchars($edit_competition['name']) ?>" required style="width:100%; margin-bottom:10px;"><br>
      <textarea name="comp_desc" placeholder="Descriere" required style="width:100%; margin-bottom:10px;"><?= htmlspecialchars($edit_competition['description']) ?></textarea><br>
      <label>Data început:</label>
      <input type="date" name="start_date" value="<?= htmlspecialchars($edit_competition['start_date']) ?>" required><br><br>
      <label>Data sfârșit:</label>
      <input type="date" name="end_date" value="<?= htmlspecialchars($edit_competition['end_date']) ?>" required><br><br>
      <button type="submit" name="save_competition" style="background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Salvează modificările</button>
    </form>

    <form method="post" action="" style="margin-top: 15px;" onsubmit="return confirm('Ești sigur că vrei să ștergi această competiție? Această acțiune este ireversibilă!');">
      <input type="hidden" name="comp_id" value="<?= $edit_competition['id'] ?>">
      <button type="submit" name="delete_competition" style="background-color: #e74c3c; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">
        Șterge competiție
      </button>
    </form>
  </section>
<?php endif; ?>

<div id="toast" style="
  visibility: hidden;
  min-width: 250px;
  margin-left: -125px;
  background-color: #f44336;
  color: white;
  text-align: center;
  border-radius: 4px;
  padding: 16px;
  position: fixed;
  z-index: 9999;
  left: 50%;
  bottom: 30px;
  font-size: 17px;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
">
</div>
<script>
function showToast(message) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.style.visibility = 'visible';
  toast.style.opacity = '1';
  setTimeout(() => {
    toast.style.visibility = 'hidden';
    toast.style.opacity = '0';
  }, 4000);
}
</script>
<script>
<?php if (!empty($_SESSION['error_message'])): ?>
  showToast("<?= addslashes($_SESSION['error_message']) ?>");
  <?php unset($_SESSION['error_message']); ?>
<?php elseif (!empty($_SESSION['success_message'])): ?>
  showToast("<?= addslashes($_SESSION['success_message']) ?>");
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
</script>
</body>
</html>
