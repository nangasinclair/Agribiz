<?php require 'config.php';
$r = rows(q('SELECT p.*,u.name farmer,c.name cat FROM produce p JOIN users u ON u.id=p.farmer_id JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.approved=1', 'i', (int)($_GET['id'] ?? 0)));
if (!$r) go('catalogue.php', 'Produce not found.');
$p = $r[0];
$title = $p['name'];
require 'header.php'; ?>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
    <div><?= photo($p['photo'], $p['name'], $p['category_id']) ?></div>
    <div>
        <h1><?= e($p['name']) ?></h1>
        <p><span class="tag"><?= e($p['cat']) ?></span> Grown by <?= e($p['farmer']) ?> in <?= e($p['location']) ?></p>
        <p class="price" style="font-size:1.4rem">KES <?= number_format($p['price'], 2) ?></p>
        <p><?= $p['quantity'] ?> available</p>
        <form method="post" action="cart.php" id="pform"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="a" value="add"><input type="hidden" name="id" value="<?= $p['id'] ?>">
            <label>Quantity</label>
            <div class="qty"><button type="button" class="step" data-d="-1" aria-label="Less">&minus;</button><input type="number" name="qty" value="1" min="1" max="<?= $p['quantity'] ?>" required><button type="button" class="step" data-d="1" aria-label="More">+</button></div><br><button class="btn">Add to cart</button>
        </form>
    </div>
</div>
<?php require 'footer.php'; ?>