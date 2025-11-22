<?php
// functions.php - Helper Functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Get current user data
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Format money in Saudi Riyal
function formatMoney($amount) {
    return number_format($amount, 2) . ' ' . CURRENCY;
}

// Time ago in Arabic
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'الآن';
    if ($diff < 3600) return floor($diff / 60) . ' دقيقة';
    if ($diff < 86400) return floor($diff / 3600) . ' ساعة';
    if ($diff < 604800) return floor($diff / 86400) . ' يوم';
    
    return date('d/m/Y', $time);
}

// Get status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge-neumorphic badge-pending">قيد المراجعة</span>',
        'accepted' => '<span class="badge-neumorphic badge-accepted">مقبول</span>',
        'rejected' => '<span class="badge-neumorphic badge-rejected">مرفوض</span>',
        'active' => '<span class="badge-neumorphic badge-accepted">نشط</span>',
        'filled' => '<span class="badge-neumorphic badge-accepted">مكتمل</span>',
        'closed' => '<span class="badge-neumorphic badge-rejected">مغلق</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge-neumorphic badge-pending">' . $status . '</span>';
}

// Flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Redirect
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Get unread messages count
function getUnreadMessages($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

// Get notifications count
function getNotificationsCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}
?>