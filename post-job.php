<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = clean($_POST['title']);
        $description = clean($_POST['description']);
        $category = clean($_POST['category']);
        $job_type = clean($_POST['job_type']);
        $pay_unit = clean($_POST['pay_unit']);
        $rate = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
        $location = clean($_POST['location']);
        $city = clean($_POST['city']);
        $gender_requirement = clean($_POST['gender_requirement'] ?? '');
        $dress_code = clean($_POST['dress_code'] ?? '');
        $required_languages = clean($_POST['required_languages'] ?? '');
        $headcount = isset($_POST['headcount']) ? intval($_POST['headcount']) : 0;
        $shift_start = clean($_POST['shift_start'] ?? '');
        $shift_end = clean($_POST['shift_end'] ?? '');
        $work_auth = clean($_POST['work_auth'] ?? '');
        $status = $_POST['action'] === 'draft' ? 'draft' : 'active';
        
        // Format location
        $job_location = $city . ', ' . $location;
        
        // Insert job
        $stmt = $pdo->prepare("
            INSERT INTO jobs (
                business_id, title, description, category, job_type,
                hourly_rate, location, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $title,
            $description,
            $category,
            $job_type,
            $rate,
            $job_location,
            $status
        ]);
        
        $job_id = $pdo->lastInsertId();
        
        if ($status === 'draft') {
            setFlash('تم حفظ الوظيفة كمسودة بنجاح!', 'success');
        } else {
            setFlash('تم نشر الوظيفة بنجاح! سيتم مراجعتها من قبل المستقلين قريباً.', 'success');
        }
        
        header('Location: jobs.php');
        exit;
        
    } catch(PDOException $e) {
        setFlash('حدث خطأ أثناء نشر الوظيفة: ' . $e->getMessage(), 'danger');
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نشر وظيفة جديدة - <?= SITE_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --neumorphic-bg: #f5f5f7;
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
        
        .logo-placeholder {
            width: 120px;
            height: 45px;
            margin: 0 auto;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 18px;
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
            background: #f5f5f7;
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
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: var(--neumorphic-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: #ddd;
            z-index: 0;
        }
        
        .progress-step.active:not(:last-child)::after {
            background: var(--primary-color);
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--neumorphic-bg);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #999;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            position: relative;
            z-index: 1;
        }
        
        .progress-step.active .step-icon {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                6px 6px 12px rgba(23, 79, 132, 0.3),
                -3px -3px 8px rgba(255, 255, 255, 0.8);
        }
        
        .progress-step.completed .step-icon {
            background: var(--success-color);
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            font-weight: 600;
            color: #999;
        }
        
        .progress-step.active .step-label {
            color: var(--primary-color);
        }
        
        /* Form Layout */
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
        }
        
        /* Form Card */
        .form-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
        }
        
        .step-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .step-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .step-header p {
            font-size: 13px;
            color: #999;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: var(--danger-color);
            margin-right: 3px;
        }
        
        .form-group .help-text {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        
        .form-control-neumorphic {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
            color: #333;
        }
        
        .form-control-neumorphic:focus {
            outline: none;
            box-shadow: 
                inset 6px 6px 12px var(--neumorphic-dark),
                inset -6px -6px 12px var(--neumorphic-light);
        }
        
        textarea.form-control-neumorphic {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Budget Type Toggle */
        .budget-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .budget-option {
            padding: 12px;
            text-align: center;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 13px;
            color: #666;
        }
        
        .budget-option input {
            display: none;
        }
        
        .budget-option.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                inset 4px 4px 8px rgba(0,0,0,0.2),
                inset -2px -2px 4px rgba(255,255,255,0.1);
        }
        
        /* Checkbox group */
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-item input {
            accent-color: var(--primary-color);
        }
        
        .checkbox-item label {
            margin: 0;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
        }
        
        /* Radio group */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-item input {
            accent-color: var(--primary-color);
        }
        
        .radio-item label {
            margin: 0;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
        }
        
        /* Preview Card */
        .preview-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            position: sticky;
            top: 25px;
        }
        
        .preview-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .preview-header h4 {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .preview-header p {
            font-size: 12px;
            color: #999;
            margin: 0;
        }
        
        .preview-content {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        
        .preview-item {
            margin-bottom: 15px;
        }
        
        .preview-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .preview-label i {
            color: var(--primary-color);
            width: 16px;
        }
        
        .preview-value {
            color: #666;
            padding-right: 24px;
        }
        
        .preview-empty {
            color: #ccc;
            font-style: italic;
            padding-right: 24px;
        }
        
        .preview-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--neumorphic-bg);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--primary-color);
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
            margin: 2px;
        }
        
        /* Form Navigation */
        .form-navigation {
            display: flex;
            gap: 15px;
            justify-content: space-between;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(0,0,0,0.05);
        }
        
        .btn-neumorphic {
            padding: 12px 30px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 
                6px 6px 18px rgba(23, 79, 132, 0.4),
                -3px -3px 10px rgba(255, 255, 255, 0.9);
        }
        
        .btn-secondary {
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-secondary:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
            box-shadow: 
                5px 5px 15px rgba(0, 191, 154, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
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
        
        /* Character Counter */
        .char-counter {
            font-size: 11px;
            color: #999;
            text-align: left;
            margin-top: 5px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .form-layout {
                grid-template-columns: 1fr;
            }
            .preview-card {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .form-row { grid-template-columns: 1fr; }
            .progress-steps { flex-direction: column; gap: 15px; }
            .progress-step:not(:last-child)::after { display: none; }
            .budget-toggle { grid-template-columns: 1fr 1fr; }
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
            <a href="post-job.php" class="active">
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
                <span>الموظفين المعينين</span>
            </a>
            <a href="financial.php">
                <i class="fas fa-wallet"></i>
                <span>المالية</span>
            </a>
            <a href="company-profile.php">
                <i class="fas fa-building"></i>
                <span>الملف التعريفي</span>
            </a>
            <a href="messages.php">
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
                <h2>نشر وظيفة جديدة 📝</h2>
                <p>أنشئ إعلان وظيفة مؤقتة على الموقع لجذب أفضل الموظفين بدوام جزئي</p>
            </div>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="progress-step active" data-step="1">
                <div class="step-icon">1</div>
                <div class="step-label">المعلومات الأساسية</div>
            </div>
            <div class="progress-step" data-step="2">
                <div class="step-icon">2</div>
                <div class="step-label">تفاصيل المنصب</div>
            </div>
            <div class="progress-step" data-step="3">
                <div class="step-icon">3</div>
                <div class="step-label">المتطلبات والأجر</div>
            </div>
            <div class="progress-step" data-step="4">
                <div class="step-icon">4</div>
                <div class="step-label">المراجعة والنشر</div>
            </div>
        </div>
        
        <!-- Form Layout -->
        <div class="form-layout">
            
            <!-- Form Card -->
            <div class="form-card">
                <form method="POST" id="jobForm">
                    
                    <!-- Step 1: Basic Information -->
                    <div class="form-step active" data-step="1">
                        <div class="step-header">
                            <h3>المعلومات الأساسية</h3>
                            <p>ابدأ بإضافة عنوان ووصف واضح للوظيفة</p>
                        </div>
                        
                        <div class="form-group">
                            <label>عنوان الوظيفة <span class="required">*</span></label>
                            <input type="text" name="title" id="jobTitle" class="form-control-neumorphic" 
                                   placeholder="مثال: ويترز / ويتريس لفعالية حفل الزفاف" 
                                   required maxlength="100">
                            <div class="char-counter">
                                <span id="titleCount">0</span> / 100 حرف
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>وصف الوظيفة <span class="required">*</span></label>
                            <textarea name="description" id="jobDescription" class="form-control-neumorphic" 
                                      placeholder="اكتب وصفاً تفصيلياً للوظيفة والمهام المطلوبة..." 
                                      required maxlength="1000"></textarea>
                            <div class="char-counter">
                                <span id="descCount">0</span> / 1000 حرف
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>التصنيف <span class="required">*</span></label>
                                <select name="category" id="jobCategory" class="form-control-neumorphic" required>
                                    <option value="">اختر التصنيف</option>
                                    <option value="ضيافة">ضيافة</option>
                                    <option value="تجزئة">تجزئة</option>
                                    <option value="فعاليات">فعاليات</option>
                                    <option value="مبيعات">مبيعات</option>
                                    <option value="إداري">إداري</option>
                                    <option value="خدمات">خدمات</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>نوع الوظيفة <span class="required">*</span></label>
                                <select name="job_type" id="jobType" class="form-control-neumorphic" required>
                                    <option value="">اختر نوع الوظيفة</option>
                                    <option value="دوام جزئي">دوام جزئي</option>
                                    <option value="مؤقت">مؤقت</option>
                                    <option value="مشروع">مشروع</option>
                                    <option value="فعالية">فعالية</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Position Details -->
                    <div class="form-step" data-step="2">
                        <div class="step-header">
                            <h3>تفاصيل المنصب</h3>
                            <p>حدد متطلبات الوظيفة والمواصفات</p>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>العدد المطلوب <span class="required">*</span></label>
                                <input type="number" name="headcount" id="headcount" class="form-control-neumorphic" 
                                       min="1" max="100" placeholder="مثال: 5" required>
                            </div>
                            
                            <div class="form-group">
                                <label>الجنس</label>
                                <select name="gender_requirement" id="genderRequirement" class="form-control-neumorphic">
                                    <option value="any">أي</option>
                                    <option value="male">ذكور فقط</option>
                                    <option value="female">إناث فقط</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>نوع الورديات <span class="required">*</span></label>
                            <select name="shift_type" id="shiftType" class="form-control-neumorphic" required>
                                <option value="single">وردية واحدة</option>
                                <option value="multi">عدة أيام</option>
                                <option value="recurring">متكررة</option>
                            </select>
                        </div>

                        <!-- Single Shift -->
                        <div id="shiftSingle">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>تاريخ الوردية <span class="required">*</span></label>
                                    <input type="date" name="shift_date" id="shiftDate" class="form-control-neumorphic" required>
                                </div>
                                <div class="form-group">
                                    <label>وقت البدء <span class="required">*</span></label>
                                    <input type="time" name="shift_start" id="startTime" class="form-control-neumorphic" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>وقت الانتهاء <span class="required">*</span></label>
                                    <input type="time" name="shift_end" id="endTime" class="form-control-neumorphic" required>
                                </div>
                                <div class="form-group">
                                    <label>وقت النداء (اختياري)</label>
                                    <input type="time" name="call_time" id="callTime" class="form-control-neumorphic">
                                </div>
                            </div>
                        </div>

                        <!-- Multi Day Shift -->
                        <div id="shiftMulti" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>تاريخ البدء <span class="required">*</span></label>
                                    <input type="date" name="start_date_multi" id="startDateMulti" class="form-control-neumorphic">
                                </div>
                                <div class="form-group">
                                    <label>تاريخ الانتهاء <span class="required">*</span></label>
                                    <input type="date" name="end_date_multi" id="endDateMulti" class="form-control-neumorphic">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>وقت البدء اليومي</label>
                                    <input type="time" name="daily_start_time" id="dailyStartTime" class="form-control-neumorphic">
                                </div>
                                <div class="form-group">
                                    <label>وقت الانتهاء اليومي</label>
                                    <input type="time" name="daily_end_time" id="dailyEndTime" class="form-control-neumorphic">
                                </div>
                            </div>
                        </div>

                        <!-- Recurring Shift -->
                        <div id="shiftRecurring" style="display: none;">
                            <div class="form-group">
                                <label>أيام الأسبوع</label>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="السبت" id="sat">
                                        <label for="sat">السبت</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="الأحد" id="sun">
                                        <label for="sun">الأحد</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="الإثنين" id="mon">
                                        <label for="mon">الإثنين</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="الثلاثاء" id="tue">
                                        <label for="tue">الثلاثاء</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="الأربعاء" id="wed">
                                        <label for="wed">الأربعاء</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="الخميس" id="thu">
                                        <label for="thu">الخميس</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="recurrence_days[]" value="الجمعة" id="fri">
                                        <label for="fri">الجمعة</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>تاريخ البدء</label>
                                    <input type="date" name="range_start" id="rangeStart" class="form-control-neumorphic">
                                </div>
                                <div class="form-group">
                                    <label>تاريخ الانتهاء</label>
                                    <input type="date" name="range_end" id="rangeEnd" class="form-control-neumorphic">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>وقت البدء</label>
                                    <input type="time" name="recurring_start_time" id="recurringStartTime" class="form-control-neumorphic">
                                </div>
                                <div class="form-group">
                                    <label>وقت الانتهاء</label>
                                    <input type="time" name="recurring_end_time" id="recurringEndTime" class="form-control-neumorphic">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>المهارات المطلوبة</label>
                            <div id="skillsContainer" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                                <input type="text" id="skillInput" class="form-control-neumorphic" 
                                       placeholder="اكتب المهارة واضغط Enter" style="flex: 1; min-width: 200px;">
                            </div>
                            <input type="hidden" name="skills" id="skillsHidden">
                            <div class="help-text">أضف المهارات المطلوبة للوظيفة</div>
                        </div>

                        <div class="form-group">
                            <label>اللغات المطلوبة</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="العربية" id="arabic">
                                    <label for="arabic">العربية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="الإنجليزية" id="english">
                                    <label for="english">الإنجليزية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="أوردو" id="urdu">
                                    <label for="urdu">أوردو</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="هندية" id="hindi">
                                    <label for="hindi">هندية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="فلبينية" id="filipino">
                                    <label for="filipino">فلبينية</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>الزي الرسمي</label>
                            <select name="dress_code" id="uniform" class="form-control-neumorphic">
                                <option value="أسود وأبيض">أسود وأبيض</option>
                                <option value="كاجوال">كاجوال</option>
                                <option value="رسمي">رسمي</option>
                                <option value="موحد من العمل">موحد من العمل</option>
                                <option value="اسود كامل">اسود كامل</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>الحجاب</label>
                                <select name="hijab_requirement" id="hijabRequirement" class="form-control-neumorphic">
                                    <option value="optional">اختياري</option>
                                    <option value="required">مطلوب</option>
                                    <option value="not_required">غير مطلوب</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>العباية</label>
                                <select name="abaya_requirement" id="abayaRequirement" class="form-control-neumorphic">
                                    <option value="not_required">غير مطلوب</option>
                                    <option value="arrival_only">مطلوبة عند الوصول/المغادرة</option>
                                    <option value="provided">موفرة من جهة العمل</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>القدرة البدنية (ساعات الوقوف)</label>
                                <input type="number" name="stand_hours" id="standHours" class="form-control-neumorphic" min="0" placeholder="مثال: 6">
                            </div>
                            <div class="form-group">
                                <label>الحمولة (كجم)</label>
                                <input type="number" name="lift_kg" id="liftKg" class="form-control-neumorphic" min="0" placeholder="مثال: 15">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>بيئة العمل</label>
                                <select name="indoor_outdoor" id="indoorOutdoor" class="form-control-neumorphic">
                                    <option value="">اختر</option>
                                    <option value="داخلي">داخلي</option>
                                    <option value="خارجي">خارجي</option>
                                    <option value="مختلط">مختلط</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>التعرّض للحرارة/الهواء الطلق</label>
                                <select name="outdoor_heat" id="outdoorHeat" class="form-control-neumorphic">
                                    <option value="no">لا</option>
                                    <option value="yes">نعم</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>فترات الصلاة</label>
                                <select name="prayer_breaks" id="prayerBreaks" class="form-control-neumorphic">
                                    <option value="yes">متاحة</option>
                                    <option value="no">غير متاحة</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>مستوى الخبرة</label>
                                <select name="experience_level" id="experienceLevel" class="form-control-neumorphic">
                                    <option value="مبتدئ">مبتدئ</option>
                                    <option value="متوسط">متوسط</option>
                                    <option value="خبير">خبير</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>مدة المشروع (اختياري)</label>
                                <select name="duration" id="duration" class="form-control-neumorphic">
                                    <option value="">اختر المدة</option>
                                    <option value="أقل من أسبوع">أقل من أسبوع</option>
                                    <option value="1-2 أسبوع">1-2 أسبوع</option>
                                    <option value="2-4 أسابيع">2-4 أسابيع</option>
                                    <option value="1-3 أشهر">1-3 أشهر</option>
                                    <option value="3-6 أشهر">3-6 أشهر</option>
                                    <option value="أكثر من 6 أشهر">أكثر من 6 أشهر</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>آخر موعد للتقديم</label>
                                <input type="date" name="deadline" id="deadline" class="form-control-neumorphic"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>الأهلية النظامية للعمل</label>
                            <select name="work_auth" id="workAuth" class="form-control-neumorphic">
                                <option value="أي">أي</option>
                                <option value="سعودي/ة فقط">سعودي/ة فقط</option>
                                <option value="مقيم/ة فقط">مقيم/ة فقط</option>
                                <option value="سعودي/ة ومقيم/ة">سعودي/ة ومقيم/ة</option>
                            </select>
                        </div>
                    </div>

                    <!-- Step 3: Budget & Location -->
                    <div class="form-step" data-step="3">
                        <div class="step-header">
                            <h3>الميزانية والموقع</h3>
                            <p>حدد طريقة الأجر وموقع العمل</p>
                        </div>

                        <div class="form-group">
                            <label>نوع الأجر <span class="required">*</span></label>
                            <div class="budget-toggle">
                                <label class="budget-option active" data-type="hourly">
                                    <input type="radio" name="pay_unit" value="hourly" checked>
                                    <i class="fas fa-clock ml-1"></i>
                                    بالساعة
                                </label>
                                <label class="budget-option" data-type="shift">
                                    <input type="radio" name="pay_unit" value="shift">
                                    <i class="fas fa-calendar-day ml-1"></i>
                                    للورديّة
                                </label>
                                <label class="budget-option" data-type="day">
                                    <input type="radio" name="pay_unit" value="day">
                                    <i class="fas fa-sun ml-1"></i>
                                    لليوم
                                </label>
                                <label class="budget-option" data-type="event">
                                    <input type="radio" name="pay_unit" value="event">
                                    <i class="fas fa-ticket-alt ml-1"></i>
                                    لكل فعالية
                                </label>
                                <label class="budget-option" data-type="fixed">
                                    <input type="radio" name="pay_unit" value="fixed">
                                    <i class="fas fa-tag ml-1"></i>
                                    مبلغ ثابت
                                </label>
                            </div>
                        </div>

                        <div class="budget-input active" id="hourlyInput">
                            <div class="form-group">
                                <label>الأجر بالساعة (ر.س) <span class="required">*</span></label>
                                <input type="number" name="rate" id="hourlyRate" class="form-control-neumorphic"
                                       placeholder="مثال: 40" min="10" step="1" required>
                            </div>
                        </div>

                        <div class="budget-input" id="shiftInput">
                            <div class="form-group">
                                <label>أجر الورديّة (ر.س) <span class="required">*</span></label>
                                <input type="number" name="rate" id="shiftRate" class="form-control-neumorphic"
                                       placeholder="مثال: 250" min="20" step="5">
                            </div>
                        </div>

                        <div class="budget-input" id="dayInput">
                            <div class="form-group">
                                <label>أجر اليوم (ر.س) <span class="required">*</span></label>
                                <input type="number" name="rate" id="dayRate" class="form-control-neumorphic"
                                       placeholder="مثال: 400" min="20" step="5">
                            </div>
                        </div>

                        <div class="budget-input" id="eventInput">
                            <div class="form-group">
                                <label>أجر الفعالية كاملة (ر.س) <span class="required">*</span></label>
                                <input type="number" name="rate" id="eventRate" class="form-control-neumorphic"
                                       placeholder="مثال: 1200" min="50" step="10">
                            </div>
                        </div>

                        <div class="budget-input" id="fixedInput">
                            <div class="form-group">
                                <label>المبلغ الإجمالي (ر.س) <span class="required">*</span></label>
                                <input type="number" name="rate" id="fixedBudget" class="form-control-neumorphic"
                                       placeholder="مثال: 500" min="50" step="10">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>بدل نقل (اختياري)</label>
                                <input type="number" name="transport_allowance" id="transportAllowance" class="form-control-neumorphic" min="0" step="5" placeholder="مثال: 20">
                            </div>
                            <div class="form-group">
                                <label>وجبة</label>
                                <select name="meal_provided" id="mealProvided" class="form-control-neumorphic">
                                    <option value="no">لا</option>
                                    <option value="yes">نعم</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>أجر التدريب/التعريف (اختياري)</label>
                                <input type="number" name="training_pay" id="trainingPay" class="form-control-neumorphic" min="0" step="5" placeholder="مثال: 30">
                            </div>
                            <div class="form-group">
                                <label>زيادة مقابل العمل الإضافي</label>
                                <select name="overtime_applicable" id="overtimeApplicable" class="form-control-neumorphic">
                                    <option value="no">لا</option>
                                    <option value="yes">نعم</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>الدولة <span class="required">*</span></label>
                                <select name="location" id="location" class="form-control-neumorphic" required>
                                    <option value="">اختر الدولة</option>
                                    <option value="السعودية" selected>السعودية</option>
                                    <option value="الإمارات">الإمارات</option>
                                    <option value="الكويت">الكويت</option>
                                    <option value="قطر">قطر</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>المدينة <span class="required">*</span></label>
                                <input type="text" name="city" id="city" class="form-control-neumorphic"
                                       placeholder="الرياض" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>اسم الموقع/المنشأة (اختياري)</label>
                            <input type="text" name="venue" id="venue" class="form-control-neumorphic" placeholder="مثال: بوليفارد، مركز الرياض للمعارض">
                        </div>
                    </div>

                    <!-- Step 4: Review -->
                    <div class="form-step" data-step="4">
                        <div class="step-header">
                            <h3>مراجعة ونشر</h3>
                            <p>راجع معلومات الوظيفة قبل النشر</p>
                        </div>

                        <div style="background: #fff3cd; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                            <p style="margin: 0; font-size: 13px; color: #856404;">
                                <i class="fas fa-info-circle ml-1"></i>
                                <strong>ملاحظة:</strong> تأكد من صحة جميع المعلومات قبل النشر.
                                يمكنك حفظ الوظيفة كمسودة والعودة لتعديلها لاحقاً.
                            </p>
                        </div>

                        <div class="preview-content" id="finalReview">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="form-navigation">
                        <div>
                            <button type="button" class="btn-neumorphic btn-secondary" id="prevBtn" style="display: none;">
                                <i class="fas fa-arrow-right"></i>
                                السابق
                            </button>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="btn-neumorphic btn-secondary" id="saveDraftBtn" style="display: none;">
                                <i class="fas fa-save"></i>
                                حفظ كمسودة
                            </button>
                            <button type="button" class="btn-neumorphic btn-primary" id="nextBtn">
                                التالي
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <button type="submit" name="action" value="publish" class="btn-neumorphic btn-success" id="publishBtn" style="display: none;">
                                <i class="fas fa-paper-plane"></i>
                                نشر الوظيفة
                            </button>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Preview Card -->
            <div class="preview-card">
                <div class="preview-header">
                    <h4><i class="fas fa-eye ml-2"></i>معاينة الوظيفة</h4>
                    <p>هكذا سيظهر إعلانك للمستقلين</p>
                </div>

                <div class="preview-content">
                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-briefcase"></i> عنوان الوظيفة</div>
                        <div class="preview-value" id="previewTitle"><span class="preview-empty">لم يتم إضافة عنوان بعد</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-align-right"></i> الوصف</div>
                        <div class="preview-value" id="previewDescription"><span class="preview-empty">لم يتم إضافة وصف بعد</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-folder"></i> التصنيف</div>
                        <div class="preview-value" id="previewCategory"><span class="preview-empty">لم يتم اختيار تصنيف</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-users"></i> العدد والنوع</div>
                        <div class="preview-value" id="previewHeadcount"><span class="preview-empty">—</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-calendar-alt"></i> التوقيت والورديات</div>
                        <div class="preview-value" id="previewShift"><span class="preview-empty">—</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-language"></i> اللغات</div>
                        <div class="preview-value" id="previewLanguages"><span class="preview-empty">—</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-tshirt"></i> الزي والاعتبارات</div>
                        <div class="preview-value" id="previewAttire"><span class="preview-empty">—</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-dollar-sign"></i> الأجر والمزايا</div>
                        <div class="preview-value" id="previewBudget"><span class="preview-empty">لم يتم تحديد الأجر</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-map-marker-alt"></i> الموقع</div>
                        <div class="preview-value" id="previewLocation"><span class="preview-empty">لم يتم تحديد موقع</span></div>
                    </div>

                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-tools"></i> المهارات/المتطلبات</div>
                        <div class="preview-value" id="previewSkills"><span class="preview-empty">لم يتم إضافة متطلبات</span></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        // Multi-step form logic
        let currentStep = 1;
        const totalSteps = 4;

        // Character counters
        document.getElementById('jobTitle').addEventListener('input', function() {
            document.getElementById('titleCount').textContent = this.value.length;
            updatePreview();
        });

        document.getElementById('jobDescription').addEventListener('input', function() {
            document.getElementById('descCount').textContent = this.value.length;
            updatePreview();
        });

        // Navigation
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });

        // Save as draft
        document.getElementById('saveDraftBtn').addEventListener('click', function() {
            const form = document.getElementById('jobForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = 'draft';
            form.appendChild(input);
            form.submit();
        });

        function validateStep(step) {
            const currentStepEl = document.querySelector(`.form-step[data-step="${step}"]`);
            const requiredInputs = currentStepEl.querySelectorAll('[required]');
            let isValid = true;

            requiredInputs.forEach(input => {
                if (!input.value || !input.value.toString().trim()) {
                    input.style.boxShadow = 'inset 4px 4px 8px rgba(250, 82, 82, 0.3), inset -4px -4px 8px rgba(255, 255, 255, 0.8)';
                    isValid = false;
                    setTimeout(() => { input.style.boxShadow = ''; }, 2000);
                }
            });

            if (!isValid) {
                alert('يرجى ملء جميع الحقول المطلوبة في هذه الخطوة');
            }

            return isValid;
        }

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(s => {
                s.classList.remove('active');
            });
            
            // Show current step
            document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');

            // Update progress steps
            document.querySelectorAll('.progress-step').forEach((s, i) => {
                const stepNumber = parseInt(s.dataset.step);
                if (stepNumber < step) {
                    s.classList.add('completed');
                    s.classList.remove('active');
                } else if (stepNumber === step) {
                    s.classList.add('active');
                    s.classList.remove('completed');
                } else {
                    s.classList.remove('active', 'completed');
                }
            });

            // Update buttons
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-flex';
            document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'inline-flex';
            document.getElementById('publishBtn').style.display = step === totalSteps ? 'inline-flex' : 'none';
            document.getElementById('saveDraftBtn').style.display = step === totalSteps ? 'inline-flex' : 'none';

            if (step === 4) {
                updateFinalReview();
            }
        }

        function updatePreview() {
            // Basic preview update logic
            const title = document.getElementById('jobTitle').value;
            const description = document.getElementById('jobDescription').value;
            const category = document.getElementById('jobCategory').value;

            if (title) {
                document.getElementById('previewTitle').innerHTML = `<strong>${escapeHtml(title)}</strong>`;
            }
            if (description) {
                document.getElementById('previewDescription').textContent = description.substring(0, 150) + (description.length > 150 ? '...' : '');
            }
            if (category) {
                document.getElementById('previewCategory').innerHTML = `<span class="preview-badge">${escapeHtml(category)}</span>`;
            }
        }

        function updateFinalReview() {
            // Final review logic
            const finalReview = document.getElementById('finalReview');
            finalReview.innerHTML = '<p>جارٍ تحميل المراجعة النهائية...</p>';
            
            // Simulate final review content
            setTimeout(() => {
                finalReview.innerHTML = `
                    <div class="preview-item">
                        <div class="preview-label"><i class="fas fa-check-circle"></i> جميع المعلومات مكتملة</div>
                        <div class="preview-value">يمكنك الآن نشر الوظيفة أو حفظها كمسودة</div>
                    </div>
                `;
            }, 500);
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Initialize
        showStep(1);
        updatePreview();

        // Debugging
        console.log('Form initialized successfully');
        console.log('Next button:', document.getElementById('nextBtn'));
        console.log('Prev button:', document.getElementById('prevBtn'));
    </script>

</body>
</html>