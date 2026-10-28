<?php require 'config.php';
$title = 'Home';
$cats = rows(q('SELECT * FROM categories'));
$st = rows(q("SELECT (SELECT COUNT(*) FROM users WHERE role='farmer' AND approved=1) f,(SELECT COUNT(*) FROM produce WHERE approved=1) p,(SELECT COUNT(*) FROM orders) o"))[0];
require 'header.php'; ?>
<section class="hero"><img src="img/hero.svg?v=<?= @filemtime(__DIR__ . '/img/hero.svg') ?>" alt="">
    <div class="shade"></div>
    <div class="in"><span class="pill"><i></i> Farm-direct, across Kenya</span>
        <h1>Fresh from the farm to <em>your table</em></h1>
        <p>Buy straight from the farmer who grew it. Fair prices for them, fresher food for you, delivered with a tracked order.</p>
        <form class="hsearch" action="catalogue.php" method="get"><input name="q" placeholder="Try tomatoes, maize, mangoes, Kiambu..." aria-label="Search produce"><button class="btn">Search</button></form>
        <div class="ctas"><a class="btn" href="catalogue.php">Shop fresh produce</a><a class="btn ghost" href="register.php">Sell as a farmer</a></div>
        <ul class="trust">
            <li>Verified farmers</li>
            <li>Approved listings only</li>
            <li>Order tracking and receipts</li>
        </ul>
    </div>
    <div class="fl f1"><b><?= (int)$st['f'] ?>+</b><span>verified farmers</span></div>
    <div class="fl f2"><b><?= (int)$st['p'] ?></b><span>products live now</span></div>
    <div class="fl f3"><b>100%</b><span>farm-direct</span></div>
</section>
<section class="stats">
    <div><b data-count="<?= $st['f'] ?>">0</b>verified farmers</div>
    <div><b data-count="<?= $st['p'] ?>">0</b>products live</div>
    <div><b data-count="<?= $st['o'] ?>">0</b>orders delivered and counting</div>
</section>
<h2>Shop by category</h2>
<div class="cats"><?php foreach ($cats as $c): ?><a href="catalogue.php?c=<?= $c['id'] ?>"><img src="img/cat-<?= $c['id'] ?>.svg" alt=""><span><?= e($c['name']) ?></span></a><?php endforeach; ?></div>
<h2>Just picked</h2>
<div id="fresh" class="grid">
    <p>Loading produce...</p>
</div>
<h2>How it works</h2>
<div class="steps">
    <div>
        <h3>1. Browse</h3>Search by crop, category or county.
    </div>
    <div>
        <h3>2. Order</h3>Add to your cart and place the order in a minute.
    </div>
    <div>
        <h3>3. Receive</h3>Track it from placed to delivered and print your receipt.
    </div>
</div>
<section class="cta">
    <h2>Grow something? Sell it here.</h2>
    <p>List your produce for free and reach hotels, schools and households.</p><a class="btn" href="register.php">Create a farmer account</a>
</section>
<?php require 'footer.php'; ?>