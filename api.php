<?php require 'config.php';
header('Content-Type: application/json');
$lim = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$s = '%' . trim($_GET['q'] ?? '') . '%';
$c = (int)($_GET['c'] ?? 0);
echo json_encode(rows(q("SELECT p.id,p.name,p.price,p.quantity,p.location,p.photo,p.category_id,u.name farmer FROM produce p JOIN users u ON u.id=p.farmer_id WHERE p.approved=1 AND p.quantity>0 AND (p.name LIKE ? OR p.location LIKE ?) AND (?=0 OR p.category_id=?) ORDER BY p.id DESC LIMIT ?", 'ssiii', $s, $s, $c, $c, $lim)));
