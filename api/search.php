<?php
require_once '../config/helpers.php';
header('Content-Type: application/json');

$db = getDB();
$q = sanitize($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['products' => []]);
    exit;
}

$products = $db->query("
    SELECT id, name, brand, price, sale_price, image
    FROM products
    WHERE is_active=1 AND (name LIKE '%$q%' OR brand LIKE '%$q%')
    ORDER BY is_featured DESC, views DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

echo json_encode(['products' => $products]);
?>