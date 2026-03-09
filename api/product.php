<?php
require_once '../config/helpers.php';
header('Content-Type: application/json');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$product = $db->query("
    SELECT p.*, c.name as cat_name,
    (SELECT COUNT(*) FROM product_likes WHERE product_id=p.id) as likes
    FROM products p LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.id=$id AND p.is_active=1
")->fetch_assoc();

echo json_encode(['product' => $product]);
?>