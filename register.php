<?php require 'config.php';
$title = 'Register';
$err = [];
$v = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    chk();
    $name = trim($v['name'] ?? '');
    $email = strtolower(trim($v['email'] ?? ''));
    $ph = trim($v['phone'] ?? '');
    $pw = $v['password'] ?? '';
    $role = $v['role'] ?? '';
    $loc = trim($v['location'] ?? '');
    if (strlen($name) < 2) $err[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Enter a valid email address.';
    if (!preg_match('/^\+?[0-9]{9,13}$/', $ph)) $err[] = 'Enter a valid phone number (digits only).';
    if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $err[] = 'Password needs 8+ characters with letters and numbers.';
    if (!in_array($role, ['farmer', 'buyer'])) $err[] = 'Choose farmer or buyer.';
    if (!$err && rows(q('SELECT id FROM users WHERE email=?', 's', $email))) $err[] = 'That email is already registered.';
    if (!$err) {
        q('INSERT INTO users(name,email,phone,password,role,approved,location) VALUES(?,?,?,?,?,?,?)', 'sssssis', $name, $email, $ph, password_hash($pw, PASSWORD_DEFAULT), $role, $role === 'buyer' ? 1 : 0, $loc);
        go('login.php', $role === 'farmer' ? 'Registered. An administrator must approve your account before you can log in.' : 'Registered. You can log in now.');
    }
}
require 'header.php'; ?><h1>Create an account</h1>
<?php foreach ($err as $x): ?><p class="err"><?= e($x) ?></p><?php endforeach; ?>
<form class="box" id="regform" method="post"><input type="hidden" name="t" value="<?= csrf() ?>">
    <label>Full name</label><input name="name" required minlength="2" value="<?= e($v['name'] ?? '') ?>">
    <label>Email</label><input type="email" name="email" required value="<?= e($v['email'] ?? '') ?>">
    <label>Phone</label><input name="phone" required pattern="\+?[0-9]{9,13}" value="<?= e($v['phone'] ?? '') ?>">
    <label>Location (town/county)</label><input name="location" value="<?= e($v['location'] ?? '') ?>">
    <label>Password</label><input type="password" id="password" name="password" required minlength="8">
    <label>I am a</label><select name="role" required>
        <option value="buyer">Buyer</option>
        <option value="farmer">Farmer</option>
    </select><br><br>
    <button class="btn">Register</button>
</form><?php require 'footer.php'; ?>