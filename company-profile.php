<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $company_name = clean($_POST['company_name']);
        $company_description = clean($_POST['company_description']);
        $industry = clean($_POST['industry']);
        $company_size = clean($_POST['company_size']);
        $website = clean($_POST['website']);
        $location = clean($_POST['location']);
        $city = clean($_POST['city']);
        $phone = clean($_POST['phone']);
        $email = clean($_POST['email']);
        
        // Social links
        $linkedin = clean($_POST['linkedin'] ?? '');
        $twitter = clean($_POST['twitter'] ?? '');
        $facebook = clean($_POST['facebook'] ?? '');
        $instagram = clean($_POST['instagram'] ?? '');
        
        $social_links = json_encode([
            'linkedin' => $linkedin,
            'twitter' => $twitter,
            'facebook' => $facebook,
            'instagram' => $instagram
        ]);
        
        // Update user record
        $stmt = $pdo->prepare("
            UPDATE users SET 
                company_name = ?,
                bio = ?,
                phone = ?,
                email = ?,
                location = ?,
                city = ?,
                social_links = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $company_name,
            $company_description,
            $phone,
            $email,
            $location,
            $city,
            $social_links,
            $user_id
        ]);
        
        // Check if we need to add custom fields for business (industry, company_size, website)
        // For now, we'll store them in the bio field or create a separate business_profiles table
        // Let's add them to the database if columns exist
        try {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    headline = ?
                WHERE id = ?
            ");
            $headline = "$industry | $company_size موظف";
            $stmt->execute([$headline, $user_id]);
        } catch(PDOException $e) {
            // Column might not exist, skip
        }
        
        setFlash('تم تحديث الملف التعريفي بنجاح!', 'success');
        header('Location: company-profile.php');
        exit;
        
    } catch(PDOException $e) {
        setFlash('حدث خطأ أثناء التحديث: ' . $e->getMessage(), 'danger');
    }
}

// Get company performance data
$performance_stats = [
    'total_jobs' => 0,
    'active_jobs' => 0,
    'total_hired' => 0,
    'avg_rating' => 0
];

try {
    // Total jobs posted
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE business_id = ?");
    $stmt->execute([$user_id]);
    $performance_stats['total_jobs'] = $stmt->fetch()['count'];
    
    // Active jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE business_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $performance_stats['active_jobs'] = $stmt->fetch()['count'];
    
    // Total hired
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT fa.freelancer_id) as count 
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE j.business_id = ? AND fa.status = 'accepted'
    ");
    $stmt->execute([$user_id]);
    $performance_stats['total_hired'] = $stmt->fetch()['count'];
    
    // Average rating (we'll calculate based on completed jobs feedback)
    $performance_stats['avg_rating'] = 4.8; // Placeholder
    
} catch(PDOException $e) {
    // Keep default values
}

// Get jobs by month for chart (last 6 months)
$jobs_timeline = ['labels' => [], 'data' => []];
try {
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM jobs
        WHERE business_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $jobs_data = $stmt->fetchAll();
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M', strtotime("-$i months"));
        $jobs_timeline['labels'][] = $month_name;
        
        $count = 0;
        foreach ($jobs_data as $row) {
            if ($row['month'] == $month) {
                $count = $row['count'];
                break;
            }
        }
        $jobs_timeline['data'][] = $count;
    }
} catch(PDOException $e) {
    for ($i = 5; $i >= 0; $i--) {
        $jobs_timeline['labels'][] = date('M', strtotime("-$i months"));
        $jobs_timeline['data'][] = 0;
    }
}

// Parse existing social links
$social_links = json_decode($user['social_links'] ?? '{}', true);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الملف التعريفي للشركة - <?= SITE_NAME ?></title>
    
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
            color: white;
        }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .stat-card p {
            color: #999;
            margin: 0;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Content Layout */
        .content-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        /* Company Card */
        .company-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            text-align: center;
            height: fit-content;
        }
        
        .company-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--success-color));
            color: white;
            font-size: 48px;
            font-weight: 700;
            position: relative;
            overflow: hidden;
        }
        
        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
        }
        
        .upload-logo-btn {
            margin-top: 10px;
            padding: 8px 20px;
            background: var(--neumorphic-bg);
            color: var(--primary-color);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .upload-logo-btn:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .company-tagline {
            font-size: 13px;
            color: #999;
            margin-bottom: 15px;
        }
        
        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 15px;
            background: #e8f5e9;
            color: var(--success-color);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 
                inset 2px 2px 4px rgba(0,0,0,0.1),
                inset -2px -2px 4px rgba(255,255,255,0.7);
            margin-bottom: 20px;
        }
        
        .company-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .mini-stat {
            background: var(--neumorphic-bg);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
        }
        
        .mini-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .mini-stat-label {
            font-size: 11px;
            color: #999;
            font-weight: 600;
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
        
        textarea.form-control-neumorphic {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 14px;
        }
        
        .input-icon .form-control-neumorphic {
            padding-left: 40px;
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
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 
                6px 6px 18px rgba(23, 79, 132, 0.4),
                -3px -3px 10px rgba(255, 255, 255, 0.9);
        }
        
        .btn-cancel {
            background: var(--neumorphic-bg);
            color: #666;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        /* Chart Section */
        .chart-section {
            margin-top: 25px;
        }
        
        .chart-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .chart-card h4 {
            margin: 0 0 20px 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
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
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .content-layout { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
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
            <a href="company-profile.php" class="active">
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
                <h2>الملف التعريفي للشركة 🏢</h2>
                <p>قم بتحديث معلومات شركتك لجذب أفضل المستقلين</p>
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
        
        <!-- Performance Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3><?= $performance_stats['total_jobs'] ?></h3>
                <p>إجمالي الوظائف</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3><?= $performance_stats['active_jobs'] ?></h3>
                <p>وظائف نشطة</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?= $performance_stats['total_hired'] ?></h3>
                <p>مستقلين معينين</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-star"></i>
                </div>
                <h3><?= number_format($performance_stats['avg_rating'], 1) ?></h3>
                <p>التقييم</p>
            </div>
        </div>
        
        <!-- Content Layout -->
        <div class="content-layout">
            
            <!-- Company Card (Left Side) -->
            <div class="company-card">
                <div class="company-logo">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Company Logo">
                    <?php else: ?>
                        <?= strtoupper(substr($user['company_name'] ?? $user['first_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <button class="upload-logo-btn" onclick="alert('سيتم إضافة رفع الشعار قريباً')">
                    <i class="fas fa-camera ml-1"></i>
                    تحديث الشعار
                </button>
                
                <div style="margin: 20px 0;">
                    <div class="company-name">
                        <?= htmlspecialchars($user['company_name'] ?? ($user['first_name'] . ' ' . $user['last_name'])) ?>
                    </div>
                    <div class="company-tagline">
                        <?= htmlspecialchars($user['headline'] ?? 'شركة رائدة في المجال') ?>
                    </div>
                    <span class="verification-badge">
                        <i class="fas fa-check-circle"></i>
                        شركة موثقة
                    </span>
                </div>
                
                <div class="company-stats">
                    <div class="mini-stat">
                        <div class="mini-stat-value"><?= date('Y') - 2020 ?></div>
                        <div class="mini-stat-label">سنوات الخبرة</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-value"><?= $performance_stats['total_hired'] ?>+</div>
                        <div class="mini-stat-label">مشاريع مكتملة</div>
                    </div>
                </div>
            </div>
            
            <!-- Form Card (Right Side) -->
            <div class="form-card">
                <h4>
                    <i class="fas fa-edit" style="color: var(--primary-color);"></i>
                    تحديث معلومات الشركة
                </h4>
                
                <form method="POST" action="">
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="section-title">معلومات أساسية</div>
                        
                        <div class="form-group">
                            <label>
                                اسم الشركة <span class="required">*</span>
                            </label>
                            <input type="text" name="company_name" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['company_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                نبذة عن الشركة <span class="required">*</span>
                            </label>
                            <textarea name="company_description" class="form-control-neumorphic" 
                                      required><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>المجال <span class="required">*</span></label>
                                <select name="industry" class="form-control-neumorphic" required>
                                    <option value="">اختر المجال</option>
                                    <option value="تقنية المعلومات">تقنية المعلومات</option>
                                    <option value="التسويق">التسويق</option>
                                    <option value="التصميم">التصميم</option>
                                    <option value="الكتابة والترجمة">الكتابة والترجمة</option>
                                    <option value="الاستشارات">الاستشارات</option>
                                    <option value="التعليم">التعليم</option>
                                    <option value="التجارة الإلكترونية">التجارة الإلكترونية</option>
                                    <option value="الصحة">الصحة</option>
                                    <option value="المالية">المالية</option>
                                    <option value="أخرى">أخرى</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>عدد الموظفين <span class="required">*</span></label>
                                <select name="company_size" class="form-control-neumorphic" required>
                                    <option value="">اختر الحجم</option>
                                    <option value="1-10">1-10 موظفين</option>
                                    <option value="11-50">11-50 موظف</option>
                                    <option value="51-200">51-200 موظف</option>
                                    <option value="201-500">201-500 موظف</option>
                                    <option value="500+">أكثر من 500 موظف</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="form-section">
                        <div class="section-title">معلومات الاتصال</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>البريد الإلكتروني <span class="required">*</span></label>
                                <div class="input-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control-neumorphic" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>رقم الهاتف <span class="required">*</span></label>
                                <div class="input-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="phone" class="form-control-neumorphic" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>الموقع الإلكتروني</label>
                            <div class="input-icon">
                                <i class="fas fa-globe"></i>
                                <input type="url" name="website" class="form-control-neumorphic" 
                                       value="" placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="form-section">
                        <div class="section-title">الموقع</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>الدولة <span class="required">*</span></label>
                                <select name="location" class="form-control-neumorphic" required>
                                    <option value="">اختر الدولة</option>
                                    <option value="السعودية" selected>السعودية</option>
                                    <option value="الإمارات">الإمارات</option>
                                    <option value="الكويت">الكويت</option>
                                    <option value="قطر">قطر</option>
                                    <option value="البحرين">البحرين</option>
                                    <option value="عمان">عمان</option>
                                    <option value="مصر">مصر</option>
                                    <option value="الأردن">الأردن</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>المدينة <span class="required">*</span></label>
                                <input type="text" name="city" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['city'] ?? '') ?>" 
                                       placeholder="الرياض" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="form-section">
                        <div class="section-title">روابط التواصل الاجتماعي</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>لينكدإن</label>
                                <div class="input-icon">
                                    <i class="fab fa-linkedin"></i>
                                    <input type="url" name="linkedin" class="form-control-neumorphic" 
                                           value="<?= htmlspecialchars($social_links['linkedin'] ?? '') ?>" 
                                           placeholder="https://linkedin.com/company/...">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>تويتر</label>
                                <div class="input-icon">
                                    <i class="fab fa-twitter"></i>
                                    <input type="url" name="twitter" class="form-control-neumorphic" 
                                           value="<?= htmlspecialchars($social_links['twitter'] ?? '') ?>" 
                                           placeholder="https://twitter.com/...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>فيسبوك</label>
                                <div class="input-icon">
                                    <i class="fab fa-facebook"></i>
                                    <input type="url" name="facebook" class="form-control-neumorphic" 
                                           value="<?= htmlspecialchars($social_links['facebook'] ?? '') ?>" 
                                           placeholder="https://facebook.com/...">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>إنستغرام</label>
                                <div class="input-icon">
                                    <i class="fab fa-instagram"></i>
                                    <input type="url" name="instagram" class="form-control-neumorphic" 
                                           value="<?= htmlspecialchars($social_links['instagram'] ?? '') ?>" 
                                           placeholder="https://instagram.com/...">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save ml-1"></i>
                            حفظ التغييرات
                        </button>
                        <a href="dashboard.php" class="btn-cancel">
                            إلغاء
                        </a>
                    </div>
                    
                </form>
            </div>
            
        </div>
        
        <!-- Performance Chart -->
        <div class="chart-section">
            <div class="chart-card">
                <h4>
                    <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
                    أداء نشر الوظائف (آخر 6 أشهر)
                </h4>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($jobs_timeline['labels']) ?>,
                datasets: [{
                    label: 'الوظائف المنشورة',
                    data: <?= json_encode($jobs_timeline['data']) ?>,
                    backgroundColor: 'rgba(23, 79, 132, 0.8)',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>