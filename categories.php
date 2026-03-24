<?php
session_start();
if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['clientRole'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Auto-create categories table with icon column
$conn->query("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        icon VARCHAR(10) DEFAULT '🌿',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("ALTER TABLE categories ADD COLUMN IF NOT EXISTS icon VARCHAR(10) DEFAULT '🌿'");

// Seed defaults if empty
$count = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
if ($count == 0) {
    $defaults = [
        ['Rose','🌹'],['Lily','🌸'],['Tulip','🌷'],['Orchid','🌺'],
        ['Gerbera','🌼'],['Sunflower','🌻'],['Lavender','💜'],
        ['Carnation','💐'],['Baby','🤍']
    ];
    foreach ($defaults as $d) {
        $s = $conn->prepare("INSERT IGNORE INTO categories (name, icon) VALUES (?, ?)");
        $s->bind_param("ss", $d[0], $d[1]);
        $s->execute();
        $s->close();
    }
}

// ── Add category ──
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $icon = trim($_POST['category_icon']) ?: '🌿';
    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $icon);
        if ($stmt->execute()) {
            $success = "Category '{$name}' added!";
        } else {
            $error = "Category already exists or error.";
        }
        $stmt->close();
    }
}

// ── Edit category ──
if (isset($_POST['edit_category'])) {
    $id      = intval($_POST['edit_id']);
    $newName = trim($_POST['new_name']);
    $newIcon = trim($_POST['new_icon']) ?: '🌿';

    if (empty($newName)) {
        $error = "Category name cannot be empty.";
    } else {
        $old = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $old->bind_param("i", $id);
        $old->execute();
        $oldRow = $old->get_result()->fetch_assoc();
        $old->close();

        if ($oldRow) {
            $oldName = $oldRow['name'];
            $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newName, $newIcon, $id);
            if ($stmt->execute()) {
                $success = "Category updated successfully!";
            } else {
                $error = "Update failed: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// ── Delete category ──
if (isset($_POST['delete_category'])) {
    $id = intval($_POST['delete_id']);

    // Check if any products use this category
    $check = $conn->prepare("SELECT COUNT(*) as c FROM products WHERE category_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();

    if ($result['c'] > 0) {
        $error = "Cannot delete. Products are using this category.";
    } else {
        $delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $delete->bind_param("i", $id);
        if ($delete->execute()) {
            $success = "Category deleted successfully.";
        } else {
            $error = "Delete failed: " . $conn->error;
        }
        $delete->close();
    }
}

// Fetch all categories
$categories = $conn->query("
    SELECT c.id, c.name, c.icon, c.created_at, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id ORDER BY c.name ASC
")->fetch_all(MYSQLI_ASSOC);

$emojis = ['🌹','🌸','🌷','🌺','🌻','🌼','💐','🌿','🍀','🌱','🌲','🌳',
           '🪷','🪻','🌾','🌵','🍁','🍂','🍃','💜','🤍','❤️','🧡','💛','🩷'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories – RIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff0f5); min-height: 100vh; padding-top: 70px; }

        .navbar { position: fixed; top: 0; left: 0; width: 100%; background: linear-gradient(90deg, #d63384, #c2185b); padding: 12px 20px; z-index: 1000; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .navbar a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 0.88rem; font-weight: 500; transition: background 0.2s; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .navbar a.active { background: white; color: #d63384; font-weight: 700; }
        .navbar a.logout { margin-left: auto; background: rgba(255,255,255,0.15); }

        .container { width: 92%; max-width: 960px; margin: 30px auto; display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start; }
        @media (max-width: 720px) { .container { grid-template-columns: 1fr; } }

        .panel { background: white; border-radius: 16px; padding: 1.8rem; box-shadow: 0 6px 24px rgba(214,51,132,0.12); }

        h2 { color: #d63384; font-size: 1.2rem; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.5rem; padding-bottom: 0.9rem; border-bottom: 2px solid #fce8f2; }

        .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.88rem; font-weight: 500; }
        .msg.success { background: #e0fdf4; color: #065f46; border: 1px solid #6ee7b7; }
        .msg.error   { background: #ffe0f0; color: #c0392b; border: 1px solid #f17dda; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 700; color: #888; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .form-group input[type=text] { width: 100%; padding: 0.72rem 1rem; border: 1.5px solid #f0d0e0; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.92rem; color: #333; background: #fff9f5; outline: none; transition: border-color 0.2s; }
        .form-group input[type=text]:focus { border-color: #d63384; background: #fff; }

        .emoji-grid, .edit-emoji-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 0.5rem; }
        .emoji-btn { width: 36px; height: 36px; border: 1.5px solid #f0d0e0; border-radius: 8px; background: #fff9f5; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; padding: 0; }
        .emoji-btn:hover, .edit-emoji-btn:hover { background: #fce8f2; border-color: #d63384; transform: scale(1.15); }
        .emoji-btn.selected, .edit-emoji-btn.selected { background: #fce8f2; border-color: #d63384; box-shadow: 0 0 0 2px rgba(214,51,132,0.25); }
        .edit-emoji-btn { width: 32px; height: 32px; border: 1.5px solid #f0d0e0; border-radius: 7px; background: #fff; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; padding: 0; }

        .btn-add { width: 100%; padding: 0.85rem; background: #d63384; color: white; border: none; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 6px 18px rgba(214,51,132,0.25); display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
        .btn-add:hover { background: #b52b70; transform: translateY(-1px); }

        .cat-item { border: 1.5px solid #f0d0e0; border-radius: 12px; margin-bottom: 0.7rem; overflow: hidden; background: #fff9f5; transition: border-color 0.2s; }
        .cat-item:hover { border-color: #d63384; }

        .cat-top { display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; gap: 0.8rem; }
        .cat-left { display: flex; align-items: center; gap: 0.8rem; }
        .cat-icon { width: 38px; height: 38px; background: #fce8f2; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .cat-name { font-weight: 600; font-size: 0.92rem; color: #333; }
        .cat-count { font-size: 0.75rem; color: #aaa; margin-top: 2px; }
        .badge { background: #fce8f2; color: #d63384; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-left: 5px; }
        .badge.zero { background: #f0f0f0; color: #aaa; }

        .cat-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
        .btn-edit { padding: 0.38rem 0.85rem; background: #fff; color: #d63384; border: 1.5px solid #f0d0e0; border-radius: 8px; font-family: Poppins, sans-serif; font-size: 0.78rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: all 0.2s; text-decoration: none; }
        .btn-edit:hover { background: #fce8f2; border-color: #d63384; }
        .btn-edit.active { background: #fce8f2; border-color: #d63384; }
        .btn-delete { padding: 0.38rem 0.85rem; background: #fff; color: #ccc; border: 1.5px solid #f0d0e0; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.78rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: all 0.2s; }
        .btn-delete:hover:not(:disabled) { background: #ffe0f0; border-color: #f17dda; color: #c0392b; }
        .btn-delete:disabled { opacity: 0.35; cursor: not-allowed; }

        .edit-panel { display: none; padding: 1rem; border-top: 1.5px dashed #f0d0e0; background: #fff; }
        .edit-panel.open { display: block; }
        .edit-grid { display: grid; grid-template-columns: 1fr auto; gap: 0.8rem; align-items: start; margin-bottom: 0.7rem; }
        .edit-panel label { font-size: 0.75rem; font-weight: 700; color: #aaa; display: block; margin-bottom: 0.3rem; text-transform: uppercase; }
        .edit-panel input[type=text] { width: 100%; padding: 0.6rem 0.9rem; border: 1.5px solid #f0d0e0; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .edit-panel input[type=text]:focus { border-color: #d63384; }
        .icon-preview { width: 54px; height: 54px; background: #fce8f2; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; border: 1.5px solid #f0d0e0; flex-shrink: 0; }
        .edit-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.9rem; }
        .btn-save { padding: 0.48rem 1.1rem; background: #d63384; color: white; border: none; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-save:hover { background: #b52b70; }
        .btn-cancel { padding: 0.48rem 1rem; background: #f5f5f5; color: #555; border: 1px solid #ddd; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.82rem; cursor: pointer; }
        .btn-cancel:hover { background: #eee; }

        .empty { text-align: center; padding: 2rem; color: #ccc; }
        .empty i { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
        .hint { margin-top: 1.2rem; padding: 0.9rem 1rem; background: #fff9f5; border-radius: 10px; font-size: 0.78rem; color: #aaa; border: 1px dashed #f0d0e0; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="categories.php" class="active">Categories</a>
    <a href="add_product.php">Add Flower</a>
    <a href="view_products.php">View Flowers</a>
    <a href="sales.php">Sales</a>
    <a href="analytics.php">Analytics</a>
    <a href="view_sales.php">View Sales</a>
    <a href="messages.php">Messages</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">

    <!-- LEFT: Add form -->
    <div class="panel">
        <h2><i class="fas fa-plus-circle"></i> Add Category</h2>

        <?php if (isset($success)): ?><div class="msg success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if (isset($error)):   ?><div class="msg error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="category_name" placeholder="e.g. Lotus, Marigold..." required>
            </div>
            <div class="form-group">
                <label>Choose Icon</label>
                <input type="text" name="category_icon" id="addEmojiInput" placeholder="Click an emoji below..." maxlength="4" readonly>
                <div class="emoji-grid">
                    <?php foreach ($emojis as $e): ?>
                    <button type="button" class="emoji-btn" onclick="pickAddEmoji('<?= $e ?>', this)"><?= $e ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" name="add_category" class="btn-add">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </form>

        <div class="hint">
            <i class="fas fa-info-circle" style="color:#d63384; margin-right:0.3rem;"></i>
            Categories with products cannot be deleted. Reassign them first.
        </div>
    </div>

    <!-- RIGHT: Category list -->
    <div class="panel">
        <h2>
            <i class="fas fa-layer-group"></i> All Categories
            <span style="margin-left:auto; font-size:0.82rem; font-weight:400; color:#aaa;"><?= count($categories) ?> total</span>
        </h2>

        <?php if (empty($categories)): ?>
            <div class="empty"><i class="fas fa-folder-open"></i><p>No categories yet.</p></div>
        <?php else: ?>
            <?php foreach ($categories as $cat):
                $icon = !empty($cat['icon']) ? $cat['icon'] : '🌿';
            ?>
            <div class="cat-item" id="catrow-<?= $cat['id'] ?>">
                <div class="cat-top">
                    <div class="cat-left">
                        <div class="cat-icon" id="preview-<?= $cat['id'] ?>"><?= $icon ?></div>
                        <div>
                            <div class="cat-name">
                                <?= htmlspecialchars($cat['name']) ?>
                                <span class="badge <?= $cat['product_count'] == 0 ? 'zero' : '' ?>">
                                    <?= $cat['product_count'] ?> product<?= $cat['product_count'] != 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <div class="cat-count">Added <?= date('d M Y', strtotime($cat['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="cat-actions">
                        <a href="edit_category.php?id=<?= $cat['id'] ?>" class="btn-edit">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        <form method="POST" style="display:inline;"
                            onsubmit="return confirm('Delete \'<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>\'?')">
                            <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                            <button type="submit" name="delete_category" class="btn-delete"
                                <?= $cat['product_count'] > 0 ? 'disabled title="Has products"' : '' ?>>
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function pickAddEmoji(emoji, btn) {
    document.getElementById('addEmojiInput').value = emoji;
    document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}

function toggleEdit(id, name, icon) {
    var panel  = document.getElementById('editpanel-' + id);
    var btn    = document.getElementById('editbtn-' + id);
    var isOpen = panel.classList.contains('open');

    document.querySelectorAll('.edit-panel.open').forEach(p => p.classList.remove('open'));
    document.querySelectorAll('.btn-edit.active').forEach(b => b.classList.remove('active'));

    if (!isOpen) {
        panel.classList.add('open');
        btn.classList.add('active');
        document.getElementById('catrow-' + id).scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function pickEditEmoji(id, emoji, btn) {
    document.getElementById('editicon-' + id).value = emoji;
    document.getElementById('iconpreview-' + id).textContent = emoji;
    document.getElementById('emojigrid-' + id).querySelectorAll('.edit-emoji-btn')
        .forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}
</script>
</body>
</html>
