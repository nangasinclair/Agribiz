<?php require 'config.php';
$title = 'Produce Catalogue';
$s = '%' . trim($_GET['q'] ?? '') . '%';
$c = (int)($_GET['c'] ?? 0);
$list = rows(q('SELECT p.*,u.name farmer FROM produce p JOIN users u ON u.id=p.farmer_id WHERE p.approved=1 AND p.quantity>0 AND (p.name LIKE ? OR p.location LIKE ?) AND (?=0 OR p.category_id=?) ORDER BY p.id DESC', 'ssii', $s, $s, $c, $c));
$cats = rows(q('SELECT * FROM categories'));
require 'header.php'; ?>
<h1>Fresh produce</h1>
<form class="filters" method="get" id="filters"><input id="q" name="q" placeholder="Search tomatoes, maize, Kiambu..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off"><select id="c" name="c">
        <option value="0">All categories</option>
        <?php foreach ($cats as $k): ?><option value="<?= $k['id'] ?>" <?= $c == $k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option><?php endforeach; ?>
    </select><button class="btn">Search</button></form>
<p id="count"><?= count($list) ?> items found</p>
<div class="grid" id="list" data-live><?php foreach ($list as $p): ?><div class="card"><a href="product.php?id=<?= $p['id'] ?>"><?= photo($p['photo'], $p['name'], $p['category_id']) ?></a>
            <div class="b">
                <h3><a href="product.php?id=<?= $p['id'] ?>"><?= e($p['name']) ?></a></h3>
                <span class="price">KES <?= number_format($p['price'], 2) ?></span><span class="tag<?= $p['quantity'] < 10 ? ' low' : '' ?>"><?= $p['quantity'] < 10 ? 'Only ' : '' ?><?= $p['quantity'] ?> left</span><small><?= e($p['farmer']) ?>, <?= e($p['location']) ?></small>
                <button class="btn sm add" data-id="<?= $p['id'] ?>">Add to cart</button>
            </div>
        </div><?php endforeach; ?></div>
<?php if (!$list): ?><p>Nothing matches yet. Try another word or category.</p><?php endif;
                                                                        require 'footer.php'; ?>