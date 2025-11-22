<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$active_page = 'my-jobs'; // For the sidebar
$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';
$all_jobs = []; // Initialize as an empty array

try {
    // --- Query 1: Get PLATFORM jobs ---
    $platform_jobs = [];
    $platform_jobs_sql = "
        SELECT 
            fa.id, j.title, j.hourly_rate, j.location, u.first_name, u.last_name,
            fa.status, fa.applied_at as created_at, 'platform' as source
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        JOIN users u ON j.business_id = u.id
        WHERE fa.freelancer_id = ?
    ";
    $params1 = [$user_id];
    if ($status_filter != 'all') {
        $platform_jobs_sql .= " AND fa.status = ?";
        $params1[] = $status_filter;
    }
    $stmt1 = $pdo->prepare($platform_jobs_sql);
    $stmt1->execute($params1);
    $platform_jobs = $stmt1->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // --- Query 2: Get MANUALLY added jobs ---
    $manual_jobs = [];
    $manual_jobs_sql = "
        SELECT 
            id, job_title as title, job_date as created_at, amount as hourly_rate,
            status, 'manual' as source
        FROM freelancer_jobs 
        WHERE freelancer_id = ? AND source = 'manual'
    ";
    $stmt2 = $pdo->prepare($manual_jobs_sql);
    $stmt2->execute([$user_id]);
    $manual_jobs = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // --- Query 3: Get FIELD GIGS ---
    $gigs = [];
    $gigs_sql = "
        SELECT 
            id, title, start_time as created_at, rate_value as hourly_rate,
            status, 'gig' as source
        FROM gigs 
        WHERE freelancer_id = ?
    ";
    $stmt3 = $pdo->prepare($gigs_sql);
    $stmt3->execute([$user_id]);
    $gigs = $stmt3->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // --- Combine and Sort All Jobs ---
    $all_jobs = array_merge($platform_jobs, $manual_jobs, $gigs);
    if (!empty($all_jobs)) {
        usort($all_jobs, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }

    // --- Get stats for the filter tabs ---
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM freelancer_applications 
        WHERE freelancer_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();

} catch (PDOException $e) {
    setFlash('danger', 'حدث خطأ في قاعدة البيانات أثناء تحميل الوظائف.');
    error_log("My Jobs Error: " . $e->getMessage());
    $stats = ['total' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>وظائفي وتطبيقاتي - <?= SITE_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sidebar.css" rel="stylesheet">
    
    <style>
        :root {
            --neumorphic-bg: #e9ecef; --neumorphic-light: #ffffff; --neumorphic-dark: rgba(174, 174, 192, 0.4);
            --primary-color: #174F84; --success-color: #00BF9A; --warning-color: #F5B759; --danger-color: #FA5252;
        }
        body { background-color: var(--neumorphic-bg); font-family: 'Cairo', sans-serif; }
        .main-content { margin-right: 260px; padding: 25px; }
        .page-header h2 { font-weight: 700; color: #333; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--neumorphic-bg); border-radius: 12px; padding: 20px; text-align: center; text-decoration: none; color: inherit; box-shadow: 8px 8px 16px var(--neumorphic-dark), -8px -8px 16px var(--neumorphic-light); }
        .stat-card.active { box-shadow: inset 4px 4px 8px var(--neumorphic-dark), inset -4px -4px 8px var(--neumorphic-light); }
        .stat-card h3 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; color: var(--primary-color); }
        .application-card { background: var(--neumorphic-bg); border-radius: 15px; padding: 25px; box-shadow: 8px 8px 16px var(--neumorphic-dark), -8px -8px 16px var(--neumorphic-light); margin-bottom: 20px; }
        .badge-neumorphic { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; box-shadow: 3px 3px 6px var(--neumorphic-dark), -3px -3px 6px var(--neumorphic-light); }
        .btn-primary-neumorphic { background: var(--primary-color); color: white; border: none; padding: 8px 16px; font-weight: 600; border-radius: 10px; box-shadow: 4px 4px 12px rgba(23, 79, 132, 0.3), -2px -2px 6px #fff; }
        .btn-secondary-neumorphic { background: var(--neumorphic-bg); color: #666; border: none; padding: 8px 16px; font-weight: 600; border-radius: 10px; box-shadow: 4px 4px 8px var(--neumorphic-dark), -4px -4px 8px var(--neumorphic-light); }
        .badge-pending { background-color: var(--warning-color); color: white; } .badge-accepted, .badge-completed { background-color: var(--success-color); color: white; } .badge-rejected { background-color: var(--danger-color); color: white; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; background: var(--neumorphic-bg); border-radius: 12px; box-shadow: inset 4px 4px 8px var(--neumorphic-dark), inset -4px -4px 8px var(--neumorphic-light); }
        @media (max-width: 992px) { .main-content { margin-right: 0; } }
    </style>
</head>
<body>

    <?php include '../includes/sidebar-freelancer.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h2>وظائفي وتطبيقاتي</h2>
                <p>تتبع حالة وظائفك الحالية والطلبات المقدمة</p>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <a href="?status=all" class="stat-card <?= $status_filter == 'all' ? 'active' : '' ?>"><h3><?= htmlspecialchars($stats['total'] ?? 0) ?></h3><p>جميع الطلبات</p></a>
            <a href="?status=pending" class="stat-card <?= $status_filter == 'pending' ? 'active' : '' ?>"><h3><?= htmlspecialchars($stats['pending'] ?? 0) ?></h3><p>قيد المراجعة</p></a>
            <a href="?status=accepted" class="stat-card <?= $status_filter == 'accepted' ? 'active' : '' ?>"><h3><?= htmlspecialchars($stats['accepted'] ?? 0) ?></h3><p>مقبول</p></a>
            <a href="?status=rejected" class="stat-card <?= $status_filter == 'rejected' ? 'active' : '' ?>"><h3><?= htmlspecialchars($stats['rejected'] ?? 0) ?></h3><p>مرفوض</p></a>
        </div>

        <div class="applications-container">
            <?php if (empty($all_jobs)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open" style="font-size: 5rem; color: #ddd;"></i>
                    <h5 class="mt-3">لا توجد وظائف أو تطبيقات لعرضها</h5>
                    <p>الوظائف التي تضيفها أو تتقدم لها ستظهر هنا.</p>
                </div>
            <?php else: ?>
                <?php foreach ($all_jobs as $job): ?>
                    <div class="application-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4><?= htmlspecialchars($job['title'] ?? 'مهمة بدون عنوان') ?></h4>
                                <div class="text-muted mb-2">
                                    <i class="fas fa-building mr-2"></i>
                                    <?php 
                                        if ($job['source'] === 'platform') { echo htmlspecialchars(($job['first_name'] ?? '') . ' ' . ($job['last_name'] ?? 'عميل')); }
                                        elseif ($job['source'] === 'manual') { echo 'مهمة يدوية'; }
                                        else { echo 'مهمة ميدانية'; }
                                    ?>
                                </div>
                                <div class="text-success font-weight-bold"><?= formatMoney($job['hourly_rate'] ?? 0) ?></div>
                            </div>
                            <div>
                                <?php
                                $status_map = [
                                    'pending' => ['text' => 'قيد المراجعة', 'class' => 'badge-pending'], 'accepted' => ['text' => 'مقبول', 'class' => 'badge-accepted'], 'rejected' => ['text' => 'مرفوض', 'class' => 'badge-rejected'],
                                    'in_progress' => ['text' => 'قيد التنفيذ', 'class' => 'badge-pending'], 'completed' => ['text' => 'مكتمل', 'class' => 'badge-accepted'],
                                    'upcoming' => ['text' => 'قادمة', 'class' => 'badge-info'], 'active' => ['text' => 'نشطة', 'class' => 'badge-success'], 'awaiting_payment' => ['text' => 'بانتظار الدفع', 'class' => 'badge-warning'],
                                ];
                                $status_info = $status_map[$job['status']] ?? ['text' => $job['status'], 'class' => 'badge-secondary'];
                                ?>
                                <span class="badge-neumorphic <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="far fa-clock mr-1"></i> أضيف <?= timeAgo($job['created_at'] ?? '1970-01-01') ?></small>
                            <div class="application-actions">
                                <?php if ($job['source'] === 'gig'): ?>
                                    <a href="gig-details.php?id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-primary-neumorphic">
                                        <i class="fas fa-cog"></i>
                                        إدارة المهمة
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn btn-secondary-neumorphic" onclick="alert('لا توجد تفاصيل إضافية لهذه المهمة.'); return false;">
                                        <i class="fas fa-eye"></i>
                                        التفاصيل
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>