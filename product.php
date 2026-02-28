<?php
$categoryColors = [
    "Rose"      => "#ff4d6d",
    "Lily"      => "#c77dff",
    "Tulip"     => "#ff85a1",
    "Orchid"    => "#9d4edd",
    "Sunflower" => "#ffbe0b",
    "Lavender"  => "#b565f5",
    "Gerbera"   => "#ff7b00",
    "Carnation" => "#ff99c8",
    "Lotus"     => "#48cae4",
    "Baby"      => "#adb5bd",
];
$catName     = $row['category_name'] ?? $row['category'] ?? 'Unknown';
$badgeColor  = $categoryColors[$catName] ?? "#ffb3d9";
$lowStock    = ($row['quantity'] > 0 && $row['quantity'] <= 5);
$outOfStock  = ($row['quantity'] <= 0);
?>
<div class="box" data-category="<?= htmlspecialchars($catName) ?>">
    <div style="position:relative; overflow:hidden;">
        <img src="uploads/<?= htmlspecialchars($row['image']) ?>"
             alt="<?= htmlspecialchars($row['name']) ?>"
             style="<?= $outOfStock ? 'filter:grayscale(60%) opacity(0.7);' : '' ?>">

        <?php if($outOfStock): ?>
        <span style="
            position:absolute; top:0; left:0; right:0; bottom:0;
            background:rgba(0,0,0,0.35);
            display:flex; align-items:center; justify-content:center;
        ">
            <span style="
                background:#555; color:#fff;
                font-size:0.8rem; font-weight:700;
                padding:6px 16px; border-radius:20px;
                letter-spacing:0.08em; text-transform:uppercase;
            ">Out of Stock</span>
        </span>
        <?php elseif($lowStock): ?>
        <span style="
            position:absolute; top:10px; left:10px;
            background:#ff4d4d; color:#fff;
            font-size:0.72rem; font-weight:700;
            padding:3px 10px; border-radius:20px;
            letter-spacing:0.05em; text-transform:uppercase;
            box-shadow: 0 2px 8px rgba(255,77,77,0.4);
        ">Only <?= $row['quantity'] ?> left!</span>
        <?php endif; ?>
    </div>

    <h3><?= htmlspecialchars($row['name']) ?></h3>

    <p style="padding: 0.2rem 1.2rem 0.4rem;">
        <span style="
            background: <?= $badgeColor ?>;
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        "><?= htmlspecialchars($row['category_name'] ?? '') ?></span>
    </p>

    <div class="price">Rs <?= number_format($row['price'], 2) ?></div>

    <p style="padding: 0 1.2rem 0.5rem; font-size:0.8rem; color:#9b7a8a;">
        <i class="fas fa-cubes" style="margin-right:4px; color:#f17dda;"></i>
        <?= $outOfStock ? '<span style="color:#e74c3c;">Out of stock</span>' : $row['quantity'] . ' in stock' ?>
    </p>

    <div class="icons">
        <?php if($outOfStock): ?>
        <a href="#" class="cart-btn" style="
            background:#ccc; cursor:not-allowed; pointer-events:none; opacity:0.6;
        ">
            <i class="fas fa-ban"></i> Unavailable
        </a>
        <?php else: ?>
        <a href="#" class="cart-btn"
           data-name="<?= htmlspecialchars($row['name']) ?>"
           data-price="<?= $row['price'] ?>"
           data-image="<?= htmlspecialchars($row['image']) ?>"
           data-stock="<?= intval($row['quantity']) ?>">
            <i class="fas fa-shopping-cart"></i> Add to Cart
        </a>
        <?php endif; ?>
    </div>
</div>