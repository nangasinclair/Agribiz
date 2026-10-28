<?php
session_start();
define('BASE', '/agribiz/');
$db = new mysqli('localhost', 'root', '', 'agribiz'); // XAMPP defaults
if ($db->connect_error) die('Database not found. Import schema.sql in phpMyAdmin first.');
$db->set_charset('utf8mb4');
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function user()
{
    return $_SESSION['u'] ?? null;
}
function need($r)
{
    if (!user() || user()['role'] !== $r) {
        header('Location: ' . BASE . 'login.php');
        exit;
    }
}
function csrf()
{
    return $_SESSION['t'] ?? ($_SESSION['t'] = bin2hex(random_bytes(16)));
}
function chk()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), $_POST['t'] ?? '')) die('Invalid request token.');
}
function q($sql, $types = '', ...$p)
{
    global $db;
    $s = $db->prepare($sql);
    if ($types) $s->bind_param($types, ...$p);
    $s->execute();
    return $s;
}
function rows($s)
{
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
function go($u, $m = '')
{
    if ($m) $_SESSION['msg'] = $m;
    header('Location: ' . BASE . $u);
    exit;
}
function cartItems()
{
    $o = [];
    foreach ($_SESSION['cart'] ?? [] as $id => $n) {
        $r = rows(q('SELECT id,name,price,quantity FROM produce WHERE id=? AND approved=1', 'i', $id));
        if ($r) {
            $r = $r[0];
            $r['qty'] = min($n, $r['quantity']);
            if ($r['qty'] > 0) $o[] = $r;
        }
    }
    return $o;
}
function photo($p, $n, $c = 0)
{
    return '<img src="' . ($p ? BASE . 'uploads/' . e($p) : BASE . 'img/cat-' . (int)$c . '.svg') . '" alt="' . e($n) . '" loading="lazy">';
}
