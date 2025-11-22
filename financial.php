<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Get financial statistics
$financial_stats = [
    'total_spent' => 0,
    'this_month' => 0,
    'pending_payments' => 0,
    'completed_transactions' => 0
];

try {
    // Total spent (all time)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(e.amount), 0) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'paid'
    ");
    $stmt->execute([$user_id]);
    $financial_stats['total_spent'] = $stmt->fetch()['total'];
    
    // This month spending
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(e.amount), 0) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? 
        AND e.status = 'paid'
        AND MONTH(e.paid_at) = MONTH(CURRENT_DATE())
        AND YEAR(e.paid_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$user_id]);
    $financial_stats['this_month'] = $stmt->fetch()['total'];
    
    // Pending payments
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(e.amount), 0) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $financial_stats['pending_payments'] = $stmt->fetch()['total'];
    
    // Completed transactions count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'paid'
    ");
    $stmt->execute([$user_id]);
    $financial_stats['completed_transactions'] = $stmt->fetch()['count'];
    
} catch(PDOException $e) {
    // Keep default values
}

// Get monthly spending data (last 12 months)
$monthly_spending = ['labels' => [], 'data' => []];
try {
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(e.paid_at, '%Y-%m') as month, SUM(e.amount) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'paid' AND e.paid_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $spending_data = $stmt->fetchAll();
    
    // Fill last 12 months
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));
        $monthly_spending['labels'][] = $month_name;
        
        $amount = 0;
        foreach ($spending_data as $row) {
            if ($row['month'] == $month) {
                $amount = $row['total'];
                break;
            }
        }
        $monthly_spending['data'][] = floatval($amount);
    }
} catch(PDOException $e) {
    for ($i = 11; $i >= 0; $i--) {
        $monthly_spending['labels'][] = date('M Y', strtotime("-$i months"));
        $monthly_spending['data'][] = 0;
    }
}

// Get spending by category (based on job categories)
$category_spending = [];
try {
    $stmt = $pdo->prepare("
        SELECT j.category, SUM(e.amount) as total
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        WHERE j.business_id = ? AND e.status = 'paid'
        GROUP BY j.category
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $category_spending = $stmt->fetchAll();
} catch(PDOException $e) {
    $category_spending = [];
}

// Get recent transactions
try {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               j.title as job_title,
               u.first_name, u.last_name
        FROM earnings e
        JOIN jobs j ON e.job_id = j.id
        JOIN users u ON e.freelancer_id = u.id
        WHERE j.business_id = ?
        ORDER BY e.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $transactions = [];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المالية - <?= SITE_NAME ?></title>
    
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
        
        .stat-change {
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-change.positive {
            color: var(--success-color);
        }
        
        .stat-change.negative {
            color: var(--danger-color);
        }
        
        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
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
            height: 300px;
        }
        
        /* Transactions Table */
        .transactions-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .transactions-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 6px 15px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                inset 4px 4px 8px rgba(0,0,0,0.2),
                inset -2px -2px 4px rgba(255,255,255,0.1);
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        
        .transactions-table thead th {
            padding: 10px 15px;
            text-align: right;
            font-size: 12px;
            font-weight: 600;
            color: #999;
            border: none;
        }
        
        .transactions-table tbody tr {
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            border-radius: 10px;
        }
        
        .transactions-table tbody td {
            padding: 15px;
            border: none;
            font-size: 13px;
        }
        
        .transactions-table tbody tr td:first-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        
        .transactions-table tbody tr td:last-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        
        .freelancer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .freelancer-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            box-shadow: 
                2px 2px 4px var(--neumorphic-dark),
                -2px -2px 4px var(--neumorphic-light);
        }
        
        .freelancer-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .freelancer-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }
        
        .job-title-small {
            font-size: 11px;
            color: #999;
        }
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 
                inset 2px 2px 4px rgba(0,0,0,0.1),
                inset -2px -2px 4px rgba(255,255,255,0.7);
        }
        
        .badge-paid { background: #e8f5e9; color: var(--success-color); }
        .badge-pending { background: #fff3e0; color: var(--warning-color); }
        
        .amount-cell {
            font-weight: 700;
            color: #333;
            font-size: 14px;
        }
        
        /* Category Spending List */
        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .category-item {
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .category-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .category-name {
            font-weight: 700;
            font-size: 14px;
            color: #333;
        }
        
        .category-amount {
            font-weight: 700;
            font-size: 14px;
            color: var(--primary-color);
        }
        
        .category-bar {
            height: 6px;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
        }
        
        .category-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h5 {
            font-size: 16px;
            font-weight: 700;
            color: #666;
            margin-bottom: 8px;
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
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
            .transactions-table { font-size: 11px; }
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
            <a href="financial.php" class="active">
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
                <h2>المالية 💰</h2>
                <p>تتبع المدفوعات والمعاملات المالية</p>
            </div>
            <a href="dashboard.php" class="btn-primary-neumorphic">
                <i class="fas fa-arrow-right"></i>
                العودة للوحة التحكم
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Financial Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-coins"></i>
                </div>
                <h3><?= formatMoney($financial_stats['total_spent']) ?></h3>
                <p>إجمالي الإنفاق</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3><?= formatMoney($financial_stats['this_month']) ?></h3>
                <p>إنفاق هذا الشهر</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?= formatMoney($financial_stats['pending_payments']) ?></h3>
                <p>مدفوعات قيد الانتظار</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?= $financial_stats['completed_transactions'] ?></h3>
                <p>معاملات مكتملة</p>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Monthly Spending Chart -->
            <div class="chart-card">
                <h4>
                    <i class="fas fa-chart-area" style="color: var(--primary-color);"></i>
                    الإنفاق الشهري (آخر 12 شهر)
                </h4>
                <div class="chart-container">
                    <canvas id="monthlySpendingChart"></canvas>
                </div>
            </div>
            
            <!-- Category Spending -->
            <div class="chart-card">
                <h4>
                    <i class="fas fa-layer-group" style="color: var(--success-color);"></i>
                    الإنفاق حسب التصنيف
                </h4>
                <?php if (empty($category_spending)): ?>
                    <div class="empty-state" style="padding: 40px 20px;">
                        <i class="fas fa-chart-pie" style="font-size: 40px;"></i>
                        <p>لا توجد بيانات بعد</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $max_amount = max(array_column($category_spending, 'total'));
                    foreach ($category_spending as $cat): 
                        $percentage = ($max_amount > 0) ? ($cat['total'] / $max_amount) * 100 : 0;
                    ?>
                        <div class="category-item">
                            <div class="category-item-header">
                                <span class="category-name"><?= htmlspecialchars($cat['category'] ?? 'غير محدد') ?></span>
                                <span class="category-amount"><?= formatMoney($cat['total']) ?></span>
                            </div>
                            <div class="category-bar">
                                <div class="category-bar-fill" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="transactions-card">
            <div class="transactions-header">
                <h4><i class="fas fa-list ml-2"></i>سجل المعاملات</h4>
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterTransactions('all')">الكل</button>
                    <button class="filter-tab" onclick="filterTransactions('paid')">مدفوع</button>
                    <button class="filter-tab" onclick="filterTransactions('pending')">قيد الانتظار</button>
                </div>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h5>لا توجد معاملات بعد</h5>
                    <p>سيظهر هنا سجل جميع المعاملات المالية<br>الخاصة بمشاريعك</p>
                </div>
            <?php else: ?>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>المستقل</th>
                            <th>المشروع</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): ?>
                            <tr class="transaction-row" data-status="<?= $trans['status'] ?>">
                                <td>
                                    <div class="freelancer-info">
                                        <div class="freelancer-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($trans['first_name']) ?>&background=667eea&color=fff&size=70" 
                                                 alt="<?= htmlspecialchars($trans['first_name']) ?>">
                                        </div>
                                        <div>
                                            <div class="freelancer-name">
                                                <?= htmlspecialchars($trans['first_name'] . ' ' . $trans['last_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="job-title-small"><?= htmlspecialchars($trans['job_title']) ?></div>
                                </td>
                                <td class="amount-cell"><?= formatMoney($trans['amount']) ?></td>
                                <td>
                                    <span class="badge-status badge-<?= $trans['status'] ?>">
                                        <?= $trans['status'] == 'paid' ? 'مدفوع' : 'قيد الانتظار' ?>
                                    </span>
                                </td>
                                <td style="color: #999; font-size: 12px;">
                                    <?= timeAgo($trans['created_at']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Monthly Spending Chart
        const monthlyCtx = document.getElementById('monthlySpendingChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthly_spending['labels']) ?>,
                datasets: [{
                    label: 'الإنفاق (ر.س)',
                    data: <?= json_encode($monthly_spending['data']) ?>,
                    borderColor: '#174F84',
                    backgroundColor: 'rgba(23, 79, 132, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#174F84',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString('ar-SA') + ' ر.س';
                            }
                        }
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

        // Filter transactions
        function filterTransactions(status) {
            const rows = document.querySelectorAll('.transaction-row');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    row.style.display = row.dataset.status === status ? '' : 'none';
                }
            });
        }
    </script>

</body>
</html>