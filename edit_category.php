<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {ESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Get category id from URL
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: categories.php");
    exit();
}

// Fetch the category
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cat) {
    echo "Category not found.";
    exit();
}

$success = $error = '';

// Handle save
if (isset($_POST['save'])) {
    $newName = trim($_POST['new_name']);
    $newIcon = trim($_POST['new_icon']) ?: '🌿';

    if (empty($newName)) {
        $error = "Name cannot be empty.";
    } else {
        $oldName = $cat['name'];
        $upd = $conn->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
        $upd->bind_param("ssi", $newName, $newIcon, $id);
        if ($upd->execute()) {
            // Update products too
            $p = $conn->prepare("UPDATE products SET category = ? WHERE category = ?");
            $p->bind_param("ss", $newName, $oldName);
            $p->execute();
            $p->close();
            $success = "Saved! Redirecting...";
            header("refresh:1;url=categories.php");
        } else {
            $error = "Failed: " . $conn->error;
        }
        $upd->close();
        // Refresh cat data
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $cat = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$currentIcon = !empty($cat['icon']) ? $cat['icon'] : '🌿';
$emojis = ['🌹','🌸','🌷','🌺','🌻','🌼','💐','🌿','🍀','🌱','🌲','🌳',
           '🪷','🪻','🌾','🌵','🍁','🍂','🍃','💜','🤍','❤️','🧡','💛','🩷'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Category – RIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff0f5); min-height: 100vh; display: flex; align-items: flex-start; justify-content: center; padding: 100px 1rem 2rem; }

        .navbar { position: fixed; top: 0; left: 0; width: 100%; background: linear-gradient(90deg, #d63384, #c2185b); padding: 12px 20px; z-index: 1000; display: flex; gap: 6px; flex-wrap: wrap; }
        .navbar a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 0.88rem; transition: background 0.2s; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }

        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 6px 24px rgba(214,51,132,0.15); width: 100%; max-width: 480px; }
        h2 { color: #d63384; margin-bottom: 1.5rem; font-size: 1.3rem; }

        .preview { text-align: center; font-size: 4rem; margin-bottom: 1.2rem; background: #fce8f2; border-radius: 12px; padding: 1rem; }

        label { display: block; font-size: 0.78rem; font-weight: 700; color: #aaa; text-transform: uppercase; margin-bottom: 0.4rem; margin-top: 1rem; }
        input[type=text] { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #f0d0e0; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; color: #333; outline: none; }
        input[type=text]:focus { border-color: #d63384; }

        .emoji-grid { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 0.5rem; }
        .emoji-btn { width: 38px; height: 38px; border: 1.5px solid #f0d0e0; border-radius: 8px; background: #fff9f5; font-size: 1.15rem; cursor: pointer; transition: all 0.15s; padding: 0; }
        .emoji-btn:hover { background: #fce8f2; border-color: #d63384; transform: scale(1.12); }
        .emoji-btn.selected { background: #fce8f2; border-color: #d63384; box-shadow: 0 0 0 3px rgba(214,51,132,0.2); transform: scale(1.1); }

        .actions { display: flex; gap: 0.8rem; margin-top: 1.5rem; }
        .btn-save { flex: 1; padding: 0.85rem; background: #d63384; color: white; border: none; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer; }
        .btn-save:hover { background: #b52b70; }
        .btn-back { flex: 1; padding: 0.85rem; background: #f5f5f5; color: #555; border: 1px solid #ddd; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; }
        .btn-back:hover { background: #eee; }

        .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.88rem; }
        .msg.success { background: #e0fdf4; color: #065f46; border: 1px solid #6ee7b7; }
        .msg.error   { background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
</div>

<div class="card">
    <h2><i class="fas fa-pen" style="margin-right:0.5rem;"></i>Edit Category</h2>

    <?php if ($success): ?><div class="msg success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="msg error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Live icon preview -->
    <div class="preview" id="livePreview"><?= $currentIcon ?></div>

    <form method="POST">
        <label>Category Name</label>
        <input type="text" name="new_name" value="<?= htmlspecialchars($cat['name']) ?>" required>

        <label>Choose Icon</label>
        <input type="hidden" name="new_icon" id="iconInput" value="<?= $currentIcon ?>">
        <div class="emoji-grid">
            <?php foreach ($emojis as $e): ?>
            <button type="button" class="emoji-btn <?= $e === $currentIcon ? 'selected' : '' ?>"
                onclick="selectEmoji('<?= $e ?>', this)">
                <?= $e ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="categories.php" class="btn-back"><i class="fas fa-arrow-left" style="margin-right:0.4rem;"></i> Back</a>
            <button type="submit" name="save" class="btn-save"><i class="fas fa-check" style="margin-right:0.4rem;"></i> Save Changes</button>
        </div>
    </form>
</div>

<script>
function selectEmoji(emoji, btn) {
    document.getElementById('iconInput').value = emoji;
    document.getElementById('livePreview').textContent = emoji;
    document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}
</script>
</body>
</html>