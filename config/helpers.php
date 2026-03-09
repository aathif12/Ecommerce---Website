<?php
session_start();
require_once __DIR__ . '/config/database.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin()
{
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price)
{
    return '$' . number_format($price, 2);
}

function generateOrderNumber()
{
    return 'ORD-' . strtoupper(uniqid()) . '-' . rand(100, 999);
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function getCartCount()
{
    if (!isLoggedIn())
        return 0;
    $stmt = dbQuery("SELECT SUM(quantity) as cnt FROM cart WHERE user_id=?", [$_SESSION['user_id']], 'i');
    $res = $stmt->get_result()->fetch_assoc();
    return $res['cnt'] ?? 0;
}

function getWishlistCount()
{
    if (!isLoggedIn())
        return 0;
    $stmt = dbQuery("SELECT COUNT(*) as cnt FROM product_likes WHERE user_id=?", [$_SESSION['user_id']], 'i');
    $res = $stmt->get_result()->fetch_assoc();
    return $res['cnt'] ?? 0;
}
?>