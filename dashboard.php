<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Get business statistics
$stats = [
    'active_jobs' => 0,
    'total_applications' => 0,
    'hired_freelancers' => 0,
    'total_spent' => 0
];

try {
    // Active jobs count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE business_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $stats['active_jobs'] = $stmt->fetch()['count'];
    
    // Total applications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE j.business_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats['total_applications'] = $stmt->fetch()['count'];
    
    // Hired freelancers (accepted applications)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE j.business_id = ? AND fa.status = 'accepted'
    ");
    $stmt->execute([$user_id]);
    $stats['hired_freelancers'] = $stmt->fetch()['count'];
    
    // Total spent (from earnings table)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(e.amount), 0) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'paid'
    ");
    $stmt->execute([$user_id]);
    $stats['total_spent'] = $stmt->fetch()['total'];
    
} catch(PDOException $e) {
    // Keep default values
}

// Get jobs data for last 6 months (for chart)
$jobs_chart_data = ['labels' => [], 'data' => []];
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
    
    // Fill last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M', strtotime("-$i months"));
        $jobs_chart_data['labels'][] = $month_name;
        
        $count = 0;
        foreach ($jobs_data as $row) {
            if ($row['month'] == $month) {
                $count = $row['count'];
                break;
            }
        }
        $jobs_chart_data['data'][] = $count;
    }
} catch(PDOException $e) {
    for ($i = 5; $i >= 0; $i--) {
        $jobs_chart_data['labels'][] = date('M', strtotime("-$i months"));
        $jobs_chart_data['data'][] = 0;
    }
}

// Get application status breakdown (for pie chart)
$applications_breakdown = ['pending' => 0, 'accepted' => 0, 'rejected' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT fa.status, COUNT(*) as count
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE j.business_id = ?
        GROUP BY fa.status
    ");
    $stmt->execute([$user_id]);
    $breakdown = $stmt->fetchAll();
    foreach ($breakdown as $row) {
        $applications_breakdown[$row['status']] = $row['count'];
    }
} catch(PDOException $e) {
    // Keep default values
}

// Get spending data for last 6 months (for chart)
$spending_chart_data = ['labels' => [], 'data' => []];
try {
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(e.paid_at, '%Y-%m') as month, SUM(e.amount) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'paid' AND e.paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $spending_data = $stmt->fetchAll();
    
    // Fill last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M', strtotime("-$i months"));
        $spending_chart_data['labels'][] = $month_name;
        
        $amount = 0;
        foreach ($spending_data as $row) {
            if ($row['month'] == $month) {
                $amount = $row['total'];
                break;
            }
        }
        $spending_chart_data['data'][] = floatval($amount);
    }
} catch(PDOException $e) {
    for ($i = 5; $i >= 0; $i--) {
        $spending_chart_data['labels'][] = date('M', strtotime("-$i months"));
        $spending_chart_data['data'][] = 0;
    }
}

// Get recent jobs
try {
    $stmt = $pdo->prepare("
        SELECT j.*,
               (SELECT COUNT(*) FROM freelancer_applications WHERE job_id = j.id) as application_count
        FROM jobs j
        WHERE j.business_id = ?
        ORDER BY j.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_jobs = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_jobs = [];
}

// Get recent applications
try {
    $stmt = $pdo->prepare("
        SELECT fa.*, 
               j.title as job_title,
               u.first_name, u.last_name, u.avatar, u.headline, u.rating
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        JOIN users u ON fa.freelancer_id = u.id
        WHERE j.business_id = ?
        ORDER BY fa.applied_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_applications = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_applications = [];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة التحكم - <?= SITE_NAME ?></title>
    
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
        
        .nav-menu .badge {
            margin-right: auto;
            font-size: 10px;
            padding: 2px 7px;
            box-shadow: 
                2px 2px 4px var(--neumorphic-dark),
                -2px -2px 4px var(--neumorphic-light);
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
        
        /* Stats Grid */
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
        
        /* Charts Section */
        .charts-section {
            margin-bottom: 25px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
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
        
        .chart-container-small {
            position: relative;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .content-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .content-card-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .content-card-header a {
            font-size: 12px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .content-card-header a:hover {
            text-decoration: underline;
        }
        
        /* Job Item */
        .job-item {
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .job-item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }
        
        .job-title {
            font-weight: 700;
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .job-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .job-meta i {
            color: #999;
            margin-left: 4px;
        }
        
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 
                inset 2px 2px 4px rgba(0,0,0,0.1),
                inset -2px -2px 4px rgba(255,255,255,0.7);
        }
        
        .badge-active { background: #e8f5e9; color: var(--success-color); }
        .badge-filled { background: #e3f2fd; color: var(--primary-color); }
        .badge-closed { background: #ffebee; color: var(--danger-color); }
        
        /* Applicant Item */
        .applicant-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .applicant-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 
                3px 3px 6px var(--neumorphic-dark),
                -3px -3px 6px var(--neumorphic-light);
        }
        
        .applicant-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .applicant-info {
            flex: 1;
        }
        
        .applicant-name {
            font-weight: 700;
            font-size: 14px;
            color: #333;
            margin-bottom: 3px;
        }
        
        .applicant-headline {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .applicant-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        
        .applicant-rating .stars {
            color: #FFD700;
        }
        
        .applicant-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm-neumorphic {
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-accept {
            background: var(--success-color);
            color: white;
            box-shadow: 
                3px 3px 6px rgba(0, 191, 154, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .btn-reject {
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                3px 3px 6px var(--neumorphic-dark),
                -3px -3px 6px var(--neumorphic-light);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            font-size: 13px;
            line-height: 1.6;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
            .content-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
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
            <a href="dashboard.php" class="active">
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
                <?php if ($stats['total_applications'] > 0): ?>
                    <span class="badge bg-warning"><?= $stats['total_applications'] ?></span>
                <?php endif; ?>
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
                <h2>مرحباً، <?= htmlspecialchars($user['company_name'] ?? $user['first_name']) ?>! 👋</h2>
                <p>إليك نظرة سريعة على نشاطك اليوم</p>
            </div>
            <a href="post-job.php" class="btn-primary-neumorphic">
                <i class="fas fa-plus"></i>
                نشر وظيفة جديدة
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3><?= $stats['active_jobs'] ?></h3>
                <p>الوظائف النشطة</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3><?= $stats['total_applications'] ?></h3>
                <p>الطلبات المستلمة</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-user-check"></i>
                </div>
                <h3><?= $stats['hired_freelancers'] ?></h3>
                <p>مستقلين معينين</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3><?= formatMoney($stats['total_spent']) ?></h3>
                <p>إجمالي الإنفاق</p>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
            <div class="charts-grid">
                <!-- Jobs Over Time Chart -->
                <div class="chart-card">
                    <h4>
                        <i class="fas fa-chart-line" style="color: var(--primary-color);"></i>
                        الوظائف المنشورة (آخر 6 أشهر)
                    </h4>
                    <div class="chart-container">
                        <canvas id="jobsChart"></canvas>
                    </div>
                </div>
                
                <!-- Applications Status Breakdown -->
                <div class="chart-card">
                    <h4>
                        <i class="fas fa-chart-pie" style="color: var(--success-color);"></i>
                        حالة الطلبات
                    </h4>
                    <div class="chart-container-small">
                        <canvas id="applicationsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Spending Chart -->
            <div class="chart-card">
                <h4>
                    <i class="fas fa-chart-bar" style="color: var(--warning-color);"></i>
                    الإنفاق الشهري (آخر 6 أشهر)
                </h4>
                <div class="chart-container">
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            
            <!-- Recent Jobs -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-briefcase ml-2"></i>الوظائف الأخيرة</h4>
                    <a href="jobs.php">عرض الكل <i class="fas fa-arrow-left mr-1"></i></a>
                </div>
                
                <?php if (empty($recent_jobs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <p>لم تنشر أي وظائف بعد<br>ابدأ بنشر أول وظيفة لك!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_jobs as $job): ?>
                        <div class="job-item">
                            <div class="job-item-header">
                                <div>
                                    <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                                    <div class="job-meta">
                                        <span><i class="fas fa-calendar"></i><?= timeAgo($job['created_at']) ?></span>
                                        <span><i class="fas fa-users"></i><?= $job['application_count'] ?> متقدم</span>
                                    </div>
                                </div>
                                <span class="badge-status badge-<?= $job['status'] ?>">
                                    <?= $job['status'] == 'active' ? 'نشط' : ($job['status'] == 'filled' ? 'مكتمل' : 'مغلق') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Recent Applications -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-users ml-2"></i>أحدث المتقدمين</h4>
                    <a href="applications.php">عرض الكل <i class="fas fa-arrow-left mr-1"></i></a>
                </div>
                
                <?php if (empty($recent_applications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>لا توجد طلبات بعد<br>سيظهر هنا المتقدمون على وظائفك</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_applications as $app): ?>
                        <div class="applicant-item">
                            <div class="applicant-avatar">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($app['first_name']) ?>&background=667eea&color=fff&size=90" 
                                     alt="<?= htmlspecialchars($app['first_name']) ?>">
                            </div>
                            <div class="applicant-info">
                                <div class="applicant-name">
                                    <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                                </div>
                                <div class="applicant-headline">
                                    <?= htmlspecialchars($app['headline'] ?? 'مستقل محترف') ?>
                                </div>
                                <div class="applicant-rating">
                                    <span class="stars">
                                        <?php for($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?= $i < floor($app['rating']) ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span><?= number_format($app['rating'], 1) ?></span>
                                </div>
                            </div>
                            <?php if ($app['status'] == 'pending'): ?>
                                <div class="applicant-actions">
                                    <button class="btn-sm-neumorphic btn-accept" 
                                            onclick="alert('سيتم إضافة نظام قبول/رفض المتقدمين قريباً')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn-sm-neumorphic btn-reject" 
                                            onclick="alert('سيتم إضافة نظام قبول/رفض المتقدمين قريباً')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="badge-status badge-<?= $app['status'] == 'accepted' ? 'active' : 'closed' ?>">
                                    <?= $app['status'] == 'accepted' ? 'مقبول' : 'مرفوض' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Jobs Over Time Chart
        const jobsCtx = document.getElementById('jobsChart').getContext('2d');
        new Chart(jobsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($jobs_chart_data['labels']) ?>,
                datasets: [{
                    label: 'الوظائف المنشورة',
                    data: <?= json_encode($jobs_chart_data['data']) ?>,
                    borderColor: '#174F84',
                    backgroundColor: 'rgba(23, 79, 132, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3
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

        // Applications Status Chart
        const applicationsCtx = document.getElementById('applicationsChart').getContext('2d');
        new Chart(applicationsCtx, {
            type: 'doughnut',
            data: {
                labels: ['قيد المراجعة', 'مقبول', 'مرفوض'],
                datasets: [{
                    data: [
                        <?= $applications_breakdown['pending'] ?>,
                        <?= $applications_breakdown['accepted'] ?>,
                        <?= $applications_breakdown['rejected'] ?>
                    ],
                    backgroundColor: [
                        '#F5B759',
                        '#00BF9A',
                        '#FA5252'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            padding: 15,
                            font: {
                                family: 'Cairo',
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Spending Chart
        const spendingCtx = document.getElementById('spendingChart').getContext('2d');
        new Chart(spendingCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($spending_chart_data['labels']) ?>,
                datasets: [{
                    label: 'الإنفاق (ر.س)',
                    data: <?= json_encode($spending_chart_data['data']) ?>,
                    backgroundColor: 'rgba(0, 191, 154, 0.8)',
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
                            callback: function(value) {
                                return value.toLocaleString('ar-SA') + ' ر.س';
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>