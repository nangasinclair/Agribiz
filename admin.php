<?php require 'config.php';
need('admin');
$title = 'Admin';
$wide = true;
$me = user()['id'];
const STATUSES = ['Placed', 'Confirmed', 'Dispatched', 'Delivered', 'Cancelled'];
// One-time safe upgrade: allow the "Cancelled" order status on databases created from the old schema.sql
try {
    $db->query("ALTER TABLE orders MODIFY status ENUM('Placed','Confirmed','Dispatched','Delivered','Cancelled') NOT NULL DEFAULT 'Placed'");
} catch (Throwable $x) {
}

/** Remove produce completely, including its lines in past orders, then fix order totals. */
function purgeProduce(array $ids)
{
    global $db;
    foreach ($ids as $pid) {
        $pid = (int)$pid;
        $os = rows(q('SELECT DISTINCT order_id FROM order_items WHERE produce_id=?', 'i', $pid));
        q('DELETE FROM order_items WHERE produce_id=?', 'i', $pid);
        foreach ($os as $o) {
            $oid = (int)$o['order_id'];
            $n = rows(q('SELECT COUNT(*) c,COALESCE(SUM(qty*price),0) t FROM order_items WHERE order_id=?', 'i', $oid))[0];
            if ($n['c'] == 0) q('DELETE FROM orders WHERE id=?', 'i', $oid);
            else q('UPDATE orders SET total=? WHERE id=?', 'di', $n['t'], $oid);
        }
        q('DELETE FROM produce WHERE id=?', 'i', $pid);
    }
}
function purgeUser($id)
{
    $ids = array_column(rows(q('SELECT id FROM produce WHERE farmer_id=?', 'i', $id)), 'id');
    purgeProduce($ids);
    q('DELETE FROM orders WHERE buyer_id=?', 'i', $id);
    q('DELETE FROM users WHERE id=?', 'i', $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    chk();
    $a = $_POST['a'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $tab = preg_replace('/[^a-z]/', '', $_POST['tab'] ?? 'overview');
    $msg = 'Updated.';
    try {
        switch ($a) {
            case 'user_set':
                if ($id !== $me) q('UPDATE users SET approved=? WHERE id=?', 'ii', (int)!empty($_POST['v']), $id);
                else $msg = 'You cannot suspend your own account.';
                break;
            case 'user_del':
                if ($id === $me) $msg = 'You cannot delete your own account.';
                else {
                    purgeUser($id);
                    $msg = 'User deleted.';
                }
                break;
            case 'user_save':
                $n = trim($_POST['name'] ?? '');
                $em = strtolower(trim($_POST['email'] ?? ''));
                $ph = trim($_POST['phone'] ?? '');
                $lo = trim($_POST['location'] ?? '');
                $ro = $_POST['role'] ?? 'buyer';
                $ap = (int)!empty($_POST['approved']);
                $pw = $_POST['password'] ?? '';
                if (!in_array($ro, ['farmer', 'buyer', 'admin'], true)) $ro = 'buyer';
                if ($n === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
                    $msg = 'Enter a name and a valid email.';
                    break;
                }
                if ($id === $me) {
                    $ro = 'admin';
                    $ap = 1;
                }
                if ($id) {
                    q('UPDATE users SET name=?,email=?,phone=?,location=?,role=?,approved=? WHERE id=?', 'sssssii', $n, $em, $ph, $lo, $ro, $ap, $id);
                    if ($pw !== '') {
                        if (strlen($pw) < 6) {
                            $msg = 'Saved, but password was not changed (min 6 characters).';
                            break;
                        }
                        q('UPDATE users SET password=? WHERE id=?', 'si', password_hash($pw, PASSWORD_DEFAULT), $id);
                    }
                    $msg = 'User saved.';
                } else {
                    if (strlen($pw) < 6) {
                        $msg = 'A new user needs a password of 6+ characters.';
                        break;
                    }
                    q('INSERT INTO users(name,email,phone,location,role,approved,password) VALUES(?,?,?,?,?,?,?)', 'sssssis', $n, $em, $ph, $lo, $ro, $ap, password_hash($pw, PASSWORD_DEFAULT));
                    $msg = 'User created.';
                }
                break;
            case 'prod_set':
                q('UPDATE produce SET approved=? WHERE id=?', 'ii', (int)!empty($_POST['v']), $id);
                break;
            case 'prod_del':
                purgeProduce([$id]);
                $msg = 'Listing deleted.';
                break;
            case 'prod_save':
                $n = trim($_POST['name'] ?? '');
                $fa = (int)($_POST['farmer_id'] ?? 0);
                $ca = (int)($_POST['category_id'] ?? 0);
                $pr = (float)($_POST['price'] ?? 0);
                $qt = (int)($_POST['quantity'] ?? 0);
                $lo = trim($_POST['location'] ?? '');
                $ap = (int)!empty($_POST['approved']);
                if ($n === '' || $pr <= 0 || $qt < 0 || !$fa || !$ca) {
                    $msg = 'Check the listing: name, farmer, category, price above 0.';
                    break;
                }
                if ($id) q('UPDATE produce SET farmer_id=?,category_id=?,name=?,price=?,quantity=?,location=?,approved=? WHERE id=?', 'iisdisii', $fa, $ca, $n, $pr, $qt, $lo, $ap, $id);
                else q('INSERT INTO produce(farmer_id,category_id,name,price,quantity,location,approved) VALUES(?,?,?,?,?,?,?)', 'iisdisi', $fa, $ca, $n, $pr, $qt, $lo, $ap);
                $msg = 'Listing saved.';
                break;
            case 'bulk_users':
                q("UPDATE users SET approved=1 WHERE role='farmer' AND approved=0");
                $msg = 'All pending farmers approved.';
                break;
            case 'bulk_prods':
                q('UPDATE produce SET approved=1 WHERE approved=0');
                $msg = 'All pending listings approved.';
                break;
            case 'ord_status':
                $s = $_POST['status'] ?? '';
                if (in_array($s, STATUSES, true)) q('UPDATE orders SET status=? WHERE id=?', 'si', $s, $id);
                break;
            case 'ord_del':
                q('DELETE FROM orders WHERE id=?', 'i', $id);
                $msg = 'Order deleted.';
                break;
            case 'cat_add':
                $n = trim($_POST['name'] ?? '');
                if ($n !== '') {
                    q('INSERT INTO categories(name) VALUES(?)', 's', $n);
                    $msg = 'Category added.';
                }
                break;
            case 'cat_save':
                $n = trim($_POST['name'] ?? '');
                if ($n !== '') q('UPDATE categories SET name=? WHERE id=?', 'si', $n, $id);
                $msg = 'Category renamed.';
                break;
            case 'cat_del':
                if (rows(q('SELECT COUNT(*) c FROM produce WHERE category_id=?', 'i', $id))[0]['c'] > 0) $msg = 'Move or delete the listings in this category first.';
                else {
                    q('DELETE FROM categories WHERE id=?', 'i', $id);
                    $msg = 'Category deleted.';
                }
                break;
        }
    } catch (Throwable $x) {
        $msg = (stripos($x->getMessage(), 'duplicate') !== false) ? 'That value already exists (duplicate email or category).' : 'Could not complete that action.';
    }
    $_SESSION['msg'] = $msg;
    header('Location: ' . BASE . 'admin.php#' . $tab);
    exit;
}

$users = rows(q('SELECT u.*,(SELECT COUNT(*) FROM produce WHERE farmer_id=u.id) np,(SELECT COUNT(*) FROM orders WHERE buyer_id=u.id) no FROM users u ORDER BY u.approved,u.id DESC'));
$prods = rows(q('SELECT p.*,u.name farmer,c.name cat FROM produce p JOIN users u ON u.id=p.farmer_id JOIN categories c ON c.id=p.category_id ORDER BY p.approved,p.id DESC'));
$ords = rows(q('SELECT o.*,u.name,(SELECT COUNT(*) FROM order_items WHERE order_id=o.id) items FROM orders o JOIN users u ON u.id=o.buyer_id ORDER BY o.id DESC'));
$cats = rows(q('SELECT c.*,(SELECT COUNT(*) FROM produce WHERE category_id=c.id) n FROM categories c ORDER BY c.name'));
$farmers = array_values(array_filter($users, fn($u) => $u['role'] === 'farmer'));
$pu = count(array_filter($users, fn($u) => $u['role'] === 'farmer' && !$u['approved']));
$pp = count(array_filter($prods, fn($p) => !$p['approved']));
$po = count(array_filter($ords, fn($o) => $o['status'] === 'Placed'));
$rev = array_sum(array_map(fn($o) => in_array($o['status'], ['Cancelled']) ? 0 : $o['total'], $ords));
$bySt = array_fill_keys(STATUSES, 0);
foreach ($ords as $o) $bySt[$o['status']] = ($bySt[$o['status']] ?? 0) + 1;
$maxSt = max(1, ...array_values($bySt));
$maxCat = max(1, ...($cats ? array_column($cats, 'n') : [1]));
function hid($a, $id, $tab, $x = '')
{
    return '<input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="a" value="' . $a . '"><input type="hidden" name="id" value="' . (int)$id . '"><input type="hidden" name="tab" value="' . $tab . '">' . $x;
}
function act($a, $id, $tab, $label, $cls = '', $extra = '', $confirm = '')
{
    return '<form method="post" class="inl"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . hid($a, $id, $tab, $extra) . '<button class="btn sm ' . $cls . '">' . $label . '</button></form>';
}
require 'header.php'; ?>
<div class="dash">
    <div class="dh">
        <div>
            <h1>Administrator dashboard</h1>
            <p class="sub">Approve, edit, add or remove anything on AgriBiz. Changes apply immediately.</p>
        </div>
        <div class="dq"><?php if ($pu): ?><?= act('bulk_users', 0, 'users', 'Approve all ' . $pu . ' farmers', 'alt') ?><?php endif; ?><?php if ($pp): ?><?= act('bulk_prods', 0, 'listings', 'Approve all ' . $pp . ' listings') ?><?php endif; ?></div>
    </div>

    <div class="tabs" role="tablist"><button data-tab="overview" class="on">Overview</button><button data-tab="users">Users<?php if ($pu): ?><span class="bd"><?= $pu ?></span><?php endif; ?></button><button data-tab="listings">Listings<?php if ($pp): ?><span class="bd"><?= $pp ?></span><?php endif; ?></button><button data-tab="orders">Orders<?php if ($po): ?><span class="bd"><?= $po ?></span><?php endif; ?></button><button data-tab="cats">Categories</button></div>

    <section class="pane on" id="p-overview">
        <div class="kpis">
            <div class="kpi"><small>Revenue (excl. cancelled)</small><b>KES <?= number_format($rev) ?></b></div>
            <div class="kpi"><small>Orders</small><b><?= count($ords) ?></b><em><?= $po ?> new</em></div>
            <div class="kpi"><small>Farmers</small><b><?= count($farmers) ?></b><em><?= $pu ?> pending</em></div>
            <div class="kpi"><small>Listings</small><b><?= count($prods) ?></b><em><?= $pp ?> pending</em></div>
            <div class="kpi"><small>Buyers</small><b><?= count(array_filter($users, fn($u) => $u['role'] === 'buyer')) ?></b></div>
        </div>
        <div class="two">
            <div class="pnl">
                <h2>Needs your attention</h2>
                <?php if (!$pu && !$pp && !$po): ?><p class="muted">All clear. Nothing is waiting.</p><?php endif; ?>
                <?php if ($pu): ?><p><b><?= $pu ?></b> farmer<?= $pu > 1 ? 's' : '' ?> awaiting approval <a href="#users" data-go="users">Review</a></p><?php endif; ?>
                <?php if ($pp): ?><p><b><?= $pp ?></b> listing<?= $pp > 1 ? 's' : '' ?> awaiting approval <a href="#listings" data-go="listings">Review</a></p><?php endif; ?>
                <?php if ($po): ?><p><b><?= $po ?></b> new order<?= $po > 1 ? 's' : '' ?> to confirm <a href="#orders" data-go="orders">Review</a></p><?php endif; ?>
            </div>
            <div class="pnl">
                <h2>Orders by status</h2><?php foreach ($bySt as $s => $n): ?><div class="bar"><span><?= $s ?></span><i style="width:<?= round($n / $maxSt * 100) ?>%"></i><b><?= $n ?></b></div><?php endforeach; ?>
            </div>
            <div class="pnl">
                <h2>Listings by category</h2><?php foreach ($cats as $c): ?><div class="bar"><span><?= e($c['name']) ?></span><i class="g" style="width:<?= round($c['n'] / $maxCat * 100) ?>%"></i><b><?= $c['n'] ?></b></div><?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="pane" id="p-users">
        <div class="tb"><input class="fs" data-for="tu" placeholder="Search name, email, location, role..."><select class="fsel" data-for="tu">
                <option value="">All roles</option>
                <option>farmer</option>
                <option>buyer</option>
                <option>admin</option>
            </select><button class="btn sm" data-open="u">+ Add user</button></div>
        <div class="tw">
            <table id="tu">
                <tr>
                    <th>Name</th>
                    <th>Email / phone</th>
                    <th>Role</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Activity</th>
                    <th></th>
                </tr>
                <?php foreach ($users as $x): $j = e(json_encode(['id' => $x['id'], 'name' => $x['name'], 'email' => $x['email'], 'phone' => $x['phone'], 'location' => $x['location'], 'role' => $x['role'], 'approved' => $x['approved']])); ?>
                    <tr data-role="<?= $x['role'] ?>">
                        <td><b><?= e($x['name']) ?></b></td>
                        <td><?= e($x['email']) ?><br><small><?= e($x['phone']) ?></small></td>
                        <td><span class="tag"><?= $x['role'] ?></span></td>
                        <td><?= e($x['location']) ?></td>
                        <td><span class="st <?= $x['approved'] ? 'ok' : 'wait' ?>"><?= $x['approved'] ? 'Active' : 'Pending / suspended' ?></span></td>
                        <td><small><?= $x['np'] ?> listings, <?= $x['no'] ?> orders</small></td>
                        <td class="ac"><?php if ($x['id'] != $me): ?><?= $x['approved'] ? act('user_set', $x['id'], 'users', 'Suspend', 'ghost2') : act('user_set', $x['id'], 'users', 'Approve', '', '<input type="hidden" name="v" value="1">') ?><?php endif; ?>
                            <button class="btn sm alt" data-open="u" data-j="<?= $j ?>">Edit</button><?php if ($x['id'] != $me): ?><?= act('user_del', $x['id'], 'users', 'Delete', 'del', '', 'Delete ' . $x['name'] . ' and all their listings and orders? This cannot be undone.') ?><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
            </table>
        </div>
    </section>

    <section class="pane" id="p-listings">
        <div class="tb"><input class="fs" data-for="tl" placeholder="Search produce, farmer, category..."><select class="fsel" data-for="tl">
                <option value="">All statuses</option>
                <option>Live</option>
                <option>Pending</option>
            </select><button class="btn sm" data-open="p">+ Add listing</button></div>
        <div class="tw">
            <table id="tl">
                <tr>
                    <th></th>
                    <th>Produce</th>
                    <th>Farmer</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                <?php foreach ($prods as $x): $j = e(json_encode(['id' => $x['id'], 'name' => $x['name'], 'farmer_id' => $x['farmer_id'], 'category_id' => $x['category_id'], 'price' => $x['price'], 'quantity' => $x['quantity'], 'location' => $x['location'], 'approved' => $x['approved']])); ?>
                    <tr data-role="<?= $x['approved'] ? 'Live' : 'Pending' ?>">
                        <td class="th"><?= photo($x['photo'], $x['name'], $x['category_id']) ?></td>
                        <td><b><?= e($x['name']) ?></b><br><small><?= e($x['location']) ?></small></td>
                        <td><?= e($x['farmer']) ?></td>
                        <td><?= e($x['cat']) ?></td>
                        <td>KES <?= number_format($x['price'], 2) ?></td>
                        <td><?= $x['quantity'] ?></td>
                        <td><span class="st <?= $x['approved'] ? 'ok' : 'wait' ?>"><?= $x['approved'] ? 'Live' : 'Pending' ?></span></td>
                        <td class="ac"><?= $x['approved'] ? act('prod_set', $x['id'], 'listings', 'Unpublish', 'ghost2') : act('prod_set', $x['id'], 'listings', 'Approve', '', '<input type="hidden" name="v" value="1">') ?>
                            <button class="btn sm alt" data-open="p" data-j="<?= $j ?>">Edit</button><?= act('prod_del', $x['id'], 'listings', 'Delete', 'del', '', 'Delete this listing? It will also be removed from past orders.') ?>
                        </td>
                    </tr><?php endforeach; ?>
            </table>
        </div>
    </section>

    <section class="pane" id="p-orders">
        <div class="tb"><input class="fs" data-for="to" placeholder="Search order number or buyer..."><select class="fsel" data-for="to">
                <option value="">All statuses</option><?php foreach (STATUSES as $s): ?><option><?= $s ?></option><?php endforeach; ?>
            </select></div>
        <div class="tw">
            <table id="to">
                <tr>
                    <th>#</th>
                    <th>Buyer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                <?php foreach ($ords as $x): ?><tr data-role="<?= $x['status'] ?>">
                        <td><a href="receipt.php?id=<?= $x['id'] ?>">#<?= $x['id'] ?></a></td>
                        <td><?= e($x['name']) ?></td>
                        <td><small><?= e($x['created']) ?></small></td>
                        <td><?= $x['items'] ?></td>
                        <td>KES <?= number_format($x['total'], 2) ?></td>
                        <td>
                            <form method="post" class="inl"><?= hid('ord_status', $x['id'], 'orders') ?><select name="status" class="sts s-<?= $x['status'] ?>" onchange="this.form.submit()"><?php foreach (STATUSES as $s): ?><option <?= $s === $x['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></form>
                        </td>
                        <td class="ac"><a class="btn sm alt" href="receipt.php?id=<?= $x['id'] ?>">Receipt</a><?= act('ord_del', $x['id'], 'orders', 'Delete', 'del', '', 'Delete order #' . $x['id'] . '?') ?></td>
                    </tr><?php endforeach; ?>
            </table>
        </div><?php if (!$ords): ?><p class="muted">No orders yet.</p><?php endif; ?>
    </section>

    <section class="pane" id="p-cats">
        <form method="post" class="tb"><?= hid('cat_add', 0, 'cats') ?><input name="name" placeholder="New category name" required><button class="btn sm">+ Add category</button></form>
        <div class="tw">
            <table>
                <tr>
                    <th>Category</th>
                    <th>Listings</th>
                    <th></th>
                </tr><?php foreach ($cats as $c): ?><tr>
                        <td>
                            <form method="post" class="inl rn"><?= hid('cat_save', $c['id'], 'cats') ?><input name="name" value="<?= e($c['name']) ?>" required><button class="btn sm alt">Rename</button></form>
                        </td>
                        <td><?= $c['n'] ?></td>
                        <td class="ac"><?= act('cat_del', $c['id'], 'cats', 'Delete', 'del', '', 'Delete category ' . $c['name'] . '?') ?></td>
                    </tr><?php endforeach; ?>
            </table>
        </div>
        <p class="muted">New categories use a default picture until an image img/cat-ID.svg is added.</p>
    </section>
</div>

<dialog id="du">
    <form method="post" class="box"><?= hid('user_save', 0, 'users') ?><h2 data-title>User</h2>
        <label>Name</label><input name="name" required><label>Email</label><input type="email" name="email" required><label>Phone</label><input name="phone"><label>Location</label><input name="location">
        <label>Role</label><select name="role">
            <option>farmer</option>
            <option>buyer</option>
            <option>admin</option>
        </select><label><input type="checkbox" name="approved" value="1" style="width:auto"> Approved / active</label>
        <label>Password <small>(leave blank to keep current)</small></label><input type="password" name="password" autocomplete="new-password">
        <div class="dbt"><button class="btn">Save</button><button type="button" class="btn ghost2" data-close>Cancel</button></div>
    </form>
</dialog>
<dialog id="dp">
    <form method="post" class="box"><?= hid('prod_save', 0, 'listings') ?><h2 data-title>Listing</h2>
        <label>Produce name</label><input name="name" required><label>Farmer</label><select name="farmer_id"><?php foreach ($farmers as $f): ?><option value="<?= $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select>
        <label>Category</label><select name="category_id"><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <label>Price (KES)</label><input type="number" step="0.01" min="0.01" name="price" required><label>Quantity</label><input type="number" min="0" name="quantity" required><label>Location</label><input name="location">
        <label><input type="checkbox" name="approved" value="1" style="width:auto"> Live (approved)</label>
        <div class="dbt"><button class="btn">Save</button><button type="button" class="btn ghost2" data-close>Cancel</button></div>
    </form>
</dialog>

<script>
    (() => {
        const $$ = s => [...document.querySelectorAll(s)];

        function tab(n) {
            if (!$('#p-' + n)) n = 'overview';
            $$('.pane').forEach(p => p.classList.toggle('on', p.id === 'p-' + n));
            $$('.tabs button').forEach(b => b.classList.toggle('on', b.dataset.tab === n));
            history.replaceState(null, '', '#' + n);
        }

        function $(s) {
            return document.querySelector(s);
        }
        $$('.tabs button').forEach(b => b.onclick = () => tab(b.dataset.tab));
        $$('[data-go]').forEach(a => a.onclick = e => {
            e.preventDefault();
            tab(a.dataset.go);
        });
        tab(location.hash.slice(1) || 'overview');

        function filt(id) {
            const q = ($(`.fs[data-for=${id}]`)?.value || '').toLowerCase(),
                r = $(`.fsel[data-for=${id}]`)?.value || '';
            $$('#' + id + ' tr').forEach((tr, i) => {
                if (!i) return;
                tr.style.display = (tr.textContent.toLowerCase().includes(q) && (!r || tr.dataset.role === r)) ? '' : 'none';
            });
        }
        $$('.fs,.fsel').forEach(el => el.addEventListener('input', () => filt(el.dataset.for)));
        const dlg = {
            u: $('#du'),
            p: $('#dp')
        };
        $$('[data-open]').forEach(b => b.onclick = () => {
            const d = dlg[b.dataset.open],
                f = d.querySelector('form'),
                j = b.dataset.j ? JSON.parse(b.dataset.j) : null;
            f.reset();
            f.querySelector('[name=id]').value = j ? j.id : 0;
            d.querySelector('[data-title]').textContent = (j ? 'Edit ' : 'Add ') + (b.dataset.open === 'u' ? 'user' : 'listing');
            if (j)
                for (const k in j) {
                    const el = f.elements[k];
                    if (!el) continue;
                    if (el.type === 'checkbox') el.checked = !!+j[k];
                    else el.value = j[k] ?? '';
                }
            else {
                const c = f.elements.approved;
                if (c) c.checked = true;
            }
            d.showModal();
        });
        $$('[data-close]').forEach(b => b.onclick = () => b.closest('dialog').close());
        $$('dialog').forEach(d => d.addEventListener('click', e => {
            if (e.target === d) d.close();
        }));
    })();
</script>
<?php require 'footer.php'; ?>