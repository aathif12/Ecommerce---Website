<?php
require_once '../config/helpers.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Login required', 'redirect' => '../login.php']);
    exit;
}

$db = getDB();
$uid = $_SESSION['user_id'];
$action = sanitize($_POST['action'] ?? '');
$pid = intval($_POST['product_id'] ?? 0);

if (!$pid || !$action) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Check product exists
$product = $db->query("SELECT id,stock FROM products WHERE id=$pid AND is_active=1")->fetch_assoc();
if (!$product) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

switch ($action) {
    case 'add':
        $qty = max(1, intval($_POST['quantity'] ?? 1));
        if ($qty > $product['stock']) {
            echo json_encode(['error' => 'Not enough stock']);
            exit;
        }
        // Upsert
        $existing = $db->query("SELECT id,quantity FROM cart WHERE user_id=$uid AND product_id=$pid")->fetch_assoc();
        if ($existing) {
            $new_qty = min($existing['quantity'] + $qty, $product['stock']);
            $db->query("UPDATE cart SET quantity=$new_qty WHERE id={$existing['id']}");
        } else {
            $db->query("INSERT INTO cart (user_id,product_id,quantity) VALUES ($uid,$pid,$qty)");
        }
        $count = $db->query("SELECT SUM(quantity) c FROM cart WHERE user_id=$uid")->fetch_assoc()['c'];
        echo json_encode(['success' => true, 'message' => 'Added to cart!', 'count' => intval($count)]);
        break;

    case 'remove':
        $db->query("DELETE FROM cart WHERE user_id=$uid AND product_id=$pid");
        $count = $db->query("SELECT SUM(quantity) c FROM cart WHERE user_id=$uid")->fetch_assoc()['c'];
        echo json_encode(['success' => true, 'message' => 'Removed from cart', 'count' => intval($count)]);
        break;

    case 'update':
        $qty = max(1, intval($_POST['quantity'] ?? 1));
        if ($qty > $product['stock']) {
            echo json_encode(['error' => 'Not enough stock']);
            exit;
        }
        $db->query("UPDATE cart SET quantity=$qty WHERE user_id=$uid AND product_id=$pid");
        $count = $db->query("SELECT SUM(quantity) c FROM cart WHERE user_id=$uid")->fetch_assoc()['c'];
        echo json_encode(['success' => true, 'count' => intval($count)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>