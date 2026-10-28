<?php require 'config.php';
if (!user()) go('login.php');
$title = 'Receipt';
$r = rows(q('SELECT o.*,u.name,u.phone FROM orders o JOIN users u ON u.id=o.buyer_id WHERE o.id=?', 'i', (int)($_GET['id'] ?? 0)));
if (!$r || (user()['role'] !== 'admin' && $r[0]['buyer_id'] != user()['id'])) go('index.php', 'Receipt not found.');
$o = $r[0];
$it = rows(q('SELECT i.*,p.name FROM order_items i JOIN produce p ON p.id=i.produce_id WHERE order_id=?', 'i', $o['id']));
require 'header.php'; ?>
<h1>Order receipt #<?= $o['id'] ?></h1>
<p>Buyer: <?= e($o['name']) ?> (<?= e($o['phone']) ?>)<br>Date: <?= $o['created'] ?><br>Status: <b><?= $o['status'] ?></b></p>
<div class="tw">
    <table>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($it as $i): ?><tr>
                <td><?= e($i['name']) ?></td>
                <td><?= $i['qty'] ?></td>
                <td><?= number_format($i['price'], 2) ?></td>
                <td><?= number_format($i['price'] * $i['qty'], 2) ?></td>
            </tr><?php endforeach; ?>
        <tr>
            <th colspan="3">Total (KES)</th>
            <th><?= number_format($o['total'], 2) ?></th>
        </tr>
    </table>
</div><br>
<button class="btn noprint" onclick="window.print()">Print / save as PDF</button><?php require 'footer.php'; ?>