<?php
session_start();
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Add flash functions if they don't exist
if (!function_exists('setFlash')) {
    function setFlash($type, $message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('getFlash')) {
    function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

// ==================== FORM PROCESSING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $lat = trim($_POST['lat'] ?? '');
    $lng = trim($_POST['lng'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $rate_type = $_POST['rate_type'] ?? '';
    $rate_value = $_POST['rate_value'] ?? '';
    $supervisor_name = trim($_POST['supervisor_name'] ?? '');
    $supervisor_phone = trim($_POST['supervisor_phone'] ?? '');
    $brief = trim($_POST['brief'] ?? '');
    
    // Validate
    if (empty($title) || empty($start_time) || empty($end_time) || empty($rate_value) || 
        empty($supervisor_name) || empty($supervisor_phone) || empty($lat) || empty($lng)) {
        setFlash('danger', 'يرجى ملء جميع الحقول المطلوبة');
    } else {
        try {
    $stmt = $pdo->prepare("
        INSERT INTO gigs (
            freelancer_id, title, venue, address, lat, lng, 
            start_time, end_time, rate_type, rate_value, 
            supervisor_name, supervisor_phone, brief, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming'
        )
    ");
    
    // The execute statement is slightly different for better security and error checking
    if ($stmt->execute([
        $user_id, $title, $venue, $address, (float)$lat, (float)$lng, 
        $start_time, $end_time, $rate_type, (float)$rate_value, 
        $supervisor_name, $supervisor_phone, $brief
    ])) {
        setFlash('success', 'تم إضافة المهمة بنجاح! 🎉');
        header('Location: dashboard.php');
        exit;
    } else {
        setFlash('danger', 'حدث خطأ أثناء حفظ المهمة، لم يتم الحفظ.');
    }
    
    } catch (PDOException $e) {
        setFlash('danger', 'حدث خطأ في قاعدة البيانات.');
        // Optional: Log the detailed error for your own debugging
        // error_log("Gig insert error: " . $e->getMessage());
    }
    
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>إضافة مهمة جديدة - <?= SITE_NAME ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    
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
        
        /* Page Header */
        .page-header {
            margin-bottom: 25px;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        
        .page-header p {
            color: #999;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .btn-primary-neumorphic {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            box-shadow: 
                4px 4px 12px rgba(23, 79, 132, 0.3),
                -2px -2px 6px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
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
        
        /* Flash Messages */
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
        
        /* Form Container */
        .form-container {
            background: var(--neumorphic-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-label .required {
            color: var(--danger-color);
        }
        
        .form-control-neumorphic {
            background: var(--neumorphic-bg);
            border: none;
            border-radius: 10px;
            padding: 12px 15px;
            width: 100%;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
            color: #333;
        }
        
        .form-control-neumorphic:focus {
            outline: none;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        /* Map Container */
        #map {
            height: 350px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            margin-bottom: 15px;
        }
        
        .map-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .btn-map {
            background: var(--neumorphic-bg);
            border: none;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-map:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        .btn-map i {
            color: var(--primary-color);
        }
        
        .map-hint {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 15px;
            border-right: 4px solid #ffc107;
        }
        
        .coordinates-display {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 600;
            display: none;
        }
        
        /* Buttons */
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 35px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 10px;
            box-shadow: 
                4px 4px 12px rgba(23, 79, 132, 0.3),
                -2px -2px 6px rgba(255, 255, 255, 0.8);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: var(--neumorphic-bg);
            color: #666;
            border: none;
            padding: 15px 35px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 10px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            transform: translateY(-2px);
            color: #666;
            text-decoration: none;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-size: 1.2rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { 
                transform: translateX(100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
                padding: 15px;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-buttons {
                flex-direction: column;
            }
            
            .btn-submit, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
            
            #map {
                height: 250px;
            }
        }
    </style>
</head>
<body>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <?php include '../includes/sidebar-freelancer.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2>إضافة مهمة ميدانية جديدة 📋</h2>
                <p>املأ تفاصيل المهمة وحدد الموقع على الخريطة</p>
            </div>
            <a href="dashboard.php" class="btn-primary-neumorphic">
                <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
            </a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Form Container -->
        <div class="form-container">
            <form method="POST" id="gigForm">
                
                <!-- Basic Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-info-circle"></i> المعلومات الأساسية
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            عنوان المهمة <span class="required">*</span>
                        </label>
                        <input type="text" name="title" class="form-control-neumorphic" 
                               placeholder="مثال: مروج للعلامة التجارية X في مول الرياض" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                تاريخ ووقت البدء <span class="required">*</span>
                            </label>
                            <input type="datetime-local" name="start_time" class="form-control-neumorphic" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                تاريخ ووقت الانتهاء <span class="required">*</span>
                            </label>
                            <input type="datetime-local" name="end_time" class="form-control-neumorphic" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                نوع الأجر <span class="required">*</span>
                            </label>
                            <select name="rate_type" class="form-control-neumorphic" required>
                                <option value="">اختر نوع الأجر</option>
                                <option value="hourly">بالساعة</option>
                                <option value="fixed">مبلغ ثابت</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                قيمة الأجر (ر.س) <span class="required">*</span>
                            </label>
                            <input type="number" step="0.01" name="rate_value" class="form-control-neumorphic" 
                                   placeholder="100.00" required>
                        </div>
                    </div>
                </div>
                
                <!-- Location Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i> موقع المهمة
                    </div>
                    
                    <div class="map-hint">
                        <i class="fas fa-info-circle"></i>
                        <strong>كيفية تحديد الموقع:</strong>
                        اضغط على الخريطة لتحديد الموقع الدقيق، أو استخدم زر "موقعي الحالي"
                    </div>
                    
                    <div class="map-controls">
                        <button type="button" class="btn-map" id="btnCurrentLocation">
                            <i class="fas fa-crosshairs"></i> موقعي الحالي
                        </button>
                        <button type="button" class="btn-map" id="btnClearMap">
                            <i class="fas fa-trash"></i> مسح التحديد
                        </button>
                    </div>
                    
                    <div id="map"></div>
                    
                    <div class="coordinates-display" id="coordinatesDisplay">
                        <i class="fas fa-check-circle"></i>
                        تم تحديد الموقع: <span id="coordText"></span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            اسم الموقع
                        </label>
                        <input type="text" name="venue" id="venue" class="form-control-neumorphic" 
                               placeholder="مثال: مول الرياض، الطابق الأرضي">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            العنوان الكامل
                        </label>
                        <input type="text" name="address" id="address" class="form-control-neumorphic" 
                               placeholder="سيتم ملؤه تلقائياً من الخريطة" readonly>
                    </div>
                    
                    <input type="hidden" name="lat" id="lat" required>
                    <input type="hidden" name="lng" id="lng" required>
                </div>
                
                <!-- Supervisor Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user-tie"></i> معلومات المشرف
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                اسم المشرف <span class="required">*</span>
                            </label>
                            <input type="text" name="supervisor_name" class="form-control-neumorphic" 
                                   placeholder="فيصل أحمد" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                رقم هاتف المشرف <span class="required">*</span>
                            </label>
                            <input type="tel" name="supervisor_phone" class="form-control-neumorphic" 
                                   placeholder="05XXXXXXXX" required>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-clipboard-list"></i> النبذة والتعليمات
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">تعليمات المهمة</label>
                        <textarea name="brief" class="form-control-neumorphic" rows="5" 
                                  placeholder="مثال:&#10;الزي: أسود كامل + حذاء رياضي&#10;المهام: توزيع منشورات + توجيه الزوار&#10;واي فاي: MallRiyadh2025"></textarea>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="form-buttons">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> حفظ المهمة
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
                
            </form>
        </div>
        
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Mobile Menu Toggle
        document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('active');
        });

        // Initialize map (centered on Riyadh)
        const map = L.map('map').setView([24.7136, 46.6753], 12);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        let marker = null;
        
        // Function to update form fields
        function updateFormFields(lat, lng) {
            document.getElementById('lat').value = lat.toFixed(6);
            document.getElementById('lng').value = lng.toFixed(6);
            document.getElementById('coordinatesDisplay').style.display = 'block';
            document.getElementById('coordText').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            
            // Reverse geocoding using Nominatim
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=ar`)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name) {
                        document.getElementById('address').value = data.display_name;
                    }
                })
                .catch(err => console.log('Geocoding error:', err));
        }
        
        // Add marker on map click
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            
            marker = L.marker(e.latlng).addTo(map);
            updateFormFields(e.latlng.lat, e.latlng.lng);
        });
        
        // Current location button
        document.getElementById('btnCurrentLocation').addEventListener('click', function() {
            if ('geolocation' in navigator) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ التحديد...';
                this.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        if (marker) map.removeLayer(marker);
                        marker = L.marker([lat, lng]).addTo(map);
                        map.setView([lat, lng], 15);
                        updateFormFields(lat, lng);
                        
                        this.innerHTML = '<i class="fas fa-crosshairs"></i> موقعي الحالي';
                        this.disabled = false;
                    },
                    (error) => {
                        alert('تعذر تحديد الموقع. يرجى السماح بالوصول للموقع.');
                        this.innerHTML = '<i class="fas fa-crosshairs"></i> موقعي الحالي';
                        this.disabled = false;
                    }
                );
            } else {
                alert('المتصفح لا يدعم تحديد الموقع');
            }
        });
        
        // Clear map button
        document.getElementById('btnClearMap').addEventListener('click', function() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            document.getElementById('lat').value = '';
            document.getElementById('lng').value = '';
            document.getElementById('address').value = '';
            document.getElementById('coordinatesDisplay').style.display = 'none';
        });
        
        // Form validation
        document.getElementById('gigForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('lat').value;
            const lng = document.getElementById('lng').value;
            
            if (!lat || !lng) {
                e.preventDefault();
                alert('يرجى تحديد موقع المهمة على الخريطة');
                return false;
            }
            
            const startTime = new Date(document.querySelector('[name="start_time"]').value);
            const endTime = new Date(document.querySelector('[name="end_time"]').value);
            
            if (endTime <= startTime) {
                e.preventDefault();
                alert('وقت الانتهاء يجب أن يكون بعد وقت البدء');
                return false;
            }
        });
    </script>
</body>
</html>