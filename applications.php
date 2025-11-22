<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $application_id = intval($_POST['application_id']);
        
        try {
            // Verify application belongs to user's job
            $stmt = $pdo->prepare("
                SELECT fa.* 
                FROM freelancer_applications fa
                JOIN jobs j ON fa.job_id = j.id
                WHERE fa.id = ? AND j.business_id = ?
            ");
            $stmt->execute([$application_id, $user_id]);
            
            if ($stmt->fetch()) {
           switch ($_POST['action']) {
    case 'accept':
        // Get application details
        $stmt = $pdo->prepare("
            SELECT fa.*, j.business_id 
            FROM freelancer_applications fa
            JOIN jobs j ON fa.job_id = j.id
            WHERE fa.id = ?
        ");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();
        
        if ($application) {
            // Update application status
            $stmt = $pdo->prepare("UPDATE freelancer_applications SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$application_id]);
            
            // Create or get conversation_id (using application_id as conversation_id)
            // Check if conversation already exists
            $stmt = $pdo->prepare("
                SELECT id FROM messages 
                WHERE (sender_id = ? AND recipient_id = ?) 
                   OR (sender_id = ? AND recipient_id = ?)
                LIMIT 1
            ");
            $stmt->execute([
                $application['business_id'], 
                $application['freelancer_id'],
                $application['freelancer_id'],
                $application['business_id']
            ]);
            $existing = $stmt->fetch();
            
            // If no conversation exists, create initial message
            if (!$existing) {
                $initial_message = "مرحباً، تم قبول طلبك للوظيفة. يمكننا الآن مناقشة تفاصيل المشروع.";
                $stmt = $pdo->prepare("
                    INSERT INTO messages (conversation_id, sender_id, recipient_id, message_text, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $application_id, 
                    $application['business_id'], 
                    $application['freelancer_id'], 
                    $initial_message
                ]);
            }
            
            setFlash('تم قبول المتقدم بنجاح! يمكنك الآن التواصل معه', 'success');
        }
        break;
                        
                    case 'reject':
                        $stmt = $pdo->prepare("UPDATE freelancer_applications SET status = 'rejected' WHERE id = ?");
                        $stmt->execute([$application_id]);
                        setFlash('تم رفض الطلب', 'success');
                        break;
                }
            }
            
            header('Location: applications.php' . (isset($_GET['job_id']) ? '?job_id=' . $_GET['job_id'] : ''));
            exit;
            
        } catch(PDOException $e) {
            setFlash('حدث خطأ: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get filters
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get user's jobs for filter dropdown
try {
    $stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE business_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $user_jobs = $stmt->fetchAll();
} catch(PDOException $e) {
    $user_jobs = [];
}

// Build applications query
$query = "
    SELECT fa.*,
           j.title as job_title,
           j.category as job_category,
           u.first_name, u.last_name, u.avatar, u.headline, u.rating,
           u.location, u.hourly_rate, u.skills
    FROM freelancer_applications fa
    JOIN jobs j ON fa.job_id = j.id
    JOIN users u ON fa.freelancer_id = u.id
    WHERE j.business_id = ?
";

$params = [$user_id];

if ($job_id > 0) {
    $query .= " AND fa.job_id = ?";
    $params[] = $job_id;
}

if ($status_filter !== 'all') {
    $query .= " AND fa.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR j.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY fa.applied_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
    
    // Get counts for filters
    $stmt = $pdo->prepare("
        SELECT fa.status, COUNT(*) as count
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE j.business_id = ?
        GROUP BY fa.status
    ");
    $stmt->execute([$user_id]);
    $status_counts = [];
    $total_count = 0;
    foreach ($stmt->fetchAll() as $row) {
        $status_counts[$row['status']] = $row['count'];
        $total_count += $row['count'];
    }
    
} catch(PDOException $e) {
    $applications = [];
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
    <title>المتقدمين - <?= SITE_NAME ?></title>
    
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
        
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
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
        
        .filter-select {
            padding: 10px 15px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            width: 100%;
        }
        
        .filter-select:focus {
            outline: none;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        /* Applications List */
        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .application-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
        }
        
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .freelancer-profile {
            display: flex;
            gap: 15px;
            flex: 1;
        }
        
        .freelancer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .freelancer-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .freelancer-info {
            flex: 1;
        }
        
        .freelancer-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .freelancer-headline {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .freelancer-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .freelancer-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .freelancer-meta-item i {
            color: var(--primary-color);
        }
        
        .rating-stars {
            color: #FFD700;
        }
        
        .application-status-badge {
            padding: 6px 15px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 
                inset 2px 2px 4px rgba(0,0,0,0.1),
                inset -2px -2px 4px rgba(255,255,255,0.7);
        }
        
        .status-pending { background: #fff3e0; color: var(--warning-color); }
        .status-accepted { background: #e8f5e9; color: var(--success-color); }
        .status-rejected { background: #ffebee; color: var(--danger-color); }
        
        .job-info-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: var(--neumorphic-bg);
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--primary-color);
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
        }
        
        .application-content {
            margin-bottom: 15px;
        }
        
        .cover-letter {
            background: rgba(255,255,255,0.5);
            padding: 15px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 15px;
        }
        
        .cover-letter-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .skill-badge {
            padding: 4px 12px;
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 
                3px 3px 6px rgba(23, 79, 132, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .application-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .application-date {
            font-size: 12px;
            color: #999;
        }
        
        .application-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 8px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-accept {
            background: var(--success-color);
            color: white;
            box-shadow: 
                4px 4px 8px rgba(0, 191, 154, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .btn-accept:hover {
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-reject:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-contact {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                4px 4px 8px rgba(23, 79, 132, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .btn-contact:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .btn-profile {
            background: var(--neumorphic-bg);
            color: var(--primary-color);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            text-decoration: none;
        }
        
        .btn-profile:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            color: var(--primary-color);
            text-decoration: none;
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
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .freelancer-profile {
                flex-direction: column;
            }
            .application-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            .application-actions {
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
            <a href="jobs.php">
                <i class="fas fa-briefcase"></i>
                <span>وظائفي</span>
            </a>
            <a href="applications.php" class="active">
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
                <h2>المتقدمين 👥</h2>
                <p>مراجعة وإدارة طلبات المستقلين</p>
            </div>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-row">
                <select class="filter-select" onchange="window.location.href='?job_id=' + this.value + '&status=<?= $status_filter ?>'">
                    <option value="0">جميع الوظائف</option>
                    <?php foreach ($user_jobs as $job): ?>
                        <option value="<?= $job['id'] ?>" <?= $job_id == $job['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($job['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="search-box">
                    <form method="GET" action="">
                        <?php if ($job_id > 0): ?>
                            <input type="hidden" name="job_id" value="<?= $job_id ?>">
                        <?php endif; ?>
                        <input type="hidden" name="status" value="<?= $status_filter ?>">
                        <input type="text" name="search" placeholder="ابحث عن متقدم..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-search"></i>
                    </form>
                </div>
            </div>
            
            <div class="filter-tabs">
                <a href="?job_id=<?= $job_id ?>&status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    الكل
                    <span class="count"><?= $total_count ?></span>
                </a>
                <a href="?job_id=<?= $job_id ?>&status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i>
                    قيد المراجعة
                    <span class="count"><?= $status_counts['pending'] ?? 0 ?></span>
                </a>
                <a href="?job_id=<?= $job_id ?>&status=accepted" class="filter-tab <?= $status_filter === 'accepted' ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i>
                    مقبول
                    <span class="count"><?= $status_counts['accepted'] ?? 0 ?></span>
                </a>
                <a href="?job_id=<?= $job_id ?>&status=rejected" class="filter-tab <?= $status_filter === 'rejected' ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i>
                    مرفوض
                    <span class="count"><?= $status_counts['rejected'] ?? 0 ?></span>
                </a>
            </div>
        </div>
        
        <!-- Applications List -->
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>لا توجد طلبات</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        لم يتم العثور على نتائج للبحث "<?= htmlspecialchars($search) ?>"
                    <?php elseif ($status_filter !== 'all'): ?>
                        لا توجد طلبات بحالة "<?= $status_filter ?>"
                    <?php else: ?>
                        لم تتلق أي طلبات على وظائفك بعد
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $app): 
                    $skills = !empty($app['skills']) ? explode(',', $app['skills']) : [];
                ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="freelancer-profile">
                                <div class="freelancer-avatar">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($app['first_name']) ?>&background=667eea&color=fff&size=120" 
                                         alt="<?= htmlspecialchars($app['first_name']) ?>">
                                </div>
                                <div class="freelancer-info">
                                    <div class="freelancer-name">
                                        <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                                    </div>
                                    <div class="freelancer-headline">
                                        <?= htmlspecialchars($app['headline'] ?? 'مستقل محترف') ?>
                                    </div>
                                    <div class="freelancer-meta">
                                        <div class="freelancer-meta-item">
                                            <span class="rating-stars">
                                                <?php for($i = 0; $i < 5; $i++): ?>
                                                    <i class="fas fa-star<?= $i < floor($app['rating']) ? '' : '-o' ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                            <span><?= number_format($app['rating'], 1) ?></span>
                                        </div>
                                        <div class="freelancer-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($app['location'] ?? 'غير محدد') ?>
                                        </div>
                                        <div class="freelancer-meta-item">
                                            <i class="fas fa-dollar-sign"></i>
                                            <?= formatMoney($app['hourly_rate']) ?> / ساعة
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                                <span class="application-status-badge status-<?= $app['status'] ?>">
                                    <?php
                                    $status_labels = [
                                        'pending' => 'قيد المراجعة',
                                        'accepted' => 'مقبول',
                                        'rejected' => 'مرفوض'
                                    ];
                                    echo $status_labels[$app['status']] ?? $app['status'];
                                    ?>
                                </span>
                                <span class="job-info-tag">
                                    <i class="fas fa-briefcase"></i>
                                    <?= htmlspecialchars($app['job_title']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="application-content">
                            <?php if (!empty($app['cover_letter'])): ?>
                                <div class="cover-letter-label">
                                    <i class="fas fa-file-alt"></i>
                                    رسالة التقديم
                                </div>
                                <div class="cover-letter">
                                    <?= nl2br(htmlspecialchars($app['cover_letter'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($skills)): ?>
                                <div class="cover-letter-label">
                                    <i class="fas fa-tools"></i>
                                    المهارات
                                </div>
                                <div class="skills-list">
                                    <?php foreach ($skills as $skill): ?>
                                        <span class="skill-badge"><?= htmlspecialchars(trim($skill)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="application-footer">
                            <div class="application-date">
                                <i class="fas fa-clock ml-1"></i>
                                تقدم <?= timeAgo($app['applied_at']) ?>
                            </div>
                            
                            <div class="application-actions">
                                <?php if ($app['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                        <button type="submit" name="action" value="accept" class="btn-action btn-accept">
                                            <i class="fas fa-check"></i>
                                            قبول
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                        <button type="submit" name="action" value="reject" class="btn-action btn-reject">
                                            <i class="fas fa-times"></i>
                                            رفض
                                        </button>
                                    </form>
                                <?php elseif ($app['status'] === 'accepted'): ?>
                                    <a href="messages.php?conversation=<?= $app['id'] ?>&recipient=<?= $app['freelancer_id'] ?>" class="btn-action btn-contact">
                                        <i class="fas fa-comment"></i>
                                        تواصل
                                    </a>
                                <?php endif; ?>
                                   <a href="view-freelancer-profile.php?id=<?= $app['freelancer_id'] ?>" 
                                       class="btn-action btn-profile">
                                        <i class="fas fa-user"></i>
                                        الملف الشخصي
                                   </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

</body>
</html>