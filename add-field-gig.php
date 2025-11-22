<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user_id = $_SESSION['user_id'];
$active_page = 'my-jobs';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (PHP code for saving is correct, no changes needed here) ...
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
    
    if (empty($title) || empty($start_time) || empty($end_time) || empty($rate_value) || empty($supervisor_name) || empty($supervisor_phone) || empty($lat) || empty($lng)) {
        setFlash('danger', 'يرجى ملء جميع الحقول المطلوبة وتحديد الموقع على الخريطة.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO gigs (freelancer_id, title, venue, address, lat, lng, start_time, end_time, rate_type, rate_value, supervisor_name, supervisor_phone, brief, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')");
            if ($stmt->execute([$user_id, $title, $venue, $address, (float)$lat, (float)$lng, $start_time, $end_time, $rate_type, (float)$rate_value, $supervisor_name, $supervisor_phone, $brief])) {
                setFlash('success', 'تم إضافة المهمة الميدانية بنجاح!');
                redirect('my-jobs.php');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'حدث خطأ في قاعدة البيانات.');
            error_log("Field Gig insert error: " . $e->getMessage());
        }
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة مهمة ميدانية - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sidebar.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    
    <style>
        :root {
            --neumorphic-bg: #e9ecef;
            --neumorphic-light: #ffffff;
            --neumorphic-dark: rgba(174, 174, 192, 0.4);
            --primary-color: #174F84;
        }
        body { background: var(--neumorphic-bg); font-family: 'Cairo', sans-serif; }
        .main-content { margin-right: 260px; padding: 25px; }
        .page-header h2 { font-weight: 700; color: #333; }
        .page-header p { color: #6c757d; }
        .section-card { background: var(--neumorphic-bg); border-radius: 15px; padding: 30px; box-shadow: 10px 10px 20px var(--neumorphic-dark), -10px -10px 20px var(--neumorphic-light); }
        .form-group label { font-weight: 600; font-size: 14px; color: #333; margin-bottom: 8px; }
        .form-control-neumorphic { width: 100%; padding: 12px 18px; border: none; border-radius: 10px; background: var(--neumorphic-bg); box-shadow: inset 4px 4px 8px var(--neumorphic-dark), inset -4px -4px 8px var(--neumorphic-light); font-size: 14px; }
        .btn-primary-neumorphic { background: var(--primary-color); color: white; border: none; padding: 10px 20px; font-weight: 600; border-radius: 10px; box-shadow: 4px 4px 12px rgba(23, 79, 132, 0.3), -2px -2px 6px rgba(255, 255, 255, 0.8); }
        #map { height: 350px; border-radius: 12px; box-shadow: 4px 4px 8px var(--neumorphic-dark), -4px -4px 8px var(--neumorphic-light); }
        .section-title { font-weight: 700; font-size: 18px; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 10px; margin-bottom: 20px; }
        @media (max-width: 992px) { .main-content { margin-right: 0; } }
    </style>
</head>
<body>
    <?php include '../includes/sidebar-freelancer.php'; ?>
    
    <div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-map-marker-alt text-primary"></i> إضافة مهمة ميدانية</h2>
                <p>سجل تفاصيل المهمة وحدد موقعها على الخريطة لتفعيل تتبع الحضور.</p>
            </div>
             <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-right ml-2"></i> العودة للرئيسية</a>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <div class="section-card">
            <form method="POST" id="gigForm">
                <div class="section-title"><i class="fas fa-info-circle text-primary mr-2"></i> معلومات المهمة</div>
                <div class="form-group"><label>عنوان المهمة *</label><input type="text" name="title" class="form-control form-control-neumorphic" required></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>وقت البدء *</label><input type="datetime-local" name="start_time" class="form-control form-control-neumorphic" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>وقت الانتهاء *</label><input type="datetime-local" name="end_time" class="form-control form-control-neumorphic" required></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>نوع الأجر *</label><select name="rate_type" class="form-control form-control-neumorphic" required><option value="hourly">بالساعة</option><option value="fixed">مبلغ ثابت</option></select></div></div>
                    <div class="col-md-6"><div class="form-group"><label>قيمة الأجر (ر.س) *</label><input type="number" step="0.01" name="rate_value" class="form-control form-control-neumorphic" required></div></div>
                </div>

                <div class="section-title mt-4"><i class="fas fa-map-marked-alt text-primary mr-2"></i> موقع المهمة</div>
                <p class="text-muted small">انقر على الخريطة لتحديد الموقع الدقيق للمهمة.</p>
                <div id="map" class="mb-3"></div>
                <div class="form-group"><label>اسم المكان (اختياري)</label><input type="text" name="venue" id="venue" class="form-control form-control-neumorphic"></div>
                <div class="form-group"><label>العنوان (يتم ملؤه تلقائياً)</label><input type="text" name="address" id="address" class="form-control form-control-neumorphic" readonly></div>
                <input type="hidden" name="lat" id="lat" required><input type="hidden" name="lng" id="lng" required>

                <div class="section-title mt-4"><i class="fas fa-user-tie text-primary mr-2"></i> معلومات المشرف</div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>اسم المشرف *</label><input type="text" name="supervisor_name" class="form-control form-control-neumorphic" required></div></div>
                    <div class="col-md-6"><div class="form-group"><label>رقم هاتف المشرف *</label><input type="tel" name="supervisor_phone" class="form-control form-control-neumorphic" required></div></div>
                </div>

                <div class="form-group"><label>النبذة والتعليمات</label><textarea name="brief" class="form-control form-control-neumorphic" rows="4"></textarea></div>

                <button type="submit" class="btn btn-primary-neumorphic mt-3"><i class="fas fa-save mr-2"></i> حفظ المهمة الميدانية</button>
            </form>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('map').setView([24.7136, 46.6753], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            let marker;
            map.on('click', function(e) {
                if (marker) { map.removeLayer(marker); }
                marker = L.marker(e.latlng).addTo(map);
                document.getElementById('lat').value = e.latlng.lat.toFixed(7);
                document.getElementById('lng').value = e.latlng.lng.toFixed(7);
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&accept-language=ar`)
                    .then(response => response.json()).then(data => { if (data.display_name) { document.getElementById('address').value = data.display_name; } });
            });
            document.getElementById('gigForm').addEventListener('submit', function(e) { if (!document.getElementById('lat').value) { e.preventDefault(); alert('يرجى تحديد موقع المهمة على الخريطة بالنقر عليها.'); } });
        });
    </script>
</body>
</html>