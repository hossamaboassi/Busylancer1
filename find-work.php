<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Handle job application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    try {
    $job_id = intval($_POST['job_id']);
    $cover_letter = clean($_POST['cover_letter']);
    
    // Check if already applied
    $checkStmt = $pdo->prepare("SELECT id FROM freelancer_applications WHERE freelancer_id = ? AND job_id = ?");
    $checkStmt->execute([$user_id, $job_id]);
    
    if ($checkStmt->fetch()) {
        setFlash('warning', 'لقد تقدمت لهذه الوظيفة من قبل');
    } else {
        // Insert application
        $insertStmt = $pdo->prepare("
            INSERT INTO freelancer_applications (freelancer_id, job_id, cover_letter, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        
        if ($insertStmt->execute([$user_id, $job_id, $cover_letter])) {
            setFlash('success', 'تم تقديم طلبك بنجاح! سيتم إشعارك عند المراجعة');
        } else {
            setFlash('danger', 'حدث خطأ أثناء تقديم الطلب، لم يتم الحفظ.');
        }
    }
    } catch(PDOException $e) {
        setFlash('danger', 'حدث خطأ في قاعدة البيانات.');
        // Optional: Log the detailed error for your own debugging
        // error_log("Application error: " . $e->getMessage());
    }

    
    header('Location: find-work.php');
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$min_budget = $_GET['min_budget'] ?? '';
$max_budget = $_GET['max_budget'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$query = "
    SELECT j.*, 
           u.company_name, u.rating as business_rating,
           (SELECT COUNT(*) FROM freelancer_applications WHERE job_id = j.id) as applications_count,
           (SELECT COUNT(*) FROM freelancer_applications WHERE job_id = j.id AND freelancer_id = ?) as user_applied
    FROM jobs j
    JOIN users u ON j.business_id = u.id
    WHERE j.status = 'active'
";

$params = [$user_id];

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND j.category = ?";
    $params[] = $category;
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($job_type)) {
    $query .= " AND j.job_type = ?";
    $params[] = $job_type;
}

if (!empty($min_budget)) {
    $query .= " AND j.hourly_rate >= ?";
    $params[] = $min_budget;
}

if (!empty($max_budget)) {
    $query .= " AND j.hourly_rate <= ?";
    $params[] = $max_budget;
}

// Sort
switch ($sort) {
    case 'newest':
        $query .= " ORDER BY j.created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY j.created_at ASC";
        break;
    case 'highest_paid':
        $query .= " ORDER BY j.hourly_rate DESC";
        break;
    case 'lowest_paid':
        $query .= " ORDER BY j.hourly_rate ASC";
        break;
    default:
        $query .= " ORDER BY j.created_at DESC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $pdo->query("SELECT DISTINCT category FROM jobs WHERE status = 'active' AND category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get locations for filter
    $stmt = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'active' AND location IS NOT NULL ORDER BY location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    $jobs = [];
    $categories = [];
    $locations = [];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>البحث عن عمل - <?= SITE_NAME ?></title>
    
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
        
        /* Search Section */
        .search-section {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            margin-bottom: 25px;
        }
        
        .main-search {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-input-wrapper {
            flex: 1;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            color: #333;
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .search-input:focus {
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }
        
        .btn-search {
            padding: 15px 30px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            background: var(--primary-color);
            color: white;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
        }
        
        /* Filters */
        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .filter-select {
            width: 100%;
            padding: 12px 15px;
            font-size: 13px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            cursor: pointer;
        }
        
        .filter-select:focus {
            outline: none;
        }
        
        /* Results Bar */
        .results-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .results-count {
            font-size: 14px;
            font-weight: 600;
            color: #666;
        }
        
        .results-count span {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .sort-select {
            padding: 8px 15px;
            font-size: 13px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 8px;
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
            cursor: pointer;
        }
        
        /* Job Cards */
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
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 
                12px 12px 24px var(--neumorphic-dark),
                -12px -12px 24px var(--neumorphic-light);
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .job-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .job-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            background: var(--primary-color);
            color: white;
            box-shadow: 
                3px 3px 6px rgba(23, 79, 132, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }
        
        .business-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .business-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #2c5f9b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 
                3px 3px 6px var(--neumorphic-dark),
                -3px -3px 6px var(--neumorphic-light);
        }
        
        .business-details {
            flex: 1;
        }
        
        .business-name {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 3px;
        }
        
        .business-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #999;
        }
        
        .rating-stars {
            color: #FFD700;
        }
        
        .job-description {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .job-meta-item i {
            color: var(--primary-color);
            font-size: 12px;
        }
        
        .job-budget {
            font-size: 18px;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 15px;
        }
        
        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid rgba(0,0,0,0.05);
        }
        
        .job-time {
            font-size: 11px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .job-applicants {
            font-size: 11px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-apply {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            background: var(--success-color);
            color: white;
            box-shadow: 
                5px 5px 15px rgba(0, 191, 154, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
        }
        
        .btn-apply:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-applied {
            background: #e3f2fd;
            color: var(--primary-color);
            cursor: default;
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 70px;
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
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--neumorphic-bg);
            border-radius: 15px;
            padding: 25px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 
                15px 15px 30px var(--neumorphic-dark),
                -15px -15px 30px var(--neumorphic-light);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .modal-close {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background: var(--neumorphic-bg);
            color: #666;
            cursor: pointer;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .modal-job-info {
            padding: 15px;
            background: rgba(255,255,255,0.5);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .modal-job-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .modal-job-budget {
            font-size: 14px;
            font-weight: 700;
            color: var(--success-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            font-size: 13px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            color: #333;
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            resize: vertical;
            min-height: 120px;
        }
        
        .form-textarea:focus {
            outline: none;
        }
        
        .char-count {
            text-align: left;
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-cancel {
            flex: 1;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            background: var(--neumorphic-bg);
            color: #666;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-submit {
            flex: 2;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            background: var(--success-color);
            color: white;
            box-shadow: 
                5px 5px 15px rgba(0, 191, 154, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
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
            .main-search {
                flex-direction: column;
            }
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            .filters-row {
                grid-template-columns: 1fr;
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
                <h2>البحث عن عمل 🔍</h2>
                <p>اكتشف آلاف الفرص المتاحة واختر ما يناسب مهاراتك</p>
            </div>
            <a href="dashboard.php" class="btn-primary-neumorphic">
                <i class="fas fa-arrow-right"></i>
                العودة للوحة التحكم
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" action="">
                <div class="main-search">
                    <div class="search-input-wrapper">
                        <input type="text" name="search" class="search-input" 
                               placeholder="ابحث عن وظيفة (مثال: تصميم, برمجة, كتابة...)"
                               value="<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                        بحث
                    </button>
                </div>
                
                <div class="filters-row">
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">جميع التصنيفات</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="location" class="filter-select" onchange="this.form.submit()">
                        <option value="">جميع المواقع</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="job_type" class="filter-select" onchange="this.form.submit()">
                        <option value="">نوع الوظيفة</option>
                        <option value="full-time" <?= $job_type === 'full-time' ? 'selected' : '' ?>>دوام كامل</option>
                        <option value="part-time" <?= $job_type === 'part-time' ? 'selected' : '' ?>>دوام جزئي</option>
                        <option value="contract" <?= $job_type === 'contract' ? 'selected' : '' ?>>عقد</option>
                        <option value="freelance" <?= $job_type === 'freelance' ? 'selected' : '' ?>>عمل حر</option>
                    </select>
                    
                    <input type="number" name="min_budget" class="filter-select" 
                           placeholder="الحد الأدنى للميزانية"
                           value="<?= htmlspecialchars($min_budget) ?>">
                    
                    <input type="number" name="max_budget" class="filter-select" 
                           placeholder="الحد الأقصى للميزانية"
                           value="<?= htmlspecialchars($max_budget) ?>">
                </div>
            </form>
        </div>
        
        <!-- Results Bar -->
        <div class="results-bar">
            <div class="results-count">
                تم العثور على <span><?= count($jobs) ?></span> وظيفة متاحة
            </div>
            
            <form method="GET" action="" style="margin: 0;">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                <?php if (!empty($category)): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                <?php endif; ?>
                <?php if (!empty($location)): ?>
                    <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
                <?php endif; ?>
                
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>الأحدث</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>الأقدم</option>
                    <option value="highest_paid" <?= $sort === 'highest_paid' ? 'selected' : '' ?>>الأعلى أجراً</option>
                    <option value="lowest_paid" <?= $sort === 'lowest_paid' ? 'selected' : '' ?>>الأقل أجراً</option>
                </select>
            </form>
        </div>
        
        <!-- Jobs Grid -->
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>لا توجد وظائف متاحة</h3>
                <p>
                    <?php if (!empty($search) || !empty($category) || !empty($location)): ?>
                        لم نجد وظائف تطابق معايير البحث الخاصة بك<br>
                        جرب تعديل الفلاتر أو البحث بكلمات أخرى
                    <?php else: ?>
                        لا توجد وظائف متاحة حالياً<br>
                        يرجى المحاولة مرة أخرى لاحقاً
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div style="flex: 1;">
                                <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                            </div>
                            <?php if (!empty($job['category'])): ?>
                                <div class="job-badge"><?= htmlspecialchars($job['category']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="business-info">
                            <div class="business-avatar">
                                <?= strtoupper(substr($job['company_name'] ?? 'B', 0, 1)) ?>
                            </div>
                            <div class="business-details">
                                <div class="business-name"><?= htmlspecialchars($job['company_name'] ?? 'شركة') ?></div>
                                <div class="business-rating">
                                    <span class="rating-stars">
                                        <?php for($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?= $i < floor($job['business_rating']) ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span><?= number_format($job['business_rating'], 1) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-description">
                            <?= nl2br(htmlspecialchars($job['description'])) ?>
                        </div>
                        
                        <div class="job-meta">
                            <?php if (!empty($job['location'])): ?>
                                <div class="job-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($job['location']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['job_type'])): ?>
                                <div class="job-meta-item">
                                    <i class="fas fa-briefcase"></i>
                                    <?php
                                    $job_types = [
                                        'full-time' => 'دوام كامل',
                                        'part-time' => 'دوام جزئي',
                                        'contract' => 'عقد',
                                        'freelance' => 'عمل حر'
                                    ];
                                    echo $job_types[$job['job_type']] ?? $job['job_type'];
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-budget">
                            <?= formatMoney($job['hourly_rate']) ?>
                            <span style="font-size: 13px; font-weight: 400; color: #999;">/ ساعة</span>
                        </div>
                        
                        <div class="job-footer">
                            <div class="job-time">
                                <i class="fas fa-clock"></i>
                                <?= timeAgo($job['created_at']) ?>
                            </div>
                            <div class="job-applicants">
                                <i class="fas fa-users"></i>
                                <?= $job['applications_count'] ?> متقدم
                            </div>
                        </div>
                        
                        <?php if ($job['user_applied'] > 0): ?>
                            <button class="btn-apply btn-applied" disabled>
                                <i class="fas fa-check"></i>
                                تم التقديم
                            </button>
                        <?php else: ?>
                            <button class="btn-apply" onclick="openApplyModal(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>', '<?= formatMoney($job['hourly_rate']) ?>')">
                                <i class="fas fa-paper-plane"></i>
                                تقدم الآن
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Application Modal -->
    <div class="modal-overlay" id="applyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>التقديم على الوظيفة</h3>
                <button class="modal-close" onclick="closeApplyModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-job-info">
                <div class="modal-job-title" id="modalJobTitle"></div>
                <div class="modal-job-budget" id="modalJobBudget"></div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="job_id" id="modalJobId">
                
                <div class="form-group">
                    <label class="form-label">رسالة التقديم *</label>
                    <textarea name="cover_letter" class="form-textarea" 
                              placeholder="اكتب رسالة تقديم مقنعة توضح فيها:
- لماذا أنت مناسب لهذه الوظيفة؟
- ما هي خبراتك ذات الصلة؟
- ما الذي يمكنك تقديمه لهذا المشروع؟"
                              required
                              maxlength="1000"
                              oninput="updateCharCount(this)"></textarea>
                    <div class="char-count">
                        <span id="charCount">0</span> / 1000 حرف
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeApplyModal()">
                        إلغاء
                    </button>
                    <button type="submit" name="apply" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApplyModal(jobId, jobTitle, jobBudget) {
            document.getElementById('modalJobId').value = jobId;
            document.getElementById('modalJobTitle').textContent = jobTitle;
            document.getElementById('modalJobBudget').textContent = jobBudget + ' / ساعة';
            document.getElementById('applyModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeApplyModal() {
            document.getElementById('applyModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        function updateCharCount(textarea) {
            const count = textarea.value.length;
            document.getElementById('charCount').textContent = count;
        }
        
        // Close modal when clicking outside
        document.getElementById('applyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApplyModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApplyModal();
            }
        });
    </script>

</body>
</html>