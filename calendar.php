<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Get month and year from URL or use current
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2030) $year = date('Y');

// Get events (accepted jobs) from the database for the calendar
$events = []; // Start with an empty events array
try {
   // --- Query 1: Get PLATFORM jobs for the calendar ---
$stmt1 = $pdo->prepare("
    SELECT j.title, j.job_date 
    FROM freelancer_applications fa
    JOIN jobs j ON fa.job_id = j.id
    WHERE fa.freelancer_id = ? 
    AND fa.status = 'accepted' 
    AND YEAR(j.job_date) = ? 
    AND MONTH(j.job_date) = ?
");
$stmt1->execute([$user_id, $year, $month]);
$platform_jobs_for_month = $stmt1->fetchAll(PDO::FETCH_ASSOC) ?: [];

// --- Query 2: Get MANUAL jobs for the calendar ---
$stmt2 = $pdo->prepare("
    SELECT job_title as title, job_date
    FROM freelancer_jobs
    WHERE freelancer_id = ?
    AND source = 'manual'
    AND YEAR(job_date) = ?
    AND MONTH(job_date) = ?
");
$stmt2->execute([$user_id, $year, $month]);
$manual_jobs_for_month = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

// --- Combine all jobs for the month ---
$jobs_for_month = array_merge($platform_jobs_for_month, $manual_jobs_for_month);

    // Loop through the jobs and add them to our events array, organized by day number
    foreach ($jobs_for_month as $job) {
        $day = (int)date('j', strtotime($job['job_date'])); // Get just the day number (e.g., 25)
        if (!isset($events[$day])) {
            $events[$day] = [];
        }
        $events[$day][] = $job; // Add this job to the list of events for that specific day
    }

} catch (PDOException $e) {
    // If there is a database error, the calendar will simply appear empty.
    // error_log("Calendar fetch error: " . $e->getMessage()); 
}
// Get upcoming job deadlines from accepted applications
try {
    $stmt = $pdo->prepare("
        SELECT j.title, j.job_date, u.company_name
        FROM freelancer_applications fa
        JOIN jobs j ON fa.job_id = j.id
        JOIN users u ON j.business_id = u.id
        WHERE fa.freelancer_id = ? 
        AND fa.status = 'accepted' 
        AND j.job_date >= CURDATE()
        ORDER BY j.job_date ASC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $upcoming_jobs = $stmt->fetchAll();
} catch(PDOException $e) {
    $upcoming_jobs = [];
}

// Calendar calculations
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$first_weekday = date('w', $first_day); // 0 = Sunday
$month_name = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
$weekdays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

// Previous and next month links
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التقويم - <?= SITE_NAME ?></title>
    
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
        
        /* Page Header */
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
        
        /* Calendar Layout */
        .calendar-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }
        
        /* Calendar Card */
        .calendar-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .calendar-month {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .calendar-month i {
            color: var(--primary-color);
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar-nav a {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            color: #666;
            text-decoration: none;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .calendar-nav a:hover {
            color: var(--primary-color);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        /* Calendar Grid */
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .calendar-weekday {
            text-align: center;
            padding: 12px 5px;
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            background: var(--neumorphic-bg);
            border-radius: 8px;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            background: var(--neumorphic-bg);
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .calendar-day:hover {
            color: var(--primary-color);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .calendar-day.empty {
            background: transparent;
            box-shadow: none;
            cursor: default;
        }
        
        .calendar-day.empty:hover {
            color: #666;
            box-shadow: none;
        }
        
        .calendar-day.today {
            background: linear-gradient(135deg, var(--primary-color), var(--success-color));
            color: white;
            box-shadow: 
                6px 6px 12px var(--neumorphic-dark),
                -6px -6px 12px var(--neumorphic-light);
        }
        
        .calendar-day.has-event::after {
            content: '';
            position: absolute;
            bottom: 6px;
            width: 6px;
            height: 6px;
            background: var(--success-color);
            border-radius: 50%;
        }
        
        .calendar-day.today.has-event::after {
            background: white;
        }
        
        .calendar-day.has-event {
    background: linear-gradient(135deg, var(--warning-color), #f7b733); /* Orange gradient for events */
    color: white;
    font-weight: 700;
    border: 2px solid white; /* Adds a nice border */
    position: relative;
    box-shadow: 6px 6px 12px var(--neumorphic-dark), -6px -6px 12px var(--neumorphic-light);
}
.calendar-day.today.has-event {
    /* Special style if today is also an event day */
    background: linear-gradient(135deg, #ff6b6b, var(--warning-color)); 
}
.calendar-day.has-event::after {
    display: none; 
}
        
        /* Events Sidebar */
        .events-sidebar {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            height: fit-content;
        }
        
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .events-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .events-header h4 i {
            color: var(--primary-color);
        }
        
        .btn-add-event {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--success-color);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 
                4px 4px 8px rgba(0, 191, 154, 0.3),
                -2px -2px 6px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            border: none;
        }
        
        .btn-add-event:hover {
            transform: scale(1.1);
            box-shadow: 
                6px 6px 12px rgba(0, 191, 154, 0.4),
                -3px -3px 8px rgba(255, 255, 255, 0.9);
        }
        
        /* Event Item */
        .event-item {
            background: var(--neumorphic-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            border-right: 4px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .event-item:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .event-date {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #999;
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        .event-date i {
            color: var(--primary-color);
        }
        
        .event-title {
            font-weight: 700;
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .event-client {
            color: #666;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .event-client i {
            color: var(--primary-color);
            font-size: 11px;
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
        
        .empty-state h5 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #666;
        }
        
        .empty-state p {
            margin: 0;
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
        
        /* Quick Stats */
        .quick-stats {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(0,0,0,0.05);
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-item span:first-child {
            color: #666;
            font-size: 13px;
            font-weight: 600;
        }
        
        .stat-item span:last-child {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .calendar-container {
                grid-template-columns: 1fr;
            }
            
            .events-sidebar {
                height: auto;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { 
                transform: translateX(100%); 
            }
            .main-content { 
                margin-right: 0; 
                padding: 15px;
            }
            .calendar-days { 
                gap: 5px; 
            }
            .calendar-weekdays { 
                gap: 5px; 
            }
            .calendar-day { 
                font-size: 12px; 
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include '../includes/sidebar-freelancer.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h2>التقويم 📅</h2>
                <p>جدول أعمالك ومواعيدك القادمة</p>
            </div>
            <a href="find-work.php" class="btn-primary-neumorphic">
                <i class="fas fa-briefcase"></i>
                البحث عن عمل
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="calendar-container">
            
            <!-- Main Calendar -->
            <div class="calendar-card">
                <div class="calendar-header">
                    <div class="calendar-month">
                        <i class="fas fa-calendar-alt"></i>
                        <?= $month_name[$month] ?> <?= $year ?>
                    </div>
                    <div class="calendar-nav">
                        <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>">
                            <i class="fas fa-calendar-day"></i>
                        </a>
                        <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Weekday Headers -->
                <div class="calendar-weekdays">
                    <?php foreach ($weekdays as $day): ?>
                        <div class="calendar-weekday"><?= $day ?></div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Calendar Days -->
                <div class="calendar-days">
                    <?php
                    // Empty cells before first day (adjust for Arabic calendar starting Sunday)
                    for ($i = 0; $i < $first_weekday; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                    
                    // Days of the month
                    $today = date('Y-m-d');
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $is_today = ($date == $today) ? 'today' : '';
                        
                        // Check if this date has events
                        $has_event = isset($events[$day]);
                        foreach ($upcoming_jobs as $job) {
                            if ($job['job_date'] == $date) {
                                $has_event = true;
                                break;
                            }
                        }
                        
                        $event_class = $has_event ? 'has-event' : '';
                        
                        echo "<div class='calendar-day $is_today $event_class' onclick='showDayDetails($day, $month, $year)'>$day</div>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Upcoming Events Sidebar -->
     <div class="events-sidebar">
    <div class="events-header">
        <h4><i class="far fa-calendar-check"></i> المواعيد القادمة</h4>
        <a href="add-manual-gig.php" class="btn-add-event"><i class="fas fa-plus"></i></a>
    </div>
    
    <?php
// Fetch upcoming jobs (both types) for the sidebar list
$upcoming_jobs_list = []; // Initialize to prevent errors
try {
    $upcomingStmt = $pdo->prepare("
        (
            -- Platform Jobs
            SELECT j.title, j.job_date, CONCAT(u.first_name, ' ', u.last_name) as company_name, j.id as job_id, 'platform' as source
            FROM freelancer_applications fa 
            JOIN jobs j ON fa.job_id = j.id 
            JOIN users u ON j.business_id = u.id 
            WHERE fa.freelancer_id = ? AND fa.status = 'accepted' AND j.job_date >= CURDATE()
        )
        UNION ALL
        (
            -- Manual Jobs
            SELECT job_title as title, job_date, employer_name as company_name, id as job_id, 'manual' as source
            FROM freelancer_jobs 
            WHERE freelancer_id = ? AND source = 'manual' AND status = 'in_progress' AND job_date >= CURDATE()
        )
        ORDER BY job_date ASC 
        LIMIT 5
    ");
    $upcomingStmt->execute([$user_id, $user_id]);
    $upcoming_jobs_list = $upcomingStmt->fetchAll();
} catch (PDOException $e) {
    // This will prevent the page from crashing if there's an error
    error_log("Calendar Upcoming Events Error: " . $e->getMessage());
}
?>

    <?php if (empty($upcoming_jobs_list)): ?>
        <div class="empty-state">
            <i class="far fa-calendar"></i>
            <h5>لا توجد مواعيد قادمة</h5>
            <p>سيظهر هنا جدول أعمالك عند قبول الوظائف</p>
        </div>
    <?php else: ?>
        <?php foreach ($upcoming_jobs_list as $job): ?>
            <div class="event-item">
                <div class="event-date">
                    <i class="far fa-clock"></i>
                    <?= date('Y-m-d', strtotime($job['job_date'])) ?>
                </div>
                <div class="event-title"><?= htmlspecialchars($job['title']) ?></div>
                <div class="event-client">
                    <i class="fas fa-building"></i>
                    <?= htmlspecialchars($job['company_name'] ?? 'مهمة يدوية') ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
                
                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-item">
                        <span>المواعيد هذا الشهر</span>
                        <span><?= count($upcoming_jobs) ?></span>
                    </div>
                    <div class="stat-item">
                        <span>المشاريع النشطة</span>
                        <span><?= count(array_filter($upcoming_jobs, function($job) { return strtotime($job['job_date']) >= time(); })) ?></span>
                    </div>
                    <div class="stat-item">
                        <span>المشاريع المكتملة</span>
                        <span>0</span>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // REPLACE WITH THIS
function showDayDetails(day, month, year) {
    // For now, we will just log it. A future feature could be to filter the list.
    console.log(`Clicked on day: ${day}, month: ${month}, year: ${year}`);
    // You can add a link to a day-specific view here later, e.g.:
    // window.location.href = `day-view.php?day=${day}&month=${month}&year=${year}`;
}
            const event = new CustomEvent('showAlert', {
                detail: {
                    title: `تفاصيل يوم ${day}/${month}/${year}`,
                    message: 'سيتم إضافة إمكانية إضافة الأحداث قريباً',
                    type: 'info'
                }
            });
            document.dispatchEvent(event);
            
            // Fallback alert
            alert(`تفاصيل يوم ${day}/${month}/${year}\n\nسيتم إضافة إمكانية إضافة الأحداث قريباً`);
        }
        
        function showAddEventModal() {
            const event = new CustomEvent('showAlert', {
                detail: {
                    title: 'إضافة حدث جديد',
                    message: 'سيتم إضافة إمكانية إضافة الأحداث قريباً',
                    type: 'info'
                }
            });
            document.dispatchEvent(event);
            
            // Fallback alert
            alert('إضافة حدث جديد\n\nسيتم إضافة إمكانية إضافة الأحداث قريباً');
        }
        
        // Add click effect to calendar days
        $(document).ready(function() {
            $('.calendar-day:not(.empty)').on('click', function() {
                // Add temporary click effect
                $(this).css({
                    'transform': 'scale(0.95)',
                    'transition': 'transform 0.1s'
                });
                
                setTimeout(() => {
                    $(this).css('transform', 'scale(1)');
                }, 100);
            });
            
            // Add hover effect to event items
            $('.event-item').on('mouseenter', function() {
                $(this).css('transform', 'translateX(-5px)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateX(0)');
            });
        });
    </script>
</body>
</html>