<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user_id = $_SESSION['user_id'];
$active_page = 'my-jobs';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (PHP code for saving is correct, no changes needed here) ...
    $company_name = clean($_POST['company_name']);
    $contact_person = clean($_POST['contact_person']);
    $email = clean($_POST['email']);
    $phone = clean($_POST['phone']);
    $address = clean($_POST['address']);
    $industry = clean($_POST['industry']);
    $website = clean($_POST['website']);
    $notes = clean($_POST['notes']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO freelancer_employers (freelancer_id, company_name, contact_person, email, phone, address, industry, website, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $company_name, $contact_person, $email, $phone, $address, $industry, $website, $notes])) {
            setFlash('success', 'تم إضافة جهة العمل بنجاح');
            redirect('my-jobs.php');
        }
    } catch (PDOException $e) {
        setFlash('danger', 'حدث خطأ في قاعدة البيانات.');
        error_log($e->getMessage());
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة جهة عمل - <?= SITE_NAME ?></title>
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
                <h2><i class="fas fa-user-tie text-primary"></i> إضافة جهة عمل</h2>
                <p>سجل معلومات العميل أو الشركة التي تعمل معها من خارج المنصة.</p>
            </div>
            <a href="my-jobs.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right ml-2"></i> العودة
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>
        
        <div class="section-card">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>اسم الشركة *</label><input type="text" name="company_name" class="form-control form-control-neumorphic" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>الشخص المسؤول</label><input type="text" name="contact_person" class="form-control form-control-neumorphic"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>البريد الإلكتروني</label><input type="email" name="email" class="form-control form-control-neumorphic"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>رقم الهاتف</label><input type="tel" name="phone" class="form-control form-control-neumorphic"></div></div>
                </div>
                <div class="form-group"><label>العنوان</label><textarea name="address" class="form-control form-control-neumorphic" rows="2"></textarea></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>المجال/الصناعة</label><input type="text" name="industry" class="form-control form-control-neumorphic"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>الموقع الإلكتروني</label><input type="url" name="website" class="form-control form-control-neumorphic"></div></div>
                </div>
                <div class="form-group"><label>ملاحظات إضافية</label><textarea name="notes" class="form-control form-control-neumorphic" rows="3"></textarea></div>
                <button type="submit" class="btn btn-primary-neumorphic mt-3"><i class="fas fa-save mr-2"></i> حفظ جهة العمل</button>
            </form>
        </div>
    </div>
</body>
</html>