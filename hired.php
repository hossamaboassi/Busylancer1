<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $application_id = intval($_POST['application_id']);
        
        try {
            // Verify application belongs to user's job
            $stmt = $pdo->prepare("
                SELECT fa.*, j.id as job_id
                FROM freelancer_applications fa
                JOIN jobs j ON fa.job_id = j.id
                WHERE fa.id = ? AND j.business_id = ?
            ");
            $stmt->execute([$application_id, $user_id]);
            $application = $stmt->fetch();
            
            if ($application) {
                switch ($_POST['action']) {
                    case 'mark_complete':
                        // Mark job as filled
                        $stmt = $pdo->prepare("UPDATE jobs SET status = 'filled' WHERE id = ?");
                        $stmt->execute([$application['job_id']]);
                        setFlash('تم تحديد المشروع كمكتمل بنجاح', 'success');
                        break;
                        
                    case 'rate':
                        $rating = intval($_POST['rating']);
                        if ($rating >= 1 && $rating <= 5) {
                            // Update freelancer rating (simplified - should calculate average)
                            $stmt = $pdo->prepare("UPDATE users SET rating = ? WHERE id = ?");
                            $stmt->execute([$rating, $application['freelancer_id']]);
                            setFlash('تم إضافة التقييم بنجاح', 'success');
                        }
                        break;
                }
            }
            
            header('Location: hired.php');
            exit;
            
        } catch(PDOException $e) {
            setFlash('حدث خطأ: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';

// Build query for hired freelancers (accepted applications)
$query = "
    SELECT fa.*,
           j.title as job_title,
           j.category as job_category,
           j.status as job_status,
           j.hourly_rate,
           u.first_name, u.last_name, u.avatar, u.headline, u.rating,
           u.location, u.skills, u.email, u.phone
    FROM freelancer_applications fa
    JOIN jobs j ON fa.job_id = j.id
    JOIN users u ON fa.freelancer_id = u.id
    WHERE j.business_id = ? AND fa.status = 'accepted'
";

$params = [$user_id];

if ($status_filter === 'active') {
    $query .= " AND j.status = 'active'";
} elseif ($status_filter === 'completed') {
    $query .= " AND j.status = 'filled'";
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
    $hired_freelancers = $stmt->fetchAll();
    
    // Get counts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN j.status = 'active' THEN 1 END) as active_count,
            COUNT(CASE WHEN j.status = 'filled' THEN 1 END) as completed_count,
            COUNT(*) as total_count
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE j.business_id = ? AND fa.status = 'accepted'
    ");
    $stmt->execute([$user_id]);
    $counts = $stmt->fetch();
    
} catch(PDOException $e) {
    $hired_freelancers = [];
    $counts = ['total_count' => 0, 'active_count' => 0, 'completed_count' => 0];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المستقلين المعينين - <?= SITE_NAME ?></title>
    
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
        /* Sidebar - Same as before */
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
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex: 1;
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
        
        .search-box {
            position: relative;
            width: 300px;
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
        
        /* Freelancer Grid */
        .freelancers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .freelancer-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .freelancer-card:hover {
            transform: translateY(-3px);
        }
        
        .freelancer-header {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .freelancer-avatar {
            width: 70px;
            height: 70px;
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
        
        .freelancer-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .rating-stars {
            color: #FFD700;
        }
        
        .project-info {
            background: rgba(255,255,255,0.5);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .project-title {
            font-weight: 700;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .project-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 
                inset 2px 2px 4px rgba(0,0,0,0.1),
                inset -2px -2px 4px rgba(255,255,255,0.7);
        }
        
        .status-active { background: #e8f5e9; color: var(--success-color); }
        .status-filled { background: #e3f2fd; color: var(--primary-color); }
        
        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 12px;
            color: #999;
            margin-top: 8px;
        }
        
        .project-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .project-meta-item i {
            color: var(--primary-color);
        }
        
        .contact-info {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .contact-item {
            flex: 1;
            padding: 10px;
            background: var(--neumorphic-bg);
            border-radius: 8px;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        
        .contact-item i {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-color);
            font-size: 14px;
        }
        
        .freelancer-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .btn-action {
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .btn-message {
            background: var(--primary-color);
            color: white;
            box-shadow: 
                4px 4px 8px rgba(23, 79, 132, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .btn-message:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .btn-complete {
            background: var(--success-color);
            color: white;
            box-shadow: 
                4px 4px 8px rgba(0, 191, 154, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .btn-complete:hover {
            transform: translateY(-2px);
        }
        
        .btn-rate {
            background: var(--neumorphic-bg);
            color: var(--warning-color);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-rate:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-profile {
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-profile:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            color: #666;
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
            margin-bottom: 20px;
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
        
        .rating-input {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .rating-star {
            font-size: 36px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .rating-star:hover,
        .rating-star.active {
            color: #FFD700;
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
            .stats-row {
                grid-template-columns: 1fr;
            }
            .freelancers-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .filter-row {
                flex-direction: column;
            }
            .search-box {
                width: 100%;
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
            <a href="applications.php">
                <i class="fas fa-users"></i>
                <span>المتقدمين</span>
            </a>
            <a href="hired.php" class="active">
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
                <h2>المستقلين المعينين ✨</h2>
                <p>إدارة المستقلين الذين تم توظيفهم في مشاريعك</p>
            </div>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?= $counts['total_count'] ?></h3>
                <p>إجمالي المستقلين المعينين</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3><?= $counts['active_count'] ?></h3>
                <p>مشاريع نشطة</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?= $counts['completed_count'] ?></h3>
                <p>مشاريع مكتملة</p>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-row">
                <div class="filter-tabs">
                    <a href="?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">
                        <i class="fas fa-list"></i>
                        الكل
                        <span class="count"><?= $counts['total_count'] ?></span>
                    </a>
                    <a href="?status=active" class="filter-tab <?= $status_filter === 'active' ? 'active' : '' ?>">
                        <i class="fas fa-play-circle"></i>
                        نشط
                        <span class="count"><?= $counts['active_count'] ?></span>
                    </a>
                    <a href="?status=completed" class="filter-tab <?= $status_filter === 'completed' ? 'active' : '' ?>">
                        <i class="fas fa-check-circle"></i>
                        مكتمل
                        <span class="count"><?= $counts['completed_count'] ?></span>
                    </a>
                </div>
                
                <div class="search-box">
                    <form method="GET" action="">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                        <input type="text" name="search" placeholder="ابحث عن مستقل..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-search"></i>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Freelancers Grid -->
        <?php if (empty($hired_freelancers)): ?>
            <div class="empty-state">
                <i class="fas fa-user-friends"></i>
                <h3>لا يوجد مستقلين معينين</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        لم يتم العثور على نتائج للبحث "<?= htmlspecialchars($search) ?>"
                    <?php elseif ($status_filter !== 'all'): ?>
                        لا يوجد مشاريع بحالة "<?= $status_filter ?>"
                    <?php else: ?>
                        لم تقم بتوظيف أي مستقلين بعد<br>
                        ابدأ بمراجعة المتقدمين على وظائفك
                    <?php endif; ?>
                </p>
                <a href="applications.php" class="btn-primary-neumorphic">
                    <i class="fas fa-users"></i>
                    مراجعة المتقدمين
                </a>
            </div>
        <?php else: ?>
            <div class="freelancers-grid">
                <?php foreach ($hired_freelancers as $hire): 
                    $skills = !empty($hire['skills']) ? explode(',', $hire['skills']) : [];
                ?>
                    <div class="freelancer-card">
                        <div class="freelancer-header">
                            <div class="freelancer-avatar">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($hire['first_name']) ?>&background=667eea&color=fff&size=140" 
                                     alt="<?= htmlspecialchars($hire['first_name']) ?>">
                            </div>
                            <div class="freelancer-info">
                                <div class="freelancer-name">
                                    <?= htmlspecialchars($hire['first_name'] . ' ' . $hire['last_name']) ?>
                                </div>
                                <div class="freelancer-headline">
                                    <?= htmlspecialchars($hire['headline'] ?? 'مستقل محترف') ?>
                                </div>
                                <div class="freelancer-rating">
                                    <span class="rating-stars">
                                        <?php for($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?= $i < floor($hire['rating']) ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span><?= number_format($hire['rating'], 1) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="project-info">
                            <div class="project-title">
                                <i class="fas fa-briefcase" style="color: var(--primary-color);"></i>
                                <?= htmlspecialchars($hire['job_title']) ?>
                                <span class="project-status status-<?= $hire['job_status'] ?>" style="margin-right: auto;">
                                    <?= $hire['job_status'] == 'active' ? 'نشط' : 'مكتمل' ?>
                                </span>
                            </div>
                            <div class="project-meta">
                                <div class="project-meta-item">
                                    <i class="fas fa-folder"></i>
                                    <?= htmlspecialchars($hire['job_category'] ?? 'غير محدد') ?>
                                </div>
                                <div class="project-meta-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    <?= formatMoney($hire['hourly_rate']) ?>
                                </div>
                                <div class="project-meta-item">
                                    <i class="fas fa-clock"></i>
                                    تم التوظيف <?= timeAgo($hire['applied_at']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($hire['email']) ?>
                            </div>
                            <?php if (!empty($hire['phone'])): ?>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <?= htmlspecialchars($hire['phone']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="freelancer-actions">
                            <a href="messages.php?user_id=<?= $hire['freelancer_id'] ?>" class="btn-action btn-message">
                                <i class="fas fa-comment"></i>
                                مراسلة
                            </a>
                            
                            <?php if ($hire['job_status'] === 'active'): ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="application_id" value="<?= $hire['id'] ?>">
                                    <button type="submit" name="action" value="mark_complete" class="btn-action btn-complete">
                                        <i class="fas fa-check"></i>
                                        تحديد كمكتمل
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn-action btn-rate" 
                                        onclick="openRatingModal(<?= $hire['id'] ?>, '<?= htmlspecialchars($hire['first_name'] . ' ' . $hire['last_name']) ?>')">
                                    <i class="fas fa-star"></i>
                                    تقييم
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn-action btn-profile" 
                                    onclick="alert('سيتم إضافة عرض الملف الشخصي قريباً')">
                                <i class="fas fa-user"></i>
                                الملف الشخصي
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Rating Modal -->
    <div class="modal-overlay" id="ratingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-star ml-2" style="color: #FFD700;"></i>تقييم المستقل</h4>
                <p id="freelancerName"></p>
            </div>
            <div class="rating-input">
                <i class="fas fa-star rating-star" data-rating="1"></i>
                <i class="fas fa-star rating-star" data-rating="2"></i>
                <i class="fas fa-star rating-star" data-rating="3"></i>
                <i class="fas fa-star rating-star" data-rating="4"></i>
                <i class="fas fa-star rating-star" data-rating="5"></i>
            </div>
            <form method="POST" id="ratingForm">
                <input type="hidden" name="application_id" id="ratingApplicationId">
                <input type="hidden" name="rating" id="ratingValue" value="0">
                <div class="modal-actions">
                    <button type="button" class="btn-action btn-profile" onclick="closeRatingModal()">
                        إلغاء
                    </button>
                    <button type="submit" name="action" value="rate" class="btn-action btn-complete">
                        <i class="fas fa-check"></i>
                        إرسال التقييم
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Rating modal
        let selectedRating = 0;
        
        function openRatingModal(applicationId, freelancerName) {
            document.getElementById('ratingApplicationId').value = applicationId;
            document.getElementById('freelancerName').textContent = freelancerName;
            document.getElementById('ratingModal').classList.add('show');
            selectedRating = 0;
            updateRatingStars();
        }
        
        function closeRatingModal() {
            document.getElementById('ratingModal').classList.remove('show');
        }
        
        // Rating stars interaction
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                document.getElementById('ratingValue').value = selectedRating;
                updateRatingStars();
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                document.querySelectorAll('.rating-star').forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        document.querySelector('.rating-input').addEventListener('mouseleave', updateRatingStars);
        
        function updateRatingStars() {
            document.querySelectorAll('.rating-star').forEach((star, i) => {
                if (i < selectedRating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('ratingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRatingModal();
            }
        });
    </script>

</body>
</html>