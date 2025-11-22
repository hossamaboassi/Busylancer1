<?php
// config.php - Database Configuration
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration - UPDATE THESE FOR HOSTINGER
define('DB_HOST', 'localhost');
define('DB_NAME', 'u375083261_busylancer_dbf');
define('DB_USER', 'u375083261_blancer_user'); // Change this to your actual username
define('DB_PASS', 'Bhpl]2o2!');  // Change this to your actual password - leave empty if no password

// Site Configuration
define('SITE_URL', 'https://busy-lancer.com'); // Change to your actual URL
define('SITE_NAME', 'BusyLancer');
define('CURRENCY', 'ر.س'); // Saudi Riyal

// Connect to Database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("
    <!DOCTYPE html>
    <html lang='ar' dir='rtl'>
    <head>
        <meta charset='utf-8'>
        <title>خطأ في الاتصال</title>
        <style>
            body { font-family: 'Cairo', Arial; background: #e9ecef; padding: 50px; text-align: center; }
            .error-box { background: white; max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            h2 { color: #FA5252; margin-bottom: 20px; }
            .error-msg { background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: right; }
            .steps { text-align: right; background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .steps h3 { color: #174F84; margin-bottom: 15px; }
            .steps ol { padding-right: 20px; }
            .steps li { margin-bottom: 10px; line-height: 1.6; }
            code { background: #f5f5f5; padding: 3px 8px; border-radius: 5px; color: #e83e8c; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h2>⚠️ خطأ في الاتصال بقاعدة البيانات</h2>
            
            <div class='error-msg'>
                <strong>الخطأ:</strong> " . htmlspecialchars($e->getMessage()) . "
            </div>
            
            <div class='steps'>
                <h3>🔧 خطوات الحل:</h3>
                <ol>
                    <li><strong>افتح ملف</strong> <code>config.php</code></li>
                    <li><strong>ابحث عن السطر 9-11:</strong>
                        <pre style='background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; text-align: left; overflow-x: auto;'>
define('DB_HOST', 'localhost');
define('DB_USER', 'u375083261_blgrok');
define('DB_PASS', 'Bhpl]2o2!');</pre>
                    </li>
                    <li><strong>غيّر القيم حسب بيانات Hostinger الخاصة بك:</strong>
                        <ul style='margin-top: 10px;'>
                            <li><code>DB_USER</code> = اسم المستخدم من MySQL في Hostinger</li>
                            <li><code>DB_PASS</code> = كلمة المرور من MySQL في Hostinger</li>
                        </ul>
                    </li>
                    <li><strong>احفظ الملف وحدّث الصفحة</strong></li>
                </ol>
                
                <hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>
                
                <h3 style='color: #174F84; margin-top: 20px;'>📍 أين تجد البيانات في Hostinger:</h3>
                <ol>
                    <li>سجل دخول إلى <strong>Hostinger Control Panel</strong></li>
                    <li>اذهب إلى <strong>Databases → MySQL Databases</strong></li>
                    <li>اختر قاعدة البيانات: <code>u375083261_blgrok</code></li>
                    <li>ستجد:
                        <ul style='margin-top: 10px;'>
                            <li><strong>Username:</strong> انسخه إلى DB_USER</li>
                            <li><strong>Password:</strong> انسخه إلى DB_PASS</li>
                        </ul>
                    </li>
                </ol>
            </div>
            
            <p style='color: #999; font-size: 14px; margin-top: 30px;'>
                إذا كنت تستخدم localhost (XAMPP/WAMP)، عادة يكون:<br>
                <code>DB_USER = 'root'</code> و <code>DB_PASS = ''</code> (فارغة)
            </p>
        </div>
    </body>
    </html>
    ");
}

// Timezone
date_default_timezone_set('Asia/Riyadh');
?>