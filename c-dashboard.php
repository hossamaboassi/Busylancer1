<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$active_page = 'dashboard';
$user_id = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

try {
    $statsStmt = $pdo->prepare("
        SELECT
            (SELECT COALESCE(SUM(amount), 0) FROM freelancer_jobs WHERE freelancer_id = :user_id AND status = 'completed') as total_earnings,
            (SELECT COALESCE(SUM(amount), 0) FROM freelancer_jobs WHERE freelancer_id = :user_id AND status = 'in_progress') as pending_earnings,
            (SELECT COUNT(*) FROM freelancer_jobs WHERE freelancer_id = :user_id AND (status = 'in_progress' OR status = 'pending_payment') AND (job_id IS NOT NULL OR source = 'manual')) as active_jobs,
            (SELECT COUNT(*) FROM freelancer_jobs WHERE freelancer_id = :user_id AND status = 'completed' AND (job_id IS NOT NULL OR source = 'manual')) as completed_jobs,
            (SELECT COUNT(*) FROM freelancer_applications WHERE freelancer_id = :user_id) as total_applications,
            (SELECT COUNT(*) FROM freelancer_applications WHERE freelancer_id = :user_id AND status = 'accepted') as accepted_applications,
            (SELECT COUNT(*) FROM freelancer_applications WHERE freelancer_id = :user_id AND status = 'rejected') as rejected_applications,
            (SELECT COUNT(*) FROM freelancer_applications WHERE freelancer_id = :user_id AND status = 'pending') as pending_applications
    ");
    $statsStmt->execute([':user_id' => $user_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $total_earnings = $stats['total_earnings'];
    $pending_earnings = $stats['pending_earnings'];
    $active_jobs = $stats['active_jobs'];
    $completed_jobs = $stats['completed_jobs'];
    $total_applications = $stats['total_applications'];
    $accepted_applications = $stats['accepted_applications'];
    $rejected_applications = $stats['rejected_applications'];
    $pending_applications = $stats['pending_applications'];

    $acceptance_rate = $total_applications > 0 ? round(($accepted_applications / $total_applications) * 100, 1) : 0;
    
    $deadlineStmt = $pdo->prepare("
        (SELECT j.title, j.job_date, u.company_name FROM freelancer_jobs fj JOIN jobs j ON fj.job_id = j.id JOIN users u ON j.business_id = u.id WHERE fj.freelancer_id = ? AND fj.status = 'in_progress' AND j.job_date >= CURDATE())
        UNION ALL
        (SELECT job_title as title, job_date, employer_name as company_name FROM freelancer_jobs WHERE freelancer_id = ? AND source = 'manual' AND (status = 'in_progress' OR status = 'pending_payment') AND job_date >= CURDATE())
        ORDER BY job_date ASC LIMIT 1
    ");
    $deadlineStmt->execute([$user_id, $user_id]);
    $upcoming_deadlines = $deadlineStmt->fetchAll();
    
    $activityStmt = $pdo->prepare("
        (SELECT 'job_completed' as type, job_title as title, amount, created_at FROM freelancer_jobs WHERE freelancer_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'new_application' as type, j.title, NULL as amount, fa.applied_at as created_at FROM freelancer_applications fa JOIN jobs j ON fa.job_id = j.id WHERE fa.freelancer_id = ? ORDER BY fa.applied_at DESC LIMIT 5)
        ORDER BY created_at DESC LIMIT 8
    ");
    $activityStmt->execute([$user_id, $user_id]);
    $recent_activity = $activityStmt->fetchAll();

    // Simulated data for charts
    $monthly_earnings = [8500, 9200, 11000, 10500, 12000, 11800]; // Last 6 months
    $monthly_labels = ['يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    $category_data = [
        ['name' => 'فعاليات', 'value' => 15, 'earnings' => 45000],
        ['name' => 'تجزئة', 'value' => 10, 'earnings' => 28000],
        ['name' => 'إداري', 'value' => 8, 'earnings' => 18000],
        ['name' => 'توصيل', 'value' => 4, 'earnings' => 9000]
    ];
    
    $leaderboard = [
        ['name' => 'أحمد محمد', 'earnings' => 12500, 'jobs' => 15, 'rank' => 1],
        ['name' => 'فاطمة علي', 'earnings' => 11800, 'jobs' => 14, 'rank' => 2],
        ['name' => 'محمد السعيد', 'earnings' => 11200, 'jobs' => 13, 'rank' => 3],
        ['name' => ($user['first_name'] ?? 'أنت'), 'earnings' => $total_earnings, 'jobs' => $completed_jobs, 'rank' => 8, 'is_current' => true]
    ];
    
} catch (PDOException $e) {
    $total_earnings = $pending_earnings = $active_jobs = $completed_jobs = 0;
    $total_applications = $accepted_applications = $rejected_applications = $pending_applications = 0;
    $acceptance_rate = 0; $upcoming_deadlines = []; $recent_activity = []; $leaderboard = [];
    $monthly_earnings = [0,0,0,0,0,0]; $monthly_labels = [];
    $category_data = [];
    error_log("Dashboard Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytics Dashboard - BusyLancer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-darker: #020617;
            --card-bg: #1e293b;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #a855f7;
            --text: #e2e8f0;
            --text-dim: #94a3b8;
        }
        
        body { 
            background: var(--bg-dark); 
            font-family: 'Cairo', sans-serif; 
            color: var(--text);
        }
        .main-content { margin-right: 260px; padding: 16px; }
        
        /* COMPACT TOP BAR */
        .top-bar {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            color: white;
        }
        .user-details h4 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: var(--text);
        }
        .user-details p {
            font-size: 11px;
            color: var(--text-dim);
            margin: 0;
        }
        
        /* KPI STRIP */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .kpi-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .kpi-label {
            font-size: 11px;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
        }
        .kpi-trend {
            font-size: 11px;
            font-weight: 600;
        }
        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }
        
        /* GRID LAYOUT */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }
        
        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .chart-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }
        .chart-subtitle {
            font-size: 12px;
            color: var(--text-dim);
        }
        
        /* EARNINGS CHART */
        .earnings-large {
            grid-column: 1 / 2;
            height: 320px;
        }
        
        /* PERFORMANCE GRID */
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            grid-column: 1 / 2;
        }
        .perf-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.05);
            text-align: center;
        }
        .perf-circle {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 12px;
        }
        .perf-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
        }
        .perf-label {
            font-size: 13px;
            color: var(--text-dim);
        }
        
        /* RIGHT SIDEBAR */
        .sidebar-section {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 16px;
        }
        .sidebar-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* LEADERBOARD */
        .leader-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: background 0.2s;
        }
        .leader-item:hover { background: rgba(255,255,255,0.03); }
        .leader-item.current { background: rgba(168, 85, 247, 0.1); border: 1px solid var(--purple); }
        .rank {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: var(--text-dim);
        }
        .current .rank { background: var(--purple); color: white; }
        .leader-info {
            flex: 1;
        }
        .leader-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }
        .leader-stats {
            font-size: 11px;
            color: var(--text-dim);
        }
        
        /* ACTIVITY FEED */
        .activity-item {
            display: flex;
            align-items: start;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .activity-content {
            flex: 1;
        }
        .activity-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 2px 0;
        }
        .activity-time {
            font-size: 11px;
            color: var(--text-dim);
        }
        
        /* CATEGORY BREAKDOWN */
        .category-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-bar {
            flex: 1;
            height: 6px;
            background: rgba(255,255,255,0.05);
            border-radius: 3px;
            margin: 0 12px;
            overflow: hidden;
        }
        .category-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s;
        }
        .category-name {
            font-size: 12px;
            color: var(--text);
            min-width: 60px;
        }
        .category-count {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            min-width: 40px;
            text-align: left;
        }
        
        @media (max-width: 992px) {
            .main-content { margin-right: 0; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .performance-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar-freelancer.php'; ?>

    <div class="main-content">
        
        <!-- COMPACT TOP BAR -->
        <div class="top-bar">
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $firstInitial = !empty($user['first_name']) ? mb_substr($user['first_name'], 0, 1) : 'B';
                    $lastInitial = !empty($user['last_name']) ? mb_substr($user['last_name'], 0, 1) : 'L';
                    echo htmlspecialchars(mb_strtoupper($firstInitial . $lastInitial));
                    ?>
                </div>
                <div class="user-details">
                    <h4>مرحباً، <?= htmlspecialchars($user['first_name'] ?? 'مستقل') ?></h4>
                    <p>⭐ 4.8 • <?= $completed_jobs ?> وظيفة مكتملة</p>
                </div>
            </div>
            <div>
                <?php
                $next_deadline = $upcoming_deadlines[0] ?? null;
                if ($next_deadline) {
                    $now = new DateTime();
                    $job_date = new DateTime($next_deadline['job_date']);
                    $interval = $now->diff($job_date);
                    $time_remaining = ($interval->d > 0 ? $interval->d . ' يوم ' : '') . 
                                    ($interval->h > 0 ? $interval->h . ' ساعة' : '');
                ?>
                    <div style="text-align: left;">
                        <p style="font-size: 11px; color: var(--text-dim); margin: 0;">الوظيفة القادمة</p>
                        <p style="font-size: 14px; font-weight: 700; color: var(--warning); margin: 0;">
                            🔥 <?= htmlspecialchars($time_remaining) ?>
                        </p>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- KPI STRIP -->
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-label">إجمالي الأرباح</div>
                <div class="kpi-value"><?= number_format($total_earnings) ?> ر.س</div>
                <div class="kpi-trend trend-up"><i class="fas fa-arrow-up"></i> 15.2%</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">معلق</div>
                <div class="kpi-value"><?= number_format($pending_earnings) ?> ر.س</div>
                <div class="kpi-trend" style="color: var(--text-dim);">بانتظار التحويل</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">نشط</div>
                <div class="kpi-value"><?= $active_jobs ?></div>
                <div class="kpi-trend" style="color: var(--primary);">جارٍ التنفيذ</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">مكتمل</div>
                <div class="kpi-value"><?= $completed_jobs ?></div>
                <div class="kpi-trend trend-up"><i class="fas fa-check"></i> <?= $acceptance_rate ?>% قبول</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">طلبات</div>
                <div class="kpi-value"><?= $total_applications ?></div>
                <div class="kpi-trend" style="color: var(--warning);"><?= $pending_applications ?> معلق</div>
            </div>
        </div>
        
        <!-- MAIN GRID -->
        <div class="dashboard-grid">
            <!-- LEFT COLUMN -->
            <div>
                <!-- LARGE EARNINGS CHART -->
                <div class="chart-card earnings-large">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">تطور الأرباح</h3>
                            <p class="chart-subtitle">آخر 6 أشهر</p>
                        </div>
                        <div style="font-size: 24px; font-weight: 800; color: var(--success);">
                            +<?= number_format($total_earnings) ?> ر.س
                        </div>
                    </div>
                    <canvas id="earningsChart" height="200"></canvas>
                </div>
                
                <!-- PERFORMANCE GRID -->
                <div class="performance-grid">
                    <div class="perf-card">
                        <h4 class="chart-title" style="margin-bottom: 16px;">معدل القبول</h4>
                        <div class="perf-circle">
                            <canvas id="acceptanceChart"></canvas>
                        </div>
                        <div class="perf-value"><?= $acceptance_rate ?>%</div>
                        <div class="perf-label"><?= $accepted_applications ?> من <?= $total_applications ?> طلب</div>
                    </div>
                    
                    <div class="perf-card">
                        <h4 class="chart-title" style="margin-bottom: 16px;">توزيع الطلبات</h4>
                        <div class="perf-circle">
                            <canvas id="applicationsChart"></canvas>
                        </div>
                        <div class="perf-value"><?= $total_applications ?></div>
                        <div class="perf-label">إجمالي الطلبات</div>
                    </div>
                    
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <h4 class="chart-title">الفئات الأكثر دخلاً</h4>
                        <div class="category-list" style="margin-top: 16px;">
                            <?php 
                            $total_cat = array_sum(array_column($category_data, 'value'));
                            foreach($category_data as $cat): 
                                $percent = $total_cat > 0 ? ($cat['value'] / $total_cat) * 100 : 0;
                                $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
                            ?>
                                <div class="category-item">
                                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                    <div class="category-bar">
                                        <div class="category-fill" style="width: <?= $percent ?>%; background: <?= $colors[array_search($cat, $category_data)] ?>;"></div>
                                    </div>
                                    <span class="category-count"><?= $cat['value'] ?> وظيفة</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT SIDEBAR -->
            <div>
                <!-- LEADERBOARD -->
                <div class="sidebar-section">
                    <h4 class="sidebar-title">🏆 الأفضل هذا الأسبوع</h4>
                    <?php foreach($leaderboard as $leader): ?>
                        <div class="leader-item <?= !empty($leader['is_current']) ? 'current' : '' ?>">
                            <div class="rank">#<?= $leader['rank'] ?></div>
                            <div class="leader-info">
                                <p class="leader-name"><?= htmlspecialchars($leader['name']) ?> <?= !empty($leader['is_current']) ? '⭐' : '' ?></p>
                                <p class="leader-stats"><?= number_format($leader['earnings']) ?> ر.س • <?= $leader['jobs'] ?> وظيفة</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- RECENT ACTIVITY -->
                <div class="sidebar-section">
                    <h4 class="sidebar-title">📊 النشاط الحديث</h4>
                    <?php if (empty($recent_activity)): ?>
                        <p style="font-size: 12px; color: var(--text-dim); text-align: center; padding: 20px 0;">لا يوجد نشاط حديث</p>
                    <?php else: ?>
                        <?php foreach(array_slice($recent_activity, 0, 6) as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background: <?= $activity['type'] == 'job_completed' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(59, 130, 246, 0.1)' ?>; color: <?= $activity['type'] == 'job_completed' ? 'var(--success)' : 'var(--primary)' ?>;">
                                    <i class="fas <?= $activity['type'] == 'job_completed' ? 'fa-check' : 'fa-paper-plane' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-title"><?= htmlspecialchars(mb_substr($activity['title'], 0, 30)) ?><?= mb_strlen($activity['title']) > 30 ? '...' : '' ?></p>
                                    <p class="activity-time"><?= timeAgo($activity['created_at']) ?></p>
                                </div>
                                <?php if (!empty($activity['amount'])): ?>
                                    <div style="font-size: 12px; font-weight: 700; color: var(--success);">
                                        +<?= number_format($activity['amount']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- QUICK ACTIONS -->
                <div class="sidebar-section">
                    <h4 class="sidebar-title">⚡ إجراءات سريعة</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="find-work.php" style="padding: 10px; background: rgba(59, 130, 246, 0.1); border: 1px solid var(--primary); border-radius: 8px; text-decoration: none; color: var(--primary); font-size: 13px; font-weight: 600; text-align: center;">
                            <i class="fas fa-search"></i> ابحث عن عمل
                        </a>
                        <a href="profile.php" style="padding: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; text-decoration: none; color: var(--text); font-size: 13px; font-weight: 600; text-align: center;">
                            <i class="fas fa-user-edit"></i> تحديث الملف
                        </a>
                        <a href="finances.php" style="padding: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; text-decoration: none; color: var(--text); font-size: 13px; font-weight: 600; text-align: center;">
                            <i class="fas fa-wallet"></i> الأرباح
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // EARNINGS LINE CHART
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [{
                    label: 'الأرباح',
                    data: <?= json_encode($monthly_earnings) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#e2e8f0',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString() + ' ر.س';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: { 
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: function(value) {
                                return (value/1000).toFixed(0) + 'k';
                            }
                        }
                    }
                }
            }
        });
        
        // ACCEPTANCE RATE DOUGHNUT
        const acceptanceCtx = document.getElementById('acceptanceChart').getContext('2d');
        new Chart(acceptanceCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?= $acceptance_rate ?>, <?= 100 - $acceptance_rate ?>],
                    backgroundColor: ['#10b981', 'rgba(255,255,255,0.05)'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '75%',
                plugins: { 
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
        
        // APPLICATIONS PIE CHART
        const applicationsCtx = document.getElementById('applicationsChart').getContext('2d');
        new Chart(applicationsCtx, {
            type: 'doughnut',
            data: {
                labels: ['مقبول', 'معلق', 'مرفوض'],
                datasets: [{
                    data: [<?= $accepted_applications ?>, <?= $pending_applications ?>, <?= $rejected_applications ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: { 
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            padding: 8,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#e2e8f0',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1
                    }
                }
            }
        });
    </script>
</body>
</html>