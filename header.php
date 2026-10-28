<?php require_once 'config.php';
$u = user();
$n = array_sum($_SESSION['cart'] ?? []); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title ?? 'AgriBiz') ?> | AgriBiz</title>
    <link rel="stylesheet" href="<?= BASE ?>style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?>">
</head>

<body data-base="<?= BASE ?>" data-t="<?= csrf() ?>">
    <header class="top"><a class="brand" href="<?= BASE ?>index.php">AgriBiz</a><button id="menu" aria-label="Toggle menu">Menu</button>
        <nav id="nav"><a href="<?= BASE ?>index.php">Home</a><a href="<?= BASE ?>catalogue.php">Produce</a><a href="<?= BASE ?>farmers.php">Farmers</a><a href="<?= BASE ?>about.php">About</a><a href="<?= BASE ?>contact.php">Contact</a>
            <?php if ($u): if ($u['role'] === 'farmer'): ?><a href="<?= BASE ?>farmer.php">My listings</a><?php elseif ($u['role'] === 'admin'): ?><a href="<?= BASE ?>admin.php">Admin</a><?php else: ?><a href="<?= BASE ?>orders.php">My orders</a><?php endif; ?>
                <a href="<?= BASE ?>logout.php">Log out (<?= e(explode(' ', $u['name'])[0]) ?>)</a><?php else: ?><a href="<?= BASE ?>login.php">Log in</a><a class="btn sm" href="<?= BASE ?>register.php">Register</a><?php endif; ?>
            <a href="<?= BASE ?>cart.php" id="cc">Cart (<?= $n ?>)</a>
        </nav>
    </header>
    <main class="wrap<?= !empty($wide) ? ' wide' : '' ?>">
        <?php if (!empty($_SESSION['msg'])): ?><p class="flash"><?= e($_SESSION['msg']) ?></p><?php unset($_SESSION['msg']);
                                                                                        endif; ?>