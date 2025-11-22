<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Get conversation ID and recipient from URL
$active_conversation = isset($_GET['conversation']) ? (int)$_GET['conversation'] : null;
$recipient_id = isset($_GET['recipient']) ? (int)$_GET['recipient'] : null;

// AJAX endpoint for fetching new messages
if (isset($_GET['ajax']) && $_GET['ajax'] == 'fetch_messages' && $active_conversation) {
    try {
        $last_message_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name, u.avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ? AND m.id > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$active_conversation, $last_message_id]);
        $new_messages = $stmt->fetchAll();
        
        // Mark new messages as read
        if (!empty($new_messages)) {
            $stmt = $pdo->prepare("
                UPDATE messages 
                SET is_read = 1, read_at = NOW() 
                WHERE conversation_id = ? AND recipient_id = ? AND is_read = 0
            ");
            $stmt->execute([$active_conversation, $user_id]);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'messages' => $new_messages]);
        exit;
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// AJAX endpoint for sending messages
if (isset($_POST['ajax']) && $_POST['ajax'] == 'send_message') {
    $conversation_id = (int)$_POST['conversation_id'];
    $recipient_id = (int)$_POST['recipient_id'];
    $message_text = clean($_POST['message_text']);
    
    if (!empty($message_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, recipient_id, message_text, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$conversation_id, $user_id, $recipient_id, $message_text]);
            
            $message_id = $pdo->lastInsertId();
            
            // Fetch the newly created message
            $stmt = $pdo->prepare("
                SELECT m.*, u.first_name, u.last_name, u.avatar
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.id = ?
            ");
            $stmt->execute([$message_id]);
            $new_message = $stmt->fetch();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $new_message]);
            exit;
        } catch(PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit;
    }
}

// Handle sending a new message (non-AJAX fallback)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $conversation_id = (int)$_POST['conversation_id'];
    $recipient_id = (int)$_POST['recipient_id'];
    $message_text = clean($_POST['message_text']);
    
    if (!empty($message_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, recipient_id, message_text, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$conversation_id, $user_id, $recipient_id, $message_text]);
            
            setFlash('تم إرسال الرسالة بنجاح', 'success');
            redirect("messages.php?conversation=$conversation_id&recipient=$recipient_id");
        } catch(PDOException $e) {
            setFlash('حدث خطأ في إرسال الرسالة', 'danger');
        }
    }
}

// Mark messages as read when viewing conversation
if ($active_conversation) {
    try {
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1, read_at = NOW() 
            WHERE conversation_id = ? AND recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$active_conversation, $user_id]);
    } catch(PDOException $e) {
        // Silently fail
    }
}

// Get all conversations for this user
try {
    // Get unique conversations with latest message
    $stmt = $pdo->prepare("
        SELECT 
            m.conversation_id,
            CASE 
                WHEN m.sender_id = ? THEN m.recipient_id 
                ELSE m.sender_id 
            END as other_user_id,
            MAX(m.id) as last_message_id,
            MAX(m.created_at) as last_message_time
        FROM messages m
        WHERE m.sender_id = ? OR m.recipient_id = ?
        GROUP BY m.conversation_id, other_user_id
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $conversations_data = $stmt->fetchAll();
    
    $conversations = [];
    foreach ($conversations_data as $conv) {
        // Get user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$conv['other_user_id']]);
        $other_user = $stmt->fetch();
        
        // Get last message
        $stmt = $pdo->prepare("SELECT message_text FROM messages WHERE id = ?");
        $stmt->execute([$conv['last_message_id']]);
        $last_msg = $stmt->fetch();
        
        // Get unread count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE conversation_id = ? AND recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$conv['conversation_id'], $user_id]);
        $unread = $stmt->fetchColumn();
        
        $conversations[] = [
            'conversation_id' => $conv['conversation_id'],
            'other_user_id' => $conv['other_user_id'],
            'first_name' => $other_user['first_name'],
            'last_name' => $other_user['last_name'],
            'company_name' => $other_user['company_name'],
            'avatar' => $other_user['avatar'],
            'last_message' => $last_msg['message_text'],
            'last_message_time' => $conv['last_message_time'],
            'unread_count' => $unread
        ];
    }
} catch(PDOException $e) {
    $conversations = [];
}

// Get messages for active conversation
$messages = [];
$recipient = null;
if ($active_conversation) {
    try {
        // Get messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name, u.avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$active_conversation]);
        $messages = $stmt->fetchAll();
        
        // Get recipient info
        if ($recipient_id) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$recipient_id]);
            $recipient = $stmt->fetch();
        } elseif (!empty($messages)) {
            $other_user_id = ($messages[0]['sender_id'] == $user_id) ? $messages[0]['recipient_id'] : $messages[0]['sender_id'];
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$other_user_id]);
            $recipient = $stmt->fetch();
        }
    } catch(PDOException $e) {
        $messages = [];
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الرسائل - <?= SITE_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --neumorphic-bg: #e9ecef;
            --neumorphic-light: #ffffff;
            --neumorphic-dark: rgba(174, 174, 192, 0.4);
            --primary-color: #174F84;
            --success-color: #00BF9A;
            --warning-color: #F5B759;
            --danger-color: #FA5252;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: var(--neumorphic-bg);
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            direction: rtl;
        }
        
        .logo {
            display: inline-block;
            text-align: center;
        }

        .logo img {
            max-width: 160px;
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background: var(--neumorphic-bg);
            position: fixed;
            top: 0;
            right: 0;
            width: 260px;
            padding: 0;
            z-index: 1000;
            box-shadow: -10px 0 30px var(--neumorphic-dark);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .sidebar-user {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin: 0 auto 12px;
            box-shadow: 
                6px 6px 12px var(--neumorphic-dark),
                -6px -6px 12px var(--neumorphic-light);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info h6 {
            margin: 0 0 3px 0;
            font-weight: 700;
            font-size: 15px;
            color: #333;
        }
        
        .user-info small {
            color: #999;
            font-size: 12px;
            display: block;
        }
        
        .nav-menu {
            padding: 15px 12px;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 11px 15px;
            margin-bottom: 6px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
        }
        
        .nav-menu a:hover {
            color: var(--primary-color);
        }
        
        .nav-menu a.active {
            background: var(--neumorphic-bg);
            color: var(--primary-color);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .nav-menu a i {
            width: 20px;
            margin-left: 10px;
            font-size: 15px;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 15px;
            width: 100%;
            padding: 0 25px;
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .sidebar-footer a:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .sidebar-footer i {
            margin-left: 8px;
        }
        
        /* Main Content */
        .main-content {
            margin-right: 260px;
            padding: 25px;
            min-height: 100vh;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        
        .page-header p {
            color: #999;
            margin: 3px 0 0 0;
            font-size: 13px;
        }
        
        .btn-primary-neumorphic {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary-neumorphic:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        /* Messages Container */
        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: calc(100vh - 150px);
        }
        
        /* Conversations List */
        .conversations-list {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 0;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .conversations-header {
            padding: 20px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .conversations-header h4 {
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            font-size: 13px;
            font-family: 'Cairo', sans-serif;
        }
        
        .search-box input:focus {
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .conversations-body {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        /* Conversation Item */
        .conversation-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            background: var(--neumorphic-bg);
        }
        
        .conversation-item:hover {
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .conversation-item.active {
            background: var(--neumorphic-bg);
            color: var(--primary-color);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .conversation-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 700;
            font-size: 14px;
            color: #333;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-item.active .conversation-name {
            color: var(--primary-color);
        }
        
        .conversation-last-message {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-meta {
            text-align: left;
            flex-shrink: 0;
        }
        
        .conversation-time {
            font-size: 11px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .unread-badge {
            background: var(--primary-color);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 
                2px 2px 4px var(--neumorphic-dark),
                -2px -2px 4px var(--neumorphic-light);
        }
        
        /* Chat Area */
        .chat-area {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--neumorphic-bg);
        }
        
        .chat-header-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .chat-header-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .chat-header-info h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .chat-header-info p {
            margin: 0;
            font-size: 12px;
            color: #999;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--neumorphic-bg);
        }
        
        /* Message Bubble */
        .message {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-end;
            gap: 10px;
        }
        
        .message.sent {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 
                3px 3px 6px var(--neumorphic-dark),
                -3px -3px 6px var(--neumorphic-light);
        }
        
        .message-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 15px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            background: var(--neumorphic-light);
        }
        
        .message.sent .message-content {
            background: linear-gradient(135deg, var(--primary-color), #2c5f9b);
            color: white;
        }
        
        .message-text {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
            text-align: left;
        }
        
        .message.sent .message-time {
            color: rgba(255,255,255,0.7);
        }
        
        /* Message Input */
        .chat-input {
            padding: 20px;
            border-top: 2px solid rgba(0,0,0,0.05);
            background: var(--neumorphic-bg);
        }
        
        .chat-input form {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .chat-input input {
            flex: 1;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
        }
        
        .chat-input input:focus {
            outline: none;
        }
        
        .btn-send {
            width: 50px;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: var(--primary-color);
            color: white;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
        }
        
        .btn-send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h5 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Scrollbar */
        .conversations-body::-webkit-scrollbar,
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        
        .conversations-body::-webkit-scrollbar-track,
        .chat-messages::-webkit-scrollbar-track {
            background: var(--neumorphic-bg);
        }
        
        .conversations-body::-webkit-scrollbar-thumb,
        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--neumorphic-dark);
            border-radius: 10px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .messages-container {
                grid-template-columns: 300px 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .messages-container {
                grid-template-columns: 1fr;
                height: calc(100vh - 120px);
            }
            .conversations-list {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <img src="/images/logo.png" alt="BusyLancer Logo">
            </a>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name']) ?>&background=174F84&color=fff&size=140" alt="Avatar">
            </div>
            <div class="user-info">
                <h6><?= htmlspecialchars($user['company_name'] ?? ($user['first_name'] . ' ' . $user['last_name'])) ?></h6>
                <small>حساب تجاري</small>
            </div>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>لوحة التحكم</span>
            </a>
            <a href="post-job.php">
                <i class="fas fa-plus-circle"></i>
                <span>نشر وظيفة</span>
            </a>
            <a href="jobs.php">
                <i class="fas fa-briefcase"></i>
                <span>وظائفي</span>
            </a>
            <a href="applications.php">
                <i class="fas fa-users"></i>
                <span>المتقدمين</span>
            </a>
            <a href="hired.php">
                <i class="fas fa-user-check"></i>
                <span>المستقلين المعينين</span>
            </a>
            <a href="financial.php">
                <i class="fas fa-wallet"></i>
                <span>المالية</span>
            </a>
            <a href="company-profile.php">
                <i class="fas fa-building"></i>
                <span>الملف التعريفي</span>
            </a>
            <a href="messages.php" class="active">
                <i class="fas fa-comments"></i>
                <span>الرسائل</span>
            </a>
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                <span>الإعدادات</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h2>الرسائل 💬</h2>
                <p>تواصل مع المستقلين والمتعاملين</p>
            </div>
            <a href="dashboard.php" class="btn-primary-neumorphic">
                <i class="fas fa-arrow-right"></i>
                العودة للوحة التحكم
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Messages Container -->
        <div class="messages-container">
            
            <!-- Conversations List -->
            <div class="conversations-list">
                <div class="conversations-header">
                    <h4>المحادثات</h4>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="ابحث عن محادثة...">
                    </div>
                </div>
                
                <div class="conversations-body">
                    <?php if (empty($conversations)): ?>
                        <div style="text-align: center; padding: 40px 20px; color: #999;">
                            <i class="far fa-comments" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
                            <p style="font-size: 13px;">لا توجد محادثات بعد<br>ابدأ بالتواصل مع المستقلين</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?conversation=<?= $conv['conversation_id'] ?>&recipient=<?= $conv['other_user_id'] ?>" 
                               class="conversation-item <?= $active_conversation == $conv['conversation_id'] ? 'active' : '' ?>">
                                <div class="conversation-avatar">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($conv['first_name']) ?>&background=174F84&color=fff&size=100" 
                                         alt="<?= htmlspecialchars($conv['first_name']) ?>">
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name">
                                        <?= htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']) ?>
                                    </div>
                                    <div class="conversation-last-message">
                                        <?= htmlspecialchars(mb_substr($conv['last_message'], 0, 35)) ?>...
                                    </div>
                                </div>
                                <div class="conversation-meta">
                                    <div class="conversation-time">
                                        <?= timeAgo($conv['last_message_time']) ?>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area">
                <?php if (!$active_conversation): ?>
                    <div class="empty-state">
                        <i class="far fa-comment-dots"></i>
                        <h5>اختر محادثة</h5>
                        <p>اختر محادثة من القائمة على اليمين<br>للبدء في المراسلة</p>
                    </div>
                <?php else: ?>
                    <?php if ($recipient): ?>
                        <!-- Chat Header -->
                        <div class="chat-header">
                            <div class="chat-header-avatar">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($recipient['first_name']) ?>&background=174F84&color=fff&size=100" 
                                     alt="<?= htmlspecialchars($recipient['first_name']) ?>">
                            </div>
                            <div class="chat-header-info">
                                <h4><?= htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']) ?></h4>
                                <p><?= htmlspecialchars($recipient['email']) ?></p>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="empty-state">
                                    <i class="far fa-comment"></i>
                                    <p>لا توجد رسائل بعد<br>ابدأ المحادثة الآن!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message <?= $msg['sender_id'] == $user_id ? 'sent' : 'received' ?>" data-message-id="<?= $msg['id'] ?>">
                                        <div class="message-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($msg['first_name']) ?>&background=174F84&color=fff&size=70" 
                                                 alt="<?= htmlspecialchars($msg['first_name']) ?>">
                                        </div>
                                        <div class="message-content">
                                            <p class="message-text"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></p>
                                            <div class="message-time">
                                                <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="chat-input">
                            <form id="messageForm">
                                <input type="hidden" name="conversation_id" value="<?= $active_conversation ?>">
                                <input type="hidden" name="recipient_id" value="<?= $recipient['id'] ?>">
                                <input type="text" id="messageInput" name="message_text" placeholder="اكتب رسالتك..." required autocomplete="off">
                                <button type="submit" class="btn-send" id="sendButton">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
        </div>
        
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        <?php if ($active_conversation && $recipient): ?>
        const currentUserId = <?= $user_id ?>;
        const conversationId = <?= $active_conversation ?>;
        const recipientId = <?= $recipient['id'] ?>;
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
        let isScrolledToBottom = true;
        
        // Auto-scroll to bottom of messages on load
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Track if user is at bottom of chat
        if (chatMessages) {
            chatMessages.addEventListener('scroll', function() {
                const threshold = 100;
                isScrolledToBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight < threshold;
            });
        }
        
        // Function to scroll to bottom
        function scrollToBottom(force = false) {
            if (chatMessages && (isScrolledToBottom || force)) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // Function to add message to chat
        function addMessageToChat(message) {
            const isSent = message.sender_id == currentUserId;
            const messageClass = isSent ? 'sent' : 'received';
            
            const messageTime = new Date(message.created_at).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const messageHTML = `
                <div class="message ${messageClass}" data-message-id="${message.id}">
                    <div class="message-avatar">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(message.first_name)}&background=174F84&color=fff&size=70" 
                             alt="${message.first_name}">
                    </div>
                    <div class="message-content">
                        <p class="message-text">${message.message_text.replace(/
/g, '<br>')}</p>
                        <div class="message-time">${messageTime}</div>
                    </div>
                </div>
            `;
            
            // Remove empty state if exists
            const emptyState = chatMessages.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            chatMessages.insertAdjacentHTML('beforeend', messageHTML);
            scrollToBottom(true);
        }
        
        // Handle form submission with AJAX
        $('#messageForm').on('submit', function(e) {
            e.preventDefault();
            
            const messageInput = $('#messageInput');
            const messageText = messageInput.val().trim();
            const sendButton = $('#sendButton');
            
            if (!messageText) return;
            
            // Disable input while sending
            messageInput.prop('disabled', true);
            sendButton.prop('disabled', true);
            
            $.ajax({
                url: 'messages.php',
                method: 'POST',
                data: {
                    ajax: 'send_message',
                    conversation_id: conversationId,
                    recipient_id: recipientId,
                    message_text: messageText
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.message) {
                        addMessageToChat(response.message);
                        lastMessageId = response.message.id;
                        messageInput.val('');
                    } else {
                        alert('حدث خطأ في إرسال الرسالة');
                    }
                },
                error: function() {
                    alert('حدث خطأ في إرسال الرسالة. حاول مرة أخرى.');
                },
                complete: function() {
                    messageInput.prop('disabled', false);
                    sendButton.prop('disabled', false);
                    messageInput.focus();
                }
            });
        });
        
        // Fetch new messages periodically
        function fetchNewMessages() {
            $.ajax({
                url: 'messages.php',
                method: 'GET',
                data: {
                    ajax: 'fetch_messages',
                    conversation: conversationId,
                    last_id: lastMessageId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.messages && response.messages.length > 0) {
                        response.messages.forEach(function(message) {
                            // Check if message already exists
                            if (!document.querySelector(`[data-message-id="${message.id}"]`)) {
                                addMessageToChat(message);
                                lastMessageId = message.id;
                            }
                        });
                    }
                }
            });
        }
        
        // Poll for new messages every 3 seconds
        setInterval(fetchNewMessages, 3000);
        <?php endif; ?>
    </script>

</body>
</html>