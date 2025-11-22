<?php
// Use a reliable path to include essential files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

// Ensure the user is a freelancer
requireRole('freelancer');

$gig_id = (int)($_GET['gig_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Verify this gig belongs to the logged-in freelancer
$g = $pdo->prepare("SELECT * FROM gigs WHERE id=? AND freelancer_id=?");
$g->execute([$gig_id, $user_id]);
$gig = $g->fetch(); 

if(!$gig) {
    die('Error: Gig not found or you do not have permission to access it.');
}

// Generate the token and link
try {
    $token = bin2hex(random_bytes(16));
    $exp = (new DateTime('+7 days'))->format('Y-m-d H:i:s');
    
    $pdo->prepare("INSERT INTO gig_confirm_tokens (gig_id, token, expires_at) VALUES (?,?,?)")
        ->execute([$gig_id, $token, $exp]);

    // The confirm.php page is in the 'freelancer' folder, not the 'api' folder.
    // We need to construct the URL correctly.
    $link = rtrim(SITE_URL, '/') . "/freelancer/api/confirm.php?token=" . $token;

    // Display the link and WhatsApp button in a user-friendly way
    echo "
    <div style='padding:40px; font-family: Cairo, sans-serif; text-align:center; background-color:#e9ecef; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
        <h3 style='font-weight:700; color:#174F84;'>تم إنشاء رابط التأكيد بنجاح</h3>
        <p style='color:#6c757d;'>انسخ الرابط وأرسله للمشرف، أو استخدم زر الواتساب.</p>
        <input style='width:100%; max-width:400px; padding:10px; border:1px solid #ccc; border-radius:8px; text-align:center; margin-bottom:15px; font-size:1rem;' value='{$link}' readonly>
        <a href='https://wa.me/" . preg_replace('/\D/','',$gig['supervisor_phone']) . "?text=" . urlencode("فضلاً أكد الدوام وقيم أدائي عبر الرابط: " . $link) . "' 
           target='_blank' 
           style='display:inline-block; padding: 12px 25px; background-color:#25D366; color:white; text-decoration:none; border-radius:8px; font-weight:600;'>
           <i class='fab fa-whatsapp'></i> إرسال عبر واتساب
        </a>
         <a href='../gig-details.php?id={$gig_id}' style='margin-top:20px; color:#174F84;'>العودة لصفحة المهمة</a>
    </div>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap' rel='stylesheet'>
    ";

} catch (PDOException $e) {
    error_log("Token creation error: " . $e->getMessage());
    die("حدث خطأ في قاعدة البيانات أثناء إنشاء الرابط.");
}
?>