<?php
require_once '../config/helpers.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Login required', 'redirect' => '../login.php']);
    exit;
}

$db = getDB();
$uid = $_SESSION['user_id'];
$pid = intval($_POST['product_id'] ?? 0);

if (!$pid) {
    echo json_encode(['error' => 'Invalid product']);
    exit;
}

// Toggle like
$existing = $db->query("SELECT id FROM product_likes WHERE user_id=$uid AND product_id=$pid")->fetch_assoc();

if ($existing) {
    $db->query("DELETE FROM product_likes WHERE user_id=$uid AND product_id=$pid");
    $liked = false;
    $msg = 'Removed from wishlist';
} else {
    $db->query("INSERT IGNORE INTO product_likes (user_id,product_id) VALUES ($uid,$pid)");
    $liked = true;
    $msg = 'Added to wishlist!';
}

$total = $db->query("SELECT COUNT(*) c FROM product_likes WHERE user_id=$uid")->fetch_assoc()['c'];

echo json_encode(['success' => true, 'liked' => $liked, 'message' => $msg, 'total' => intval($total)]);
?>