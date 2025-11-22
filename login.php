<?php
require_once 'config.php';
require_once 'functions.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] == 'freelancer') {
        redirect(SITE_URL . '/freelancer/dashboard.php');
    } else {
        redirect(SITE_URL . '/business/dashboard.php');
    }
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            if ($user['role'] == 'freelancer') {
                redirect(SITE_URL . '/freelancer/dashboard.php');
            } else {
                redirect(SITE_URL . '/business/dashboard.php');
            }
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول - <?= SITE_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
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
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-card {
            background: var(--neumorphic-bg);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 
                15px 15px 30px var(--neumorphic-dark),
                -15px -15px 30px var(--neumorphic-light);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo h1 {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .logo p {
            color: #999;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-control-neumorphic {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 12px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 5px 5px 10px var(--neumorphic-dark),
                inset -5px -5px 10px var(--neumorphic-light);
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control-neumorphic:focus {
            outline: none;
            box-shadow: 
                inset 7px 7px 15px var(--neumorphic-dark),
                inset -7px -7px 15px var(--neumorphic-light);
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
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
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
            color: #FA5252;
            font-size: 14px;
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(0,0,0,0.1);
        }
        
        .divider span {
            background: var(--neumorphic-bg);
            padding: 0 20px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
        
        .demo-logins {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-demo {
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                5px 5px 10px var(--neumorphic-dark),
                -5px -5px 10px var(--neumorphic-light);
            color: #666;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-demo:hover {
            color: var(--primary-color);
            box-shadow: 
                inset 5px 5px 10px var(--neumorphic-dark),
                inset -5px -5px 10px var(--neumorphic-light);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
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
                <p>منصة ربط المستقلين بأصحاب الأعمال</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-neumorphic">
                    <i class="fas fa-exclamation-circle ml-2"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
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
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt ml-2"></i>
                    تسجيل الدخول
                </button>
            </form>
            
            <div class="divider">
                <span>أو جرب حساب تجريبي</span>
            </div>
            
            <div class="demo-logins">
                <form method="POST" action="">
                    <input type="hidden" name="email" value="ahmed@busylancer.com">
                    <input type="hidden" name="password" value="password">
                    <button type="submit" class="btn-demo">
                        <i class="fas fa-user ml-1"></i><br>
                        دخول كمستقل
                    </button>
                </form>
                
                <form method="POST" action="">
                    <input type="hidden" name="email" value="business@busylancer.com">
                    <input type="hidden" name="password" value="password">
                    <button type="submit" class="btn-demo">
                        <i class="fas fa-briefcase ml-1"></i><br>
                        دخول كصاحب عمل
                    </button>
                </form>
            </div>
            
            <div class="register-link">
                ليس لديك حساب؟ 
                <a href="register.php">إنشاء حساب جديد</a>
            </div>
        </div>
    </div>
</body>
</html>