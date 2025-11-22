<?php
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/freelancer/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = clean($_POST['role']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'جميع الحقول مطلوبة';
    } elseif ($password !== $confirm_password) {
        $error = 'كلمتا المرور غير متطابقتين';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'البريد الإلكتروني مستخدم بالفعل';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$first_name, $last_name, $email, $hashed_password, $role])) {
                $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول';
            } else {
                $error = 'حدث خطأ أثناء إنشاء الحساب';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إنشاء حساب - <?= SITE_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Same styles as login.php -->
    <style>
        /* Copy styles from login.php */
        :root {
            --neumorphic-bg: #e9ecef;
            --neumorphic-light: #ffffff;
            --neumorphic-dark: rgba(174, 174, 192, 0.4);
            --primary-color: #174F84;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--neumorphic-bg);
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .login-container { width: 100%; max-width: 500px; }
        .login-card {
            background: var(--neumorphic-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 
                15px 15px 30px var(--neumorphic-dark),
                -15px -15px 30px var(--neumorphic-light);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { font-size: 32px; font-weight: 700; color: var(--primary-color); margin: 0; }
        .logo p { color: #999; font-size: 14px; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .form-control-neumorphic {
            width: 100%;
            padding: 12px 18px;
            border: none;
            border-radius: 12px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 5px 5px 10px var(--neumorphic-dark),
                inset -5px -5px 10px var(--neumorphic-light);
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control-neumorphic:focus {
            outline: none;
            box-shadow: 
                inset 7px 7px 15px var(--neumorphic-dark),
                inset -7px -7px 15px var(--neumorphic-light);
        }
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .role-option {
            position: relative;
        }
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .role-option label {
            display: block;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            background: var(--neumorphic-bg);
            box-shadow: 
                5px 5px 10px var(--neumorphic-dark),
                -5px -5px 10px var(--neumorphic-light);
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option input[type="radio"]:checked + label {
            color: var(--primary-color);
            box-shadow: 
                inset 5px 5px 10px var(--neumorphic-dark),
                inset -5px -5px 10px var(--neumorphic-light);
        }
        .role-option label i {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: var(--primary-color);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 
                7px 7px 20px rgba(23, 79, 132, 0.4),
                -3px -3px 10px rgba(255, 255, 255, 0.9);
        }
        .alert-neumorphic {
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
            font-size: 14px;
        }
        .alert-danger { color: #FA5252; }
        .alert-success { color: #00BF9A; }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .register-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1><?= SITE_NAME ?></h1>
                <p>إنشاء حساب جديد</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-neumorphic alert-danger">
                    <i class="fas fa-exclamation-circle ml-2"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert-neumorphic alert-success">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?= $success ?>
                    <br><a href="login.php">تسجيل الدخول الآن</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>اختر نوع الحساب</label>
                    <div class="role-selector">
                        <div class="role-option">
                            <input type="radio" name="role" value="freelancer" id="role-freelancer" checked>
                            <label for="role-freelancer">
                                <i class="fas fa-user"></i>
                                مستقل
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" name="role" value="business" id="role-business">
                            <label for="role-business">
                                <i class="fas fa-briefcase"></i>
                                صاحب عمل
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>الاسم الأول</label>
                            <input type="text" name="first_name" class="form-control-neumorphic" 
                                   placeholder="أحمد" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>اسم العائلة</label>
                            <input type="text" name="last_name" class="form-control-neumorphic" 
                                   placeholder="محمد" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control-neumorphic" 
                           placeholder="example@email.com" required>
                </div>
                
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" class="form-control-neumorphic" 
                           placeholder="••••••••" required>
                </div>
                
                <div class="form-group">
                    <label>تأكيد كلمة المرور</label>
                    <input type="password" name="confirm_password" class="form-control-neumorphic" 
                           placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-user-plus ml-2"></i>
                    إنشاء حساب
                </button>
            </form>
            
            <div class="register-link">
                لديك حساب بالفعل؟ 
                <a href="login.php">تسجيل الدخول</a>
            </div>
        </div>
    </div>
</body>
</html>