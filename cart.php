<?php require 'config.php';
$title = 'Cart';
$err = '';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk();
  $a = $_POST['a'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  if ($a === 'add') {
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + max(1, (int)$_POST['qty']);
    if (!empty($_POST['ajax'])) {
      header('Content-Type: application/json');
      exit(json_encode(['n' => array_sum($_SESSION['cart'])]));
    }
    go('cart.php', 'Added to cart.');
  }
  if ($a === 'remove') {
    unset($_SESSION['cart'][$id]);
    go('cart.php');
  }
  if ($a === 'place') {
    need('buyer');
    $items = cartItems();
    if (!$items) go('cart.php', 'Your cart is empty.');
    $db->begin_transaction();
    try {
      $tot = 0;
      foreach ($items as $i) $tot += $i['price'] * $i['qty'];
      q('INSERT INTO orders(buyer_id,total) VALUES(?,?)', 'id', user()['id'], $tot);
      $oid = $db->insert_id;
      foreach ($items as $i) {
        $s = q('UPDATE produce SET quantity=quantity-? WHERE id=? AND quantity>=?', 'iii', $i['qty'], $i['id'], $i['qty']);
        if ($s->affected_rows !== 1) throw new Exception($i['name'] . ' is out of stock.');
        q('INSERT INTO order_items(order_id,produce_id,qty,price) VALUES(?,?,?,?)', 'iiid', $oid, $i['id'], $i['qty'], $i['price']);
      }
      $db->commit();
      $_SESSION['cart'] = [];
      go('receipt.php?id=' . $oid, 'Order placed.');
    } catch (Exception $x) {
      $db->rollback();
      $err = $x->getMessage();
    }
  }
}
$items = cartItems();
$tot = 0;
require 'header.php'; ?><h1>Your cart</h1><?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif;
                                                                                                                  if (!$items): ?><p>Your cart is empty. <a href="catalogue.php">Browse produce</a></p><?php else: ?>
  <div class="tw">
    <table>
      <tr>
        <th>Item</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Subtotal</th>
        <th></th>
      </tr>
      <?php foreach ($items as $i): $tot += $i['price'] * $i['qty']; ?><tr>
          <td><?= e($i['name']) ?></td>
          <td><?= $i['qty'] ?></td>
          <td><?= number_format($i['price'], 2) ?></td>
          <td><?= number_format($i['price'] * $i['qty'], 2) ?></td>
          <td>
            <form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="a" value="remove"><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="btn sm del">Remove</button></form>
          </td>
        </tr><?php endforeach; ?>
      <tr>
        <th colspan="3">Total (KES)</th>
        <th colspan="2"><?= number_format($tot, 2) ?></th>
      </tr>
    </table>
  </div><br>
  <?php if (user() && user()['role'] === 'buyer'): ?><form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="a" value="place"><button class="btn">Place order</button></form>
  <?php else: ?><p>Please <a href="login.php">log in</a> or <a href="register.php">register</a> as a buyer to place your order.</p><?php endif;
                                                                                                                              endif;
                                                                                                                              require 'footer.php'; ?>