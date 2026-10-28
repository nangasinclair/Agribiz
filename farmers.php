<?php require 'config.php';
$title = 'Farmer Profiles';
$f = rows(q("SELECT u.name,u.location,u.bio,(SELECT COUNT(*) FROM produce p WHERE p.farmer_id=u.id AND p.approved=1) n FROM users u WHERE role='farmer' AND approved=1"));
require 'header.php'; ?>
<h1>Our farmers</h1>
<div class="grid"><?php foreach ($f as $x): ?><div class="card">
            <h3><?= e($x['name']) ?></h3>
            <p><?= e($x['location']) ?></p>
            <p><?= e($x['bio']) ?></p><span class="tag"><?= $x['n'] ?> listings</span>
        </div>
    <?php endforeach;
                    if (!$f): ?><p>No farmers approved yet.</p><?php endif; ?></div><?php require 'footer.php'; ?>