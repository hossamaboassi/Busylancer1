<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$gig_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$active_page = 'my-jobs';

// Fetch existing gig data
$stmt = $pdo->prepare("SELECT * FROM gigs WHERE id = ? AND freelancer_id = ?");
$stmt->execute([$gig_id, $user_id]);
$gig = $stmt->fetch();
if (!$gig) { die("المهمة غير موجودة."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is the update logic
    $title = trim($_POST['title'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $rate_value = $_POST['rate_value'] ?? '';
    $supervisor_name = trim($_POST['supervisor_name'] ?? '');
    $supervisor_phone = trim($_POST['supervisor_phone'] ?? '');
    
    try {
        $stmt = $pdo->prepare("
            UPDATE gigs SET title = ?, start_time = ?, end_time = ?, rate_value = ?, 
            supervisor_name = ?, supervisor_phone = ? WHERE id = ? AND freelancer_id = ?
        ");
        
        if ($stmt->execute([$title, $start_time, $end_time, (float)$rate_value, $supervisor_name, $supervisor_phone, $gig_id, $user_id])) {
            setFlash('success', 'تم تحديث تفاصيل المهمة بنجاح!');
            redirect("gig-details.php?id={$gig_id}");
        }
    } catch (PDOException $e) {
        setFlash('danger', 'حدث خطأ في قاعدة البيانات.');
        error_log("Gig update error: " . $e->getMessage());
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعديل المهمة - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sidebar.css" rel="stylesheet">
    <style>
        :root { --neumorphic-bg: #e9ecef; --primary-color: #174F84; }
        body { background: var(--neumorphic-bg); font-family: 'Cairo', sans-serif; }
        .main-content { margin-right: 260px; padding: 25px; }
        .form-card { background: var(--neumorphic-bg); border-radius: 15px; padding: 30px; box-shadow: 10px 10px 20px rgba(174,174,192,0.4), -10px -10px 20px #fff; }
        .form-control-neumorphic { width: 100%; border: none; border-radius: 10px; background: var(--neumorphic-bg); box-shadow: inset 4px 4px 8px rgba(174,174,192,0.4), inset -4px -4px 8px #fff; }
        .btn-primary-neumorphic { background: var(--primary-color); color: white; border: none; padding: 10px 20px; font-weight: 600; border-radius: 10px; }
        @media (max-width: 992px) { .main-content { margin-right: 0; } }
    </style>
</head>
<body>
    <?php include '../includes/sidebar-freelancer.php'; ?>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-edit text-primary"></i> تعديل المهمة الميدانية</h2>
                <p class="text-muted">قم بتحديث المعلومات الأساسية للمهمة.</p>
            </div>
             <a href="gig-details.php?id=<?= $gig_id ?>" class="btn btn-secondary">إلغاء</a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group"><label>عنوان المهمة *</label><input type="text" name="title" class="form-control form-control-neumorphic" value="<?= htmlspecialchars($gig['title']) ?>" required></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>وقت البدء *</label><input type="datetime-local" name="start_time" class="form-control form-control-neumorphic" value="<?= date('Y-m-d\TH:i', strtotime($gig['start_time'])) ?>" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>وقت الانتهاء *</label><input type="datetime-local" name="end_time" class="form-control form-control-neumorphic" value="<?= date('Y-m-d\TH:i', strtotime($gig['end_time'])) ?>" required></div></div>
                </div>
                <div class="form-group"><label>قيمة الأجر (ر.س) *</label><input type="number" step="0.01" name="rate_value" class="form-control form-control-neumorphic" value="<?= htmlspecialchars($gig['rate_value']) ?>" required></div>
                <hr class="my-4">
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>اسم المشرف *</label><input type="text" name="supervisor_name" class="form-control form-control-neumorphic" value="<?= htmlspecialchars($gig['supervisor_name']) ?>" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>رقم هاتف المشرف *</label><input type="tel" name="supervisor_phone" class="form-control form-control-neumorphic" value="<?= htmlspecialchars($gig['supervisor_phone']) ?>" required></div></div>
                </div>
                <button type="submit" class="btn btn-primary-neumorphic mt-3"><i class="fas fa-save"></i> حفظ التعديلات</button>
            </form>
        </div>
    </div>
</body>
</html>