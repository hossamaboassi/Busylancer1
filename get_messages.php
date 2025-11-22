<?php
require_once '../config.php';
require_once '../functions.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($selected_user_id > 0) {
    try {
        // Get conversation ID
        $stmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
            LIMIT 1
        ");
        $stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
        $conversation = $stmt->fetch();
        
        if ($conversation) {
            // Get messages
            $stmt = $pdo->prepare("
                SELECT m.*, u.first_name, u.last_name 
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$conversation['id']]);
            $messages = $stmt->fetchAll();
            
            // Mark messages as read
            $stmt = $pdo->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
            ");
            $stmt->execute([$conversation['id'], $user_id]);
            
            // Output messages HTML
            if (empty($messages)) {
                echo '<div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>ابدأ المحادثة</h3>
                    <p>أرسل أول رسالة لبدء التواصل</p>
                </div>';
            } else {
                foreach ($messages as $msg) {
                    $messageClass = $msg['sender_id'] == $user_id ? 'sent' : 'received';
                    echo '<div class="message-group ' . $messageClass . '">
                        <div class="message-avatar">
                            <img src="https://ui-avatars.com/api/?name=' . urlencode($msg['first_name']) . '&background=667eea&color=fff&size=70" 
                                 alt="' . htmlspecialchars($msg['first_name']) . '">
                        </div>
                        <div class="message-content">
                            <div class="message-bubble">
                                ' . nl2br(htmlspecialchars($msg['message'])) . '
                            </div>
                            <div class="message-time">
                                ' . date('H:i', strtotime($msg['created_at'])) . '
                            </div>
                        </div>
                    </div>';
                }
            }
        }
    } catch(PDOException $e) {
        echo '<div class="alert alert-danger">حدث خطأ في تحميل الرسائل</div>';
    }
}
?>