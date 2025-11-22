<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Get financial data
try {
    // Total earnings from jobs
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_earnings FROM freelancer_jobs WHERE freelancer_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $total_earnings = $stmt->fetch()['total_earnings'];
    
    // Pending earnings
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as pending_earnings FROM freelancer_jobs WHERE freelancer_id = ? AND status = 'in_progress'");
    $stmt->execute([$user_id]);
    $pending_earnings = $stmt->fetch()['pending_earnings'];
    
    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT 'job' as type, job_title as description, amount, job_date as date, status 
        FROM freelancer_jobs 
        WHERE freelancer_id = ? 
        ORDER BY job_date DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $total_earnings = 0;
    $pending_earnings = 0;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #333;
        }
        
        .stat-card p {
            color: #999;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Section Card */
        .section-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            margin-bottom: 25px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .table th {
            padding: 15px 12px;
            text-align: right;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(0,0,0,0.1);
            background: var(--neumorphic-bg);
        }
        
        .table td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover td {
            background: rgba(255,255,255,0.5);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: var(--success-color);
        }
        
        .status-pending {
            background: #fff3e0;
            color: var(--warning-color);
        }
        
        .status-in-progress {
            background: #e3f2fd;
            color: var(--primary-color);
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-state h5 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #666;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .table {
                font-size: 12px;
            }
            .table th,
            .table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <<?php include '../includes/sidebar-freelancer.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h2>الأرباح 💰</h2>
                <p>إدارة أموالك وإيراداتك</p>
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
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--success-color)); color: white;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3><?= formatMoney($total_earnings) ?></h3>
                <p>إجمالي الأرباح</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #f5576c); color: white;">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?= formatMoney($pending_earnings) ?></h3>
                <p>قيد الانتظار</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, var(--success-color)); color: white;">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3><?= formatMoney($total_earnings - $pending_earnings) ?></h3>
                <p>متاح للسحب</p>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="section-card">
            <div class="section-header">
                <h4>
                    <i class="fas fa-history"></i>
                    آخر المعاملات
                </h4>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-exchange-alt"></i>
                    <h5>لا توجد معاملات حتى الآن</h5>
                    <p>ستظهر معاملاتك هنا عند بدء العمل على المشاريع</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الوصف</th>
                                <th>المبلغ</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 40px; height: 40px; border-radius: 8px; background: linear-gradient(135deg, var(--primary-color), var(--success-color)); display: flex; align-items: center; justify-content: center; color: white;">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #333;"><?= htmlspecialchars($transaction['description']) ?></div>
                                            <div style="font-size: 12px; color: #999;">مشروع</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight: 700; color: var(--primary-color);"><?= formatMoney($transaction['amount']) ?></td>
                                <td>
                                    <div style="color: #333; font-weight: 600;"><?= date('Y-m-d', strtotime($transaction['date'])) ?></div>
                                    <div style="font-size: 12px; color: #999;"><?= date('h:i A', strtotime($transaction['date'])) ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    $status_text = '';
                                    switch($transaction['status']) {
                                        case 'completed': 
                                            $status_class = 'status-completed';
                                            $status_text = 'مكتمل';
                                            break;
                                        case 'in_progress': 
                                            $status_class = 'status-in-progress';
                                            $status_text = 'قيد التنفيذ';
                                            break;
                                        default: 
                                            $status_class = 'status-pending';
                                            $status_text = 'معلق';
                                    }
                                    ?>
                                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Withdrawal Section -->
        <div class="section-card">
            <div class="section-header">
                <h4>
                    <i class="fas fa-credit-card"></i>
                    طلبات السحب
                </h4>
                <button class="btn-primary-neumorphic" onclick="alert('سيتم إضافة إمكانية السحب قريباً')">
                    <i class="fas fa-plus"></i>
                    طلب سحب جديد
                </button>
            </div>
            
            <div class="empty-state">
                <i class="fas fa-money-bill-wave"></i>
                <h5>لا توجد طلبات سحب</h5>
                <p>يمكنك طلب سحب الأموال المتاحة في محفظتك</p>
            </div>
        </div>
        
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add hover effects to stat cards
            $('.stat-card').on('mouseenter', function() {
                $(this).css('transform', 'translateY(-5px)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateY(0)');
            });
            
            // Add click effect to table rows
            $('.table tbody tr').on('click', function() {
                // Add temporary click effect
                $(this).css({
                    'transform': 'scale(0.99)',
                    'transition': 'transform 0.1s'
                });
                
                setTimeout(() => {
                    $(this).css('transform', 'scale(1)');
                }, 100);
                
                // Show transaction details (you can implement this)
                const description = $(this).find('td:first-child div div:first-child').text();
                alert(`تفاصيل المعاملة: ${description}\n\nسيتم إضافة تفاصيل كاملة قريباً`);
            });
        });
    </script>
</body>
</html>