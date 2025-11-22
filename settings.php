<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                try {
                    $company_name = clean($_POST['company_name']);
                    $email = clean($_POST['email']);
                    $phone = clean($_POST['phone']);
                    $location = clean($_POST['location']);
                    $city = clean($_POST['city']);

                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            company_name = ?,
                            email = ?,
                            phone = ?,
                            location = ?,
                            city = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([$company_name, $email, $phone, $location, $city, $user_id]);
                    setFlash('تم تحديث الملف الشخصي بنجاح!', 'success');
                } catch(PDOException $e) {
                    setFlash('حدث خطأ أثناء تحديث الملف الشخصي: ' . $e->getMessage(), 'danger');
                }
                break;

            case 'change_password':
                $current_password = clean($_POST['current_password']);
                $new_password = clean($_POST['new_password']);
                $confirm_password = clean($_POST['confirm_password']);

                if ($new_password !== $confirm_password) {
                    setFlash('كلمتا المرور الجديدتان لا تتطابقان.', 'danger');
                    break;
                }

                if (!password_verify($current_password, $user['password'])) {
                    setFlash('كلمة المرور الحالية غير صحيحة.', 'danger');
                    break;
                }

                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    setFlash('تم تغيير كلمة المرور بنجاح!', 'success');
                } catch(PDOException $e) {
                    setFlash('حدث خطأ أثناء تغيير كلمة المرور: ' . $e->getMessage(), 'danger');
                }
                break;
        }
    }
    header('Location: settings.php');
    exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الإعدادات - <?= SITE_NAME ?></title>
    
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
        
        /* Form Cards */
        .form-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            margin-bottom: 25px;
        }
        
        .form-card:last-child {
            margin-bottom: 0;
        }
        
        .form-card h4 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #666;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
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
        }
        
        .form-control-neumorphic:focus {
            outline: none;
            box-shadow: 
                inset 6px 6px 12px var(--neumorphic-dark),
                inset -6px -6px 12px var(--neumorphic-light);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .input-group-neumorphic {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-group-neumorphic .form-control-neumorphic {
            flex: 1;
        }
        
        .toggle-password {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
            background: rgba(0,0,0,0.05);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-start;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(0,0,0,0.05);
        }
        
        .btn-save {
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 
                6px 6px 18px rgba(23, 79, 132, 0.4),
                -3px -3px 10px rgba(255, 255, 255, 0.9);
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
        
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .form-row { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .form-actions { flex-direction: column; }
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
            <a href="messages.php">
                <i class="fas fa-comments"></i>
                <span>الرسائل</span>
            </a>
            <a href="settings.php" class="active">
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
                <h2>الإعدادات ⚙️</h2>
                <p>إدارة إعدادات حسابك وتحديث معلوماتك</p>
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
        
        <!-- Profile Settings Form -->
        <div class="form-card">
            <h4>
                <i class="fas fa-user-edit" style="color: var(--primary-color);"></i>
                تعديل الملف الشخصي
            </h4>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-section">
                    <div class="section-title">المعلومات الأساسية</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                اسم الشركة <span class="required">*</span>
                            </label>
                            <input type="text" name="company_name" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['company_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                البريد الإلكتروني <span class="required">*</span>
                            </label>
                            <input type="email" name="email" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <input type="text" name="phone" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>الدولة</label>
                            <select name="location" class="form-control-neumorphic">
                                <option value="">اختر الدولة</option>
                                <option value="السعودية" <?= ($user['location'] ?? '') == 'السعودية' ? 'selected' : '' ?>>السعودية</option>
                                <option value="الإمارات" <?= ($user['location'] ?? '') == 'الإمارات' ? 'selected' : '' ?>>الإمارات</option>
                                <option value="الكويت" <?= ($user['location'] ?? '') == 'الكويت' ? 'selected' : '' ?>>الكويت</option>
                                <option value="قطر" <?= ($user['location'] ?? '') == 'قطر' ? 'selected' : '' ?>>قطر</option>
                                <option value="البحرين" <?= ($user['location'] ?? '') == 'البحرين' ? 'selected' : '' ?>>البحرين</option>
                                <option value="عمان" <?= ($user['location'] ?? '') == 'عمان' ? 'selected' : '' ?>>عمان</option>
                                <option value="مصر" <?= ($user['location'] ?? '') == 'مصر' ? 'selected' : '' ?>>مصر</option>
                                <option value="الأردن" <?= ($user['location'] ?? '') == 'الأردن' ? 'selected' : '' ?>>الأردن</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>المدينة</label>
                            <input type="text" name="city" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['city'] ?? '') ?>" 
                                   placeholder="الرياض">
                        </div>
                        <div class="form-group">
                            <!-- Empty column for alignment -->
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>
                        حفظ التغييرات
                    </button>
                </div>
                
            </form>
        </div>
        
        <!-- Password Change Form -->
        <div class="form-card">
            <h4>
                <i class="fas fa-lock" style="color: var(--primary-color);"></i>
                تغيير كلمة المرور
            </h4>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-section">
                    <div class="section-title">أمان الحساب</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                كلمة المرور الحالية <span class="required">*</span>
                            </label>
                            <div class="input-group-neumorphic">
                                <input type="password" name="current_password" class="form-control-neumorphic" required>
                                <button type="button" class="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <!-- Empty column for alignment -->
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                كلمة المرور الجديدة <span class="required">*</span>
                            </label>
                            <div class="input-group-neumorphic">
                                <input type="password" name="new_password" class="form-control-neumorphic" required>
                                <button type="button" class="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="help-text">يجب أن تتكون من 8 أحرف على الأقل</span>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                تأكيد كلمة المرور الجديدة <span class="required">*</span>
                            </label>
                            <div class="input-group-neumorphic">
                                <input type="password" name="confirm_password" class="form-control-neumorphic" required>
                                <button type="button" class="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-key"></i>
                        تغيير كلمة المرور
                    </button>
                </div>
                
            </form>
        </div>
        
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>

</body>
</html>