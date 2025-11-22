<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle job actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $job_id = intval($_POST['job_id']);
        
        try {
            // Verify job belongs to user
            $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND business_id = ?");
            $stmt->execute([$job_id, $user_id]);
            
            if ($stmt->fetch()) {
                switch ($_POST['action']) {
                    case 'close':
                        $stmt = $pdo->prepare("UPDATE jobs SET status = 'closed' WHERE id = ?");
                        $stmt->execute([$job_id]);
                        setFlash('تم إغلاق الوظيفة بنجاح', 'success');
                        break;
                        
                    case 'reopen':
                        $stmt = $pdo->prepare("UPDATE jobs SET status = 'active' WHERE id = ?");
                        $stmt->execute([$job_id]);
                        setFlash('تم إعادة فتح الوظيفة بنجاح', 'success');
                        break;
                        
                    case 'delete':
                        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                        $stmt->execute([$job_id]);
                        setFlash('تم حذف الوظيفة بنجاح', 'success');
                        break;
                        
                    case 'mark_filled':
                        $stmt = $pdo->prepare("UPDATE jobs SET status = 'filled' WHERE id = ?");
                        $stmt->execute([$job_id]);
                        setFlash('تم تحديد الوظيفة كمكتملة', 'success');
                        break;
                }
            }
            
            header('Location: jobs.php');
            exit;
            
        } catch(PDOException $e) {
            setFlash('حدث خطأ: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get filter
$filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT j.*,
           (SELECT COUNT(*) FROM freelancer_applications WHERE job_id = j.id) as applications_count,
           (SELECT COUNT(*) FROM freelancer_applications WHERE job_id = j.id AND status = 'pending') as pending_count
    FROM jobs j
    WHERE j.business_id = ?
";

$params = [$user_id];

if ($filter !== 'all') {
    $query .= " AND j.status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY j.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    // Get counts for filters
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM jobs WHERE business_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $status_counts = [];
    $total_count = 0;
    foreach ($stmt->fetchAll() as $row) {
        $status_counts[$row['status']] = $row['count'];
        $total_count += $row['count'];
    }
    
} catch(PDOException $e) {
    $jobs = [];
    $status_counts = [];
    $total_count = 0;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الوظائف - <?= SITE_NAME ?></title>
    
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
        
        /* Filter Bar */
        .filter-bar {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 20px;
            font-size: 13px;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-tab:hover {
            color: var(--primary-color);
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                inset 4px 4px 8px rgba(0,0,0,0.2),
                inset -2px -2px 4px rgba(255,255,255,0.1);
        }
        
        .filter-tab .count {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .filter-tab.active .count {
            background: rgba(255,255,255,0.3);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .search-box input:focus {
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        /* Jobs Grid */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .job-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            transition: all 0.3s;
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 
                10px 10px 20px var(--neumorphic-dark),
                -10px -10px 20px var(--neumorphic-light);
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .job-status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 
                inset 2px 2px 4px rgba(0,0,0,0.1),
                inset -2px -2px 4px rgba(255,255,255,0.7);
        }
        
        .status-active { background: #e8f5e9; color: var(--success-color); }
        .status-filled { background: #e3f2fd; color: var(--primary-color); }
        .status-closed { background: #ffebee; color: var(--danger-color); }
        .status-draft { background: #fff3e0; color: var(--warning-color); }
        
        .job-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .job-description {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .job-meta-item i {
            color: var(--primary-color);
        }
        
        .job-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .job-stat {
            text-align: center;
        }
        
        .job-stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 3px;
        }
        
        .job-stat-label {
            font-size: 10px;
            color: #999;
            font-weight: 600;
        }
        
        .job-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            flex: 1;
            padding: 8px 15px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .btn-view {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                3px 3px 6px rgba(23, 79, 132, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .btn-view:hover {
            transform: translateY(-1px);
            color: white;
        }
        
        .btn-edit {
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                3px 3px 6px var(--neumorphic-dark),
                -3px -3px 6px var(--neumorphic-light);
        }
        
        .btn-edit:hover {
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
        }
        
        .job-dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            background: var(--neumorphic-bg);
            color: #666;
            cursor: pointer;
            box-shadow: 
                3px 3px 6px var(--neumorphic-dark),
                -3px -3px 6px var(--neumorphic-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropdown-menu-custom {
            position: absolute;
            top: 40px;
            left: 0;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            padding: 5px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            min-width: 150px;
            z-index: 100;
            display: none;
        }
        
        .dropdown-menu-custom.show {
            display: block;
        }
        
        .dropdown-item-custom {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .dropdown-item-custom:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .dropdown-item-custom i {
            width: 16px;
        }
        
        .dropdown-item-custom.danger {
            color: var(--danger-color);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 20px;
            font-weight: 700;
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
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
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            max-width: 400px;
            width: 90%;
            box-shadow: 
                15px 15px 30px var(--neumorphic-dark),
                -15px -15px 30px var(--neumorphic-light);
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .modal-header p {
            font-size: 13px;
            color: #999;
            margin: 0;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .modal-actions button {
            flex: 1;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .jobs-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            .filter-tabs {
                flex-direction: column;
            }
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
            <a href="jobs.php" class="active">
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
                <h2>إدارة الوظائف 💼</h2>
                <p>عرض وإدارة جميع الوظائف المنشورة</p>
            </div>
            <a href="post-job.php" class="btn-primary-neumorphic">
                <i class="fas fa-plus"></i>
                نشر وظيفة جديدة
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    الكل
                    <span class="count"><?= $total_count ?></span>
                </a>
                <a href="?status=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i>
                    نشط
                    <span class="count"><?= $status_counts['active'] ?? 0 ?></span>
                </a>
                <a href="?status=filled" class="filter-tab <?= $filter === 'filled' ? 'active' : '' ?>">
                    <i class="fas fa-user-check"></i>
                    مكتمل
                    <span class="count"><?= $status_counts['filled'] ?? 0 ?></span>
                </a>
                <a href="?status=closed" class="filter-tab <?= $filter === 'closed' ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i>
                    مغلق
                    <span class="count"><?= $status_counts['closed'] ?? 0 ?></span>
                </a>
                <a href="?status=draft" class="filter-tab <?= $filter === 'draft' ? 'active' : '' ?>">
                    <i class="fas fa-file"></i>
                    مسودة
                    <span class="count"><?= $status_counts['draft'] ?? 0 ?></span>
                </a>
            </div>
            
            <div class="search-box">
                <form method="GET" action="">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
                    <input type="text" name="search" placeholder="ابحث عن وظيفة..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <i class="fas fa-search"></i>
                </form>
            </div>
        </div>
        
        <!-- Jobs Grid -->
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <i class="fas fa-briefcase"></i>
                <h3>لا توجد وظائف</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        لم يتم العثور على نتائج للبحث "<?= htmlspecialchars($search) ?>"
                    <?php elseif ($filter !== 'all'): ?>
                        لا توجد وظائف بحالة "<?= $filter ?>"
                    <?php else: ?>
                        لم تقم بنشر أي وظائف بعد<br>
                        ابدأ بنشر أول وظيفة لك لجذب أفضل المستقلين
                    <?php endif; ?>
                </p>
                <a href="post-job.php" class="btn-primary-neumorphic">
                    <i class="fas fa-plus"></i>
                    نشر وظيفة جديدة
                </a>
            </div>
        <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-card-header">
                            <span class="job-status-badge status-<?= $job['status'] ?>">
                                <?php
                                $status_labels = [
                                    'active' => 'نشط',
                                    'filled' => 'مكتمل',
                                    'closed' => 'مغلق',
                                    'draft' => 'مسودة'
                                ];
                                echo $status_labels[$job['status']] ?? $job['status'];
                                ?>
                            </span>
                            
                            <div class="job-dropdown">
                                <button class="dropdown-toggle" onclick="toggleDropdown(<?= $job['id'] ?>)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu-custom" id="dropdown-<?= $job['id'] ?>">
                                    <?php if ($job['status'] === 'active'): ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button type="submit" name="action" value="mark_filled" class="dropdown-item-custom">
                                                <i class="fas fa-check"></i>
                                                تحديد كمكتمل
                                            </button>
                                        </form>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button type="submit" name="action" value="close" class="dropdown-item-custom">
                                                <i class="fas fa-times"></i>
                                                إغلاق الوظيفة
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['status'] === 'closed' || $job['status'] === 'draft'): ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button type="submit" name="action" value="reopen" class="dropdown-item-custom">
                                                <i class="fas fa-redo"></i>
                                                إعادة فتح
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button class="dropdown-item-custom danger" 
                                            onclick="confirmDelete(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title']) ?>')">
                                        <i class="fas fa-trash"></i>
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                        
                        <div class="job-description">
                            <?= htmlspecialchars($job['description']) ?>
                        </div>
                        
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-folder"></i>
                                <?= htmlspecialchars($job['category'] ?? 'غير محدد') ?>
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($job['location'] ?? 'غير محدد') ?>
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-calendar"></i>
                                <?= timeAgo($job['created_at']) ?>
                            </div>
                        </div>
                        
                        <div class="job-stats">
                            <div class="job-stat">
                                <div class="job-stat-value"><?= $job['applications_count'] ?></div>
                                <div class="job-stat-label">متقدمين</div>
                            </div>
                            <div class="job-stat">
                                <div class="job-stat-value"><?= $job['pending_count'] ?></div>
                                <div class="job-stat-label">قيد المراجعة</div>
                            </div>
                            <div class="job-stat">
                                <div class="job-stat-value"><?= formatMoney($job['hourly_rate']) ?></div>
                                <div class="job-stat-label">الميزانية</div>
                            </div>
                        </div>
                        
                        <div class="job-actions">
                            <a href="applications.php?job_id=<?= $job['id'] ?>" class="btn-action btn-view">
                                <i class="fas fa-users"></i>
                                المتقدمين (<?= $job['applications_count'] ?>)
                            </a>
                            <button class="btn-action btn-edit" 
                                    onclick="alert('سيتم إضافة صفحة التعديل قريباً')">
                                <i class="fas fa-edit"></i>
                                تعديل
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-exclamation-triangle ml-2" style="color: var(--danger-color);"></i>تأكيد الحذف</h4>
                <p>هل أنت متأكد من حذف هذه الوظيفة؟</p>
            </div>
            <div style="background: #fff3cd; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                <p style="margin: 0; font-size: 12px; color: #856404;">
                    <strong>تحذير:</strong> لا يمكن التراجع عن هذا الإجراء
                </p>
            </div>
            <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                <strong id="jobTitleToDelete"></strong>
            </p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="job_id" id="jobIdToDelete">
                <div class="modal-actions">
                    <button type="button" class="btn-action btn-edit" onclick="closeDeleteModal()">
                        إلغاء
                    </button>
                    <button type="submit" name="action" value="delete" class="btn-action" 
                            style="background: var(--danger-color); color: white;">
                        <i class="fas fa-trash"></i>
                        حذف
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDropdown(jobId) {
            const dropdown = document.getElementById('dropdown-' + jobId);
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
                if (menu.id !== 'dropdown-' + jobId) {
                    menu.classList.remove('show');
                }
            });
            
            dropdown.classList.toggle('show');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.job-dropdown')) {
                document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        function confirmDelete(jobId, jobTitle) {
            document.getElementById('jobIdToDelete').value = jobId;
            document.getElementById('jobTitleToDelete').textContent = jobTitle;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>

</body>
</html>