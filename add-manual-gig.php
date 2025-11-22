<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user_id = $_SESSION['user_id'];
$active_page = 'my-jobs';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (PHP code for saving is correct, no changes needed here) ...
    $title = clean($_POST['title']);
    $employer_name = clean($_POST['employer_name']);
    $start_time = clean($_POST['start_time']);
    $end_time = clean($_POST['end_time']);
    $rate_value = (float)($_POST['rate_value'] ?? 0);
    $status = clean($_POST['status']);
    $description = clean($_POST['notes']);

    if (empty($title) || empty($start_time) || empty($end_time) || empty($rate_value)) {
        setFlash('danger', 'يرجى ملء الحقول المطلوبة.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO freelancer_jobs (freelancer_id, job_title, employer_name, job_date, due_date, amount, status, description, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'manual')");
            if ($stmt->execute([$user_id, $title, $employer_name, $start_time, $end_time, $rate_value, $status, $description])) {
                setFlash('success', 'تمت إضافة المهمة اليدوية بنجاح!');
                redirect('my-jobs.php');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'حدث خطأ في قاعدة البيانات.');
            error_log("Manual Gig Add Error: " . $e->getMessage());
        }
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة مهمة يدوية - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sidebar.css" rel="stylesheet">
    <style>
        :root {
            --neumorphic-bg: #e9ecef;
            --neumorphic-light: #ffffff;
            --neumorphic-dark: rgba(174, 174, 192, 0.4);
            --primary-color: #174F84;
        }
        body { background: var(--neumorphic-bg); font-family: 'Cairo', sans-serif; }
        .main-content { margin-right: 260px; padding: 25px; }
        .page-header h2 { font-weight: 700; color: #333; }
        .page-header p { color: #6c757d; }
        .section-card { background: var(--neumorphic-bg); border-radius: 15px; padding: 30px; box-shadow: 10px 10px 20px var(--neumorphic-dark), -10px -10px 20px var(--neumorphic-light); }
        .form-group label { font-weight: 600; font-size: 14px; color: #333; margin-bottom: 8px; }
        .form-control-neumorphic { width: 100%; padding: 12px 18px; border: none; border-radius: 10px; background: var(--neumorphic-bg); box-shadow: inset 4px 4px 8px var(--neumorphic-dark), inset -4px -4px 8px var(--neumorphic-light); font-size: 14px; }
        .btn-primary-neumorphic { background: var(--primary-color); color: white; border: none; padding: 10px 20px; font-weight: 600; border-radius: 10px; box-shadow: 4px 4px 12px rgba(23, 79, 132, 0.3), -2px -2px 6px rgba(255, 255, 255, 0.8); }
        @media (max-width: 992px) { .main-content { margin-right: 0; } }
    </style>
</head>
<body>
    <?php include '../includes/sidebar-freelancer.php'; ?>
    
    <div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-plus-circle text-primary"></i> إضافة مهمة يدوية</h2>
                <p>سجل تفاصيل وظيفة حصلت عليها من خارج المنصة لتتبعها.</p>
            </div>
            <a href="my-jobs.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right ml-2"></i> العودة لوظائفي
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>
        
        <div class="section-card">
            <form method="POST" action="">
                <div class="form-group"><label for="title">عنوان المهمة *</label><input type="text" id="title" name="title" class="form-control form-control-neumorphic" required></div>
                <div class="form-group"><label for="employer_name">اسم العميل / الشركة</label><input type="text" id="employer_name" name="employer_name" class="form-control form-control-neumorphic"></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label for="start_time">تاريخ البدء *</label><input type="datetime-local" id="start_time" name="start_time" class="form-control form-control-neumorphic" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label for="end_time">تاريخ الانتهاء *</label><input type="datetime-local" id="end_time" name="end_time" class="form-control form-control-neumorphic" required></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label for="rate_value">قيمة الأجر (ر.س) *</label><input type="number" step="0.01" id="rate_value" name="rate_value" class="form-control form-control-neumorphic" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label for="status">حالة المهمة *</label><select id="status" name="status" class="form-control form-control-neumorphic" required><option value="in_progress">قيد التنفيذ</option><option value="completed">مكتملة</option><option value="pending_payment">بانتظار الدفع</option></select></div></div>
                </div>
                <div class="form-group"><label for="notes">ملاحظات</label><textarea id="notes" name="notes" class="form-control form-control-neumorphic" rows="4"></textarea></div>
                <button type="submit" class="btn btn-primary-neumorphic mt-3"><i class="fas fa-save mr-2"></i> حفظ المهمة</button>
            </form>
        </div>
    </div>
</body>
</html>