<?php require 'config.php';
need('farmer');
$title = 'My Listings';
$fid = user()['id'];
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk();
  if (($_POST['a'] ?? '') === 'del') {
    q('DELETE FROM produce WHERE id=? AND farmer_id=?', 'ii', (int)$_POST['id'], $fid);
    go('farmer.php', 'Listing removed.');
  }
  $id = (int)($_POST['id'] ?? 0);
  $nm = trim($_POST['name'] ?? '');
  $cat = (int)$_POST['category'];
  $pr = (float)$_POST['price'];
  $qt = (int)$_POST['quantity'];
  $loc = trim($_POST['location'] ?? '');
  $ph = null;
  if (strlen($nm) < 2) $err[] = 'Enter the produce name.';
  if ($pr <= 0) $err[] = 'Price must be above 0.';
  if ($qt < 0) $err[] = 'Quantity cannot be negative.';
  if (!rows(q('SELECT id FROM categories WHERE id=?', 'i', $cat))) $err[] = 'Choose a category.';
  if (!empty($_FILES['photo']['name'])) {
    $f = $_FILES['photo'];
    $m = $f['error'] === 0 ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$m] ?? null;
    if (!$ext || $f['size'] > 2 * 1024 * 1024) $err[] = 'Photo must be a JPG, PNG or WebP under 2 MB.';
    else {
      $ph = bin2hex(random_bytes(8)) . '.' . $ext;
      move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $ph);
    }
  }
  if (!$err) {
    if ($id) {
      q('UPDATE produce SET category_id=?,name=?,price=?,quantity=?,location=?,photo=COALESCE(?,photo),approved=0 WHERE id=? AND farmer_id=?', 'isdissii', $cat, $nm, $pr, $qt, $loc, $ph, $id, $fid);
    } else q('INSERT INTO produce(farmer_id,category_id,name,price,quantity,location,photo) VALUES(?,?,?,?,?,?,?)', 'iisdiss', $fid, $cat, $nm, $pr, $qt, $loc, $ph);
    go('farmer.php', 'Saved. Your listing goes live after administrator approval.');
  }
}
$e = rows(q('SELECT * FROM produce WHERE id=? AND farmer_id=?', 'ii', (int)($_GET['edit'] ?? 0), $fid))[0] ?? [];
$cats = rows(q('SELECT * FROM categories'));
$mine = rows(q('SELECT p.*,c.name cat FROM produce p JOIN categories c ON c.id=p.category_id WHERE farmer_id=? ORDER BY p.id DESC', 'i', $fid));
require 'header.php'; ?>
<h1>My produce listings</h1><?php foreach ($err as $x): ?><p class="err"><?= e($x) ?></p><?php endforeach; ?>
<form class="box" method="post" enctype="multipart/form-data"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $e['id'] ?? 0 ?>">
  <h2><?= $e ? 'Edit listing' : 'Add produce' ?></h2><label>Name</label><input name="name" required value="<?= e($e['name'] ?? '') ?>">
  <label>Category</label><select name="category"><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($e['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <label>Price per unit (KES)</label><input type="number" step="0.01" min="0.01" name="price" required value="<?= e($e['price'] ?? '') ?>">
  <label>Quantity available</label><input type="number" min="0" name="quantity" required value="<?= e($e['quantity'] ?? '') ?>">
  <label>Location</label><input name="location" value="<?= e($e['location'] ?? '') ?>"><label>Photo (JPG/PNG, max 2 MB)</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><br><br><button class="btn">Save listing</button>
</form>
<h2>Your listings</h2>
<div class="tw">
  <table>
    <tr>
      <th>Name</th>
      <th>Category</th>
      <th>Price</th>
      <th>Qty</th>
      <th>Status</th>
      <th></th>
    </tr>
    <?php foreach ($mine as $p): ?><tr>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['cat']) ?></td>
        <td><?= number_format($p['price'], 2) ?></td>
        <td><?= $p['quantity'] ?></td>
        <td><?= $p['approved'] ? 'Live' : 'Pending approval' ?></td>
        <td><a class="btn sm alt" href="?edit=<?= $p['id'] ?>">Edit</a>
          <form method="post" style="display:inline" data-confirm="Remove this listing?"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm del">Remove</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div><?php require 'footer.php'; ?>