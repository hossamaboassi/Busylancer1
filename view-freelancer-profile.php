<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('business');

$business_user = getCurrentUser($pdo);

// Get freelancer ID from URL
$freelancer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$freelancer_id) {
    setFlash('معرف المستقل غير صحيح', 'danger');
    redirect('applications.php');
}

// Get freelancer data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$freelancer_id]);
    $freelancer = $stmt->fetch();
    
    if (!$freelancer) {
        setFlash('لم يتم العثور على المستقل', 'danger');
        redirect('applications.php');
    }
    
    // Verify this user has applied to at least one of our jobs (security check)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE fa.freelancer_id = ? AND j.business_id = ?
    ");
    $stmt->execute([$freelancer_id, $business_user['id']]);
    $has_applied = $stmt->fetchColumn();
    
    if ($has_applied == 0) {
        setFlash('لا يمكنك عرض هذا الملف الشخصي', 'danger');
        redirect('applications.php');
    }
    
} catch(PDOException $e) {
    setFlash('حدث خطأ في تحميل البيانات', 'danger');
    redirect('applications.php');
}

// Get portfolio items
try {
    $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$freelancer_id]);
    $portfolio = $stmt->fetchAll();
} catch(PDOException $e) {
    $portfolio = [];
}

// Get experience
try {
    $stmt = $pdo->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$freelancer_id]);
    $experiences = $stmt->fetchAll();
} catch(PDOException $e) {
    $experiences = [];
}

// Get education
try {
    $stmt = $pdo->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$freelancer_id]);
    $educations = $stmt->fetchAll();
} catch(PDOException $e) {
    $educations = [];
}

// Get completed projects count
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM freelancer_applications 
        WHERE freelancer_id = ? AND status = 'accepted'
    ");
    $stmt->execute([$freelancer_id]);
    $completed_projects = $stmt->fetchColumn();
} catch(PDOException $e) {
    $completed_projects = 0;
}

// Get applications to this business's jobs
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        WHERE fa.freelancer_id = ? AND j.business_id = ?
    ");
    $stmt->execute([$freelancer_id, $business_user['id']]);
    $applications_to_business = $stmt->fetchColumn();
} catch(PDOException $e) {
    $applications_to_business = 0;
}

// Get total applications for this freelancer (public stat)
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM freelancer_applications 
        WHERE freelancer_id = ?
    ");
    $stmt->execute([$freelancer_id]);
    $total_applications = $stmt->fetchColumn();
} catch(PDOException $e) {
    $total_applications = 0;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ملف <?= htmlspecialchars($freelancer['first_name']) ?> - <?= SITE_NAME ?></title>
    
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
            font-family: 'Cairo', sans-serif;
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
            margin-bottom: 25px;
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
        
        .btn-back {
            background: var(--neumorphic-bg);
            color: #666;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            color: #666;
            text-decoration: none;
        }
        
        /* Profile Card */
        .profile-card {
            background: var(--neumorphic-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 
                10px 10px 20px var(--neumorphic-dark),
                -10px -10px 20px var(--neumorphic-light);
            margin-bottom: 20px;
        }
        
        .profile-header {
            display: flex;
            gap: 25px;
            align-items: start;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h3 {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 700;
            color: #333;
        }
        
        .profile-headline {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .profile-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .profile-meta-item i {
            color: var(--primary-color);
            font-size: 16px;
        }
        
        .rating-stars {
            color: #FFD700;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: var(--neumorphic-bg);
            border-radius: 12px;
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .stat-item h4 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-item p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #999;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-primary-action {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
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
        
        .btn-primary-action:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        /* Section Card */
        .section-card {
            background: var(--neumorphic-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 
                10px 10px 20px var(--neumorphic-dark),
                -10px -10px 20px var(--neumorphic-light);
            margin-bottom: 20px;
        }
        
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .section-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header h4 i {
            color: var(--primary-color);
        }
        
        .section-content {
            line-height: 1.8;
            color: #666;
            font-size: 14px;
        }
        
        /* Skills */
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .skill-badge {
            background: var(--neumorphic-bg);
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
        }
        
        /* Portfolio Grid */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .portfolio-item {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 
                6px 6px 12px var(--neumorphic-dark),
                -6px -6px 12px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .portfolio-item:hover {
            transform: translateY(-5px);
        }
        
        .portfolio-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .portfolio-item-content {
            padding: 15px;
        }
        
        .portfolio-item h5 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .portfolio-item p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-right: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, var(--primary-color) 0%, transparent 100%);
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            right: -6px;
            top: 5px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary-color);
            box-shadow: 0 0 0 4px var(--neumorphic-bg);
        }
        
        .timeline-content {
            background: var(--neumorphic-bg);
            padding: 18px;
            border-radius: 10px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .timeline-content h5 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .timeline-content .company {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .timeline-content .date {
            color: #999;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .timeline-content p {
            margin: 0;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            font-size: 14px;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { margin-right: 0; padding: 15px; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-stats { grid-template-columns: repeat(2, 1fr); }
            .portfolio-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
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
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($business_user['first_name']) ?>&background=174F84&color=fff&size=140" alt="Avatar">
            </div>
            <div class="user-info">
                <h6><?= htmlspecialchars($business_user['company_name'] ?? ($business_user['first_name'] . ' ' . $business_user['last_name'])) ?></h6>
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
                <h2>ملف المستقل الشخصي</h2>
                <p>استعرض معلومات وخبرات المستقل</p>
            </div>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-right"></i>
                رجوع
            </a>
        </div>
        
        <!-- Profile Overview -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($freelancer['first_name']) ?>&background=667eea&color=fff&size=240" 
                         alt="<?= htmlspecialchars($freelancer['first_name']) ?>">
                </div>
                
                <div class="profile-info">
                    <h3><?= htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']) ?></h3>
                    <div class="profile-headline">
                        <?= htmlspecialchars($freelancer['headline'] ?? 'مستقل محترف') ?>
                    </div>
                    
                    <div class="profile-meta">
                        <div class="profile-meta-item">
                            <span class="rating-stars">
                                <?php for($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star<?= $i < floor($freelancer['rating']) ? '' : '-o' ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <span><?= number_format($freelancer['rating'] ?? 0, 1) ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($freelancer['city'] ?? 'غير محدد') ?>, <?= htmlspecialchars($freelancer['country'] ?? 'السعودية') ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span><?= formatMoney($freelancer['hourly_rate'] ?? 0) ?> / ساعة</span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($freelancer['email']) ?></span>
                        </div>
                        <?php if (!empty($freelancer['phone'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($freelancer['phone']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="messages.php?user_id=<?= $freelancer['id'] ?>" class="btn-primary-action">
                            <i class="fas fa-comment"></i>
                            إرسال رسالة
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <h4><?= $completed_projects ?></h4>
                    <p>مشاريع مقبولة</p>
                </div>
                <div class="stat-item">
                    <h4><?= $total_applications ?></h4>
                    <p>إجمالي التقديمات</p>
                </div>
                <div class="stat-item">
                    <h4><?= $applications_to_business ?></h4>
                    <p>تقديمات لوظائفك</p>
                </div>
            </div>
        </div>
        
        <!-- Bio -->
        <?php if (!empty($freelancer['bio'])): ?>
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-user-circle"></i>نبذة عني</h4>
            </div>
            <div class="section-content">
                <?= nl2br(htmlspecialchars($freelancer['bio'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Skills -->
        <?php if (!empty($freelancer['skills'])): ?>
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-tools"></i>المهارات</h4>
            </div>
            <div class="skills-list">
                <?php foreach (explode(',', $freelancer['skills']) as $skill): ?>
                    <span class="skill-badge"><?= htmlspecialchars(trim($skill)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Portfolio -->
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-briefcase"></i>محفظة الأعمال</h4>
            </div>
            
            <?php if (empty($portfolio)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>لم يضف المستقل مشاريع في محفظته بعد</p>
                </div>
            <?php else: ?>
                <div class="portfolio-grid">
                    <?php foreach ($portfolio as $item): ?>
                        <div class="portfolio-item">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                            <div class="portfolio-item-content">
                                <h5><?= htmlspecialchars($item['title']) ?></h5>
                                <p><?= htmlspecialchars($item['category']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Experience -->
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-briefcase"></i>الخبرات العملية</h4>
            </div>
            
            <?php if (empty($experiences)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>لم يضف المستقل خبراته العملية بعد</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($experiences as $exp): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <h5><?= htmlspecialchars($exp['role']) ?></h5>
                                <div class="company"><?= htmlspecialchars($exp['company']) ?></div>
                                <div class="date"><?= htmlspecialchars($exp['period']) ?></div>
                                <?php if (!empty($exp['description'])): ?>
                                    <p><?= htmlspecialchars($exp['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Education -->
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-graduation-cap"></i>التعليم</h4>
            </div>
            
            <?php if (empty($educations)): ?>
                <div class="empty-state">
                    <i class="fas fa-university"></i>
                    <p>لم يضف المستقل مؤهلاته التعليمية بعد</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($educations as $edu): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <h5><?= htmlspecialchars($edu['degree']) ?></h5>
                                <div class="company"><?= htmlspecialchars($edu['institution']) ?></div>
                                <div class="date"><?= htmlspecialchars($edu['period']) ?></div>
                                <?php if (!empty($edu['description'])): ?>
                                    <p><?= htmlspecialchars($edu['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>