<?php require 'config.php';
need('buyer');
$title = 'My Orders';
$o = rows(q('SELECT * FROM orders WHERE buyer_id=? ORDER BY id DESC', 'i', user()['id']));
require 'header.php'; ?>
<h1>My orders</h1>
<div class="tw">
    <table>
        <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
            <th></th>
        </tr>
        <?php foreach ($o as $x): ?><tr>
                <td>#<?= $x['id'] ?></td>
                <td><?= $x['created'] ?></td>
                <td>KES <?= number_format($x['total'], 2) ?></td>
                <td><?= $x['status'] ?></td>
                <td><a href="receipt.php?id=<?= $x['id'] ?>">Receipt</a></td>
            </tr><?php endforeach; ?>
    </table>
</div>
<?php if (!$o): ?><p>No orders yet.</p><?php endif;
                                    require 'footer.php'; ?>