<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

$user_id = $_SESSION['user_id'];

// --- Main Form Submission Handler ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  // --- SUBMIT: Basic Information ---
if (isset($_POST['update_basic_info'])) {
    try {
        // This query now perfectly matches your 'users' table structure.
        $stmt = $pdo->prepare("
            UPDATE users SET 
                first_name = ?, 
                last_name = ?,
                email = ?, 
                phone = ?, 
                whatsapp = ?,
                city = ?, 
                gender = ?, 
                age = ?, 
                nationality = ?, 
                id_number = ?,
                has_driving_license = ?,
                height = ?,
                weight = ?,
                body_shape = ?,
                hair_color = ?,
                eye_color = ?,
                clothing_type = ?,
                languages = ?
            WHERE id = ?
        ");
        
        $languages_str = implode(',', $_POST['languages'] ?? []);

        $stmt->execute([
            clean($_POST['first_name']),
            clean($_POST['last_name']),
            clean($_POST['email']),
            clean($_POST['phone']),
            clean($_POST['whatsapp']),
            clean($_POST['city']),
            clean($_POST['gender']),
            (int)($_POST['age'] ?? null),
            clean($_POST['nationality']),
            clean($_POST['id_number']),
            clean($_POST['has_driving_license']),
            (int)($_POST['height'] ?? null),
            (int)($_POST['weight'] ?? null),
            clean($_POST['body_shape']),
            clean($_POST['hair_color']),
            clean($_POST['eye_color']),
            clean($_POST['clothing_type']),
            $languages_str,
            $user_id
        ]);

        setFlash('success', 'تم حفظ المعلومات الأساسية بنجاح');

    } catch (PDOException $e) {
        setFlash('danger', 'حدث خطأ في قاعدة البيانات عند حفظ المعلومات الأساسية.');
        // This log is crucial for a developer to see the exact error.
        error_log("Profile Update Error: " . $e->getMessage());
    }
    redirect('profile.php');
}
    // --- SUBMIT: Skills & Work Preferences ---
    if (isset($_POST['update_skills_info'])) {
        try {
            $available_jobs_str = implode(',', $_POST['available_jobs'] ?? []);
            $work_preferences_str = implode(',', $_POST['work_preferences'] ?? []);
            $work_days_str = implode(',', $_POST['work_days'] ?? []);

            $stmt = $pdo->prepare("
                UPDATE users SET 
                    skills = ?, available_jobs = ?, work_preferences = ?, 
                    availability = ?, work_hours = ?, work_days = ?,
                    shift_preference = ?, transportation = ?, holiday_work = ?, 
                    rate_type = ?, hourly_rate = ?, daily_rate = ?, monthly_rate = ?, project_rate = ?
                WHERE id = ?
            ");
            $stmt->execute([
                clean($_POST['skills']), $available_jobs_str, $work_preferences_str, 
                clean($_POST['availability']), clean($_POST['work_hours']), $work_days_str, 
                clean($_POST['shift_preference']), clean($_POST['transportation']),
                clean($_POST['holiday_work']), clean($_POST['rate_type']),
                (float)($_POST['hourly_rate'] ?? 0), (float)($_POST['daily_rate'] ?? 0),
                (float)($_POST['monthly_rate'] ?? 0), (float)($_POST['project_rate'] ?? 0),
                $user_id
            ]);
            setFlash('success', 'تم حفظ المعلومات المهنية بنجاح');
        } catch (PDOException $e) {
            setFlash('danger', 'حدث خطأ في قاعدة البيانات عند حفظ المعلومات المهنية.');
            error_log("Profile Skills Info Error: " . $e->getMessage());
        }
        redirect('profile.php');
    }

    // --- SUBMIT: Bio & Social Media ---
    if (isset($_POST['update_bio_info'])) {
        try {
            // Safely encode social links into a JSON string for the database
            $social_links = json_encode([
                'linkedin' => clean($_POST['linkedin']),
                'instagram' => clean($_POST['instagram']),
                'twitter' => clean($_POST['twitter']),
                'behance' => clean($_POST['behance']),
                'portfolio' => clean($_POST['portfolio'])
            ]);

            $stmt = $pdo->prepare("
                UPDATE users SET bio = ?, recommendations = ?, social_links = ? WHERE id = ?
            ");
            $stmt->execute([
                clean($_POST['bio']), clean($_POST['recommendations']), $social_links, $user_id
            ]);
            setFlash('success', 'تم حفظ المعلومات الإضافية بنجاح');
        } catch (PDOException $e) {
            setFlash('danger', 'حدث خطأ في قاعدة البيانات عند حفظ المعلومات الإضافية.');
            error_log("Profile Bio Info Error: " . $e->getMessage());
        }
        redirect('profile.php');
    }
    
    // The rest of the forms (media, portfolio, experience, education, avatar) are already using
    // prepared statements correctly, so we can leave their logic as is.
    // However, it's good practice to add better error handling.

    // Handle portfolio addition
    if (isset($_POST['add_portfolio'])) {
        $title = clean($_POST['portfolio_title']);
        $category = clean($_POST['portfolio_category']);
        $description = clean($_POST['portfolio_description']);
        $url = clean($_POST['portfolio_url']);
        
        $image = 'default-project.jpg';
        if (isset($_FILES['portfolio_image']) && $_FILES['portfolio_image']['error'] == 0) {
            $uploadDir = '../uploads/portfolio/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $fileName = time() . '_' . basename($_FILES['portfolio_image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['portfolio_image']['tmp_name'], $targetPath)) {
                $image = 'uploads/portfolio/' . $fileName;
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO portfolio (user_id, title, image, category, description, url) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $title, $image, $category, $description, $url])) {
                setFlash('success', 'تم إضافة المشروع إلى محفظتك بنجاح');
            }
        } catch(PDOException $e) {
            setFlash('danger', 'حدث خطأ عند إضافة المشروع.');
        }
        redirect('profile.php');
    }
    
    // Handle experience addition
    if (isset($_POST['add_experience'])) {
        $role = clean($_POST['exp_role']);
        $company = clean($_POST['exp_company']);
        $period = clean($_POST['exp_period']);
        $description = clean($_POST['exp_description']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO experience (user_id, role, company, period, description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $role, $company, $period, $description])) {
                setFlash('success', 'تم إضافة الخبرة العملية بنجاح');
            }
        } catch(PDOException $e) {
             setFlash('danger', 'حدث خطأ عند إضافة الخبرة.');
        }
        redirect('profile.php');
    }
    
    // Handle education addition
    if (isset($_POST['add_education'])) {
        $degree = clean($_POST['edu_degree']);
        $institution = clean($_POST['edu_institution']);
        $period = clean($_POST['edu_period']);
        $description = clean($_POST['edu_description']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO education (user_id, degree, institution, period, description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $degree, $institution, $period, $description])) {
                setFlash('success', 'تم إضافة المؤهل التعليمي بنجاح');
            }
        } catch(PDOException $e) {
            setFlash('danger', 'حدث خطأ عند إضافة المؤهل.');
        }
        redirect('profile.php');
    }
    
   // Handle profile image upload
if (isset($_POST['update_avatar'])) {
    // Check if a file was uploaded without errors
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/avatars/';
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) { 
            mkdir($uploadDir, 0777, true); 
        }

        $fileName = $user_id . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        // --- THIS IS THE CORRECTED LINE ---
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $avatarPath = 'uploads/avatars/' . $fileName;
            try {
                // Your database column for the photo is 'profile_photo', not 'avatar'
                $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                if ($stmt->execute([$avatarPath, $user_id])) {
                    setFlash('success', 'تم تحديث صورة الملف الشخصي بنجاح');
                }
            } catch(PDOException $e) {
                setFlash('danger', 'حدث خطأ عند تحديث الصورة في قاعدة البيانات.');
                error_log("Avatar DB Error: " . $e->getMessage());
            }
        } else {
            setFlash('danger', 'حدث خطأ أثناء رفع الصورة.');
        }
    } else {
        setFlash('warning', 'الرجاء اختيار ملف صورة أولاً.');
    }
    redirect('profile.php');
}
    // Handle media upload
    if (isset($_POST['add_media'])) {
        $media_type = clean($_POST['media_type']);
        $title = clean($_POST['media_title']);
        $description = clean($_POST['media_description']);
        
        $file_path = '';
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
            $uploadDir = '../uploads/media/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $fileName = time() . '_' . basename($_FILES['media_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $targetPath)) {
                $file_path = 'uploads/media/' . $fileName;
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO user_media (user_id, media_type, title, description, file_path) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $media_type, $title, $description, $file_path])) {
                setFlash('success', 'تم إضافة الوسائط بنجاح');
            }
        } catch(PDOException $e) {
            setFlash('danger', 'حدث خطأ عند إضافة الوسائط.');
        }
        redirect('profile.php');
    }
}

// --- Data Fetching for Display ---

// Get main user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { die("User not found."); }

// Get portfolio items
try {
    $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $portfolio = $stmt->fetchAll();
} catch(PDOException $e) { $portfolio = []; }

// Get experience
try {
    $stmt = $pdo->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $experiences = $stmt->fetchAll();
} catch(PDOException $e) { $experiences = []; }

// Get education
try {
    $stmt = $pdo->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $educations = $stmt->fetchAll();
} catch(PDOException $e) { $educations = []; }

// Get media
try {
    $stmt = $pdo->prepare("SELECT * FROM user_media WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $media = $stmt->fetchAll();
} catch(PDOException $e) { $media = []; }

// Parse social links from JSON
$social_links = json_decode($user['social_links'] ?? '{}', true);

// Calculate profile completion percentage
$completion_fields = [
    'first_name' => 5, 'last_name' => 5, 'email' => 5, 'phone' => 5,
    'city' => 3, 'country' => 3, 'job_title' => 5, 'gender' => 2,
    'age' => 2, 'nationality' => 2, 'id_number' => 3, 'bio' => 10,
    'skills' => 10, 'availability' => 5, 'work_hours' => 5,
    'work_days' => 5, 'rate_type' => 5, 'hourly_rate' => 5
];
$completion_score = 0;
foreach ($completion_fields as $field => $weight) {
    if (!empty($user[$field])) {
        $completion_score += $weight;
    }
}
// --- Fetch and Calculate Average Rating ---
$avg_rating = 0;
$total_ratings = 0;
$testimonials = [];

try {
    $rating_stmt = $pdo->prepare("
        SELECT stars, comment 
        FROM gig_ratings 
        WHERE freelancer_id = ?
    ");
    $rating_stmt->execute([$user_id]);
    $ratings = $rating_stmt->fetchAll();

    if ($ratings) {
        $total_ratings = count($ratings);
        $total_stars = 0;
        foreach ($ratings as $rating) {
            $total_stars += $rating['stars'];
            // Only add non-empty comments to the testimonials list
            if (!empty($rating['comment'])) {
                $testimonials[] = $rating['comment'];
            }
        }
        $avg_rating = round($total_stars / $total_ratings, 1);
    }

} catch (PDOException $e) {
    // If there's an error, default to 0
    $avg_rating = 0;
    $total_ratings = 0;
    error_log("Rating fetch error: " . $e->getMessage());
}
// --- END OF NEW BLOCK ---
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الملف الشخصي - <?= SITE_NAME ?></title>
    
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
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            align-items: start;
        }
        
        /* Profile Sidebar (Sticky) */
        .profile-sidebar {
            position: sticky;
            top: 25px;
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            height: fit-content;
        }
        
        .profile-avatar {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 20px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            overflow: hidden;
            margin: 0 auto 20px;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
        }
        
        .edit-avatar {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: var(--primary-color);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.3);
        }
        
        .profile-info h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 700;
            color: #333;
            text-align: center;
        }
        
        .profile-info .job-title {
            color: var(--primary-color);
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .profile-meta {
            margin-bottom: 20px;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .profile-meta-item i {
            color: var(--primary-color);
            width: 16px;
            text-align: center;
        }
        
        .profile-stats {
            background: var(--neumorphic-bg);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            box-shadow: 
                inset 3px 3px 6px var(--neumorphic-dark),
                inset -3px -3px 6px var(--neumorphic-light);
        }
        
        .completion-progress {
            margin-bottom: 15px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .completion-text {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-item span:first-child {
            color: #666;
            font-size: 13px;
        }
        
        .stat-item span:last-child {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Content Area */
        .content-area {
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
        
        .btn-secondary-neumorphic {
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
        
        .btn-secondary-neumorphic:hover {
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
        }
        
        /* Form Cards */
        .form-card {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 
                8px 8px 16px var(--neumorphic-dark),
                -8px -8px 16px var(--neumorphic-light);
            margin-bottom: 25px;
        }
        
        .form-card h4 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .form-section:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: var(--danger-color);
            margin-right: 3px;
        }
        
        .form-control-neumorphic {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 10px;
            background: var(--neumorphic-bg);
            box-shadow: 
                inset 4px 4px 8px var(--neumorphic-dark),
                inset -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .form-control-neumorphic:focus {
            outline: none;
            box-shadow: 
                inset 6px 6px 12px var(--neumorphic-dark),
                inset -6px -6px 12px var(--neumorphic-light);
        }
        
        textarea.form-control-neumorphic {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        
        /* Enhanced Form Elements */
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: var(--neumorphic-bg);
            border-radius: 8px;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
            transition: all 0.3s;
        }
        
        .checkbox-item:hover {
            box-shadow: 
                2px 2px 6px var(--neumorphic-dark),
                -2px -2px 6px var(--neumorphic-light);
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            font-size: 13px;
        }
        
        /* Work Schedule Table */
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 
                inset 2px 2px 4px var(--neumorphic-dark),
                inset -2px -2px 4px var(--neumorphic-light);
        }
        
        .schedule-table th,
        .schedule-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .schedule-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 13px;
        }
        
        .schedule-table td {
            background: var(--neumorphic-bg);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .schedule-table td:hover {
            background: var(--neumorphic-light);
        }
        
        .schedule-table td.selected {
            background: var(--success-color);
            color: white;
        }
        
        /* Rate System */
        .rate-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .rate-type-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: var(--neumorphic-bg);
            color: #666;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            transition: all 0.3s;
            flex: 1;
            min-width: 120px;
            text-align: center;
        }
        
        .rate-type-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .rate-input-group {
            display: none;
        }
        
        .rate-input-group.active {
            display: block;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-start;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(0,0,0,0.05);
        }
        
        .btn-save {
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 
                5px 5px 15px rgba(23, 79, 132, 0.3),
                -2px -2px 8px rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 
                6px 6px 18px rgba(23, 79, 132, 0.4),
                -3px -3px 10px rgba(255, 255, 255, 0.9);
        }
        
        /* Portfolio Grid */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .portfolio-item {
            background: var(--neumorphic-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .portfolio-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .portfolio-item-content {
            padding: 15px;
        }
        
        .portfolio-item-content h5 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .portfolio-item-content p {
            margin: 0;
            font-size: 13px;
            color: #999;
        }
        
        /* Timeline */
        .timeline {
            margin-top: 20px;
        }
        
        .timeline-item {
            background: var(--neumorphic-bg);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
        }
        
        .timeline-content h5 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .timeline-content .company {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .timeline-content .date {
            color: #999;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .timeline-content p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }
        
        /* Media Gallery */
        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .media-item {
            position: relative;
            background: var(--neumorphic-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 
                4px 4px 8px var(--neumorphic-dark),
                -4px -4px 8px var(--neumorphic-light);
            aspect-ratio: 1;
        }
        
        .media-item img,
        .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .media-item:hover .media-item-overlay {
            opacity: 1;
        }
        
        .media-item-overlay i {
            color: white;
            font-size: 24px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
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
            margin: 0 0 20px 0;
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
        
        /* Skills Tags */
        .skills-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .skill-tag {
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
        
        /* Responsive */
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                position: relative;
                top: 0;
            }
            
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .main-content { 
                margin-right: 0; 
                padding: 15px;
                grid-template-columns: 1fr;
            }
            .rate-type-selector { flex-direction: column; }
            .form-actions { flex-direction: column; }
            .checkbox-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include '../includes/sidebar-freelancer.php'; ?>

    <div class="main-content">
        
        <!-- Profile Sidebar (Sticky) -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <img src="<?= !empty($user['profile_photo']) ? '../' . htmlspecialchars($user['profile_photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name']) . '&background=174F84&color=fff&size=200' ?>" 
                     alt="صورة الملف الشخصي">
                <div class="edit-avatar" data-toggle="modal" data-target="#avatarModal">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            
            <div class="profile-info">
                <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                <!-- ADD THIS RATING DISPLAY -->
<div class="text-center mb-3">
    <div style="color: #FFD700; font-size: 1.2rem;">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <?php if ($i <= $avg_rating): ?>
                <i class="fas fa-star"></i>
            <?php elseif ($i - 0.5 <= $avg_rating): ?>
                <i class="fas fa-star-half-alt"></i>
            <?php else: ?>
                <i class="far fa-star"></i>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <span class="font-weight-bold"><?= $avg_rating ?></span>
    <span class="text-muted">(<?= $total_ratings ?> تقييمات)</span>
</div>
<!-- END OF RATING DISPLAY -->
                <?php if (!empty($user['job_title'])): ?>
                    <div class="job-title"><?= htmlspecialchars($user['job_title']) ?></div>
                <?php endif; ?>
                
                <div class="profile-meta">
                    <div class="profile-meta-item">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($user['gender'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-birthday-cake"></i>
                        <span><?= htmlspecialchars($user['age'] ?? 'غير محدد') ?> سنة</span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-flag"></i>
                        <span><?= htmlspecialchars($user['nationality'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($user['city'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($user['phone'] ?? 'غير محدد') ?></span>
                    </div>
                    <?php if (!empty($user['languages'])): ?>
                    <div class="profile-meta-item">
                        <i class="fas fa-language"></i>
                        <span><?= htmlspecialchars($user['languages']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <div class="completion-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $completion_score ?>%"></div>
                        </div>
                        <div class="completion-text">اكتمال الملف الشخصي: <?= $completion_score ?>%</div>
                    </div>
                    
                    <div class="stat-item">
                        <span>إجمالي الأرباح</span>
                        <span><?= formatMoney($user['total_earnings'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span>المشاريع</span>
                        <span><?= count($portfolio) ?></span>
                    </div>
                    <div class="stat-item">
                        <span>الخبرات</span>
                        <span><?= count($experiences) ?></span>
                    </div>
                    <div class="stat-item">
                        <span>التوصيات</span>
                        <span><?= !empty($user['recommendations']) ? 'نعم' : 'لا' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2>الملف الشخصي 👤</h2>
                    <p>إدارة معلوماتك الشخصية ومحفظة أعمالك</p>
                </div>
                <a href="dashboard.php" class="btn-primary-neumorphic">
                    <i class="fas fa-arrow-right"></i>
                    العودة للوحة التحكم
                </a>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Basic Information Form -->
            <div class="form-card">
                <h4>
                    <i class="fas fa-user" style="color: var(--primary-color);"></i>
                    المعلومات الأساسية
                </h4>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_basic_info" value="1">
                    
                    <!-- Personal Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-id-card"></i>
                            المعلومات الشخصية
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>
                                    الاسم الأول <span class="required">*</span>
                                </label>
                                <input type="text" name="first_name" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    اسم العائلة <span class="required">*</span>
                                </label>
                                <input type="text" name="last_name" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>المسمى الوظيفي</label>
                                <input type="text" name="job_title" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['job_title'] ?? '') ?>"
                                       placeholder="مثال: منظم حفلات، سائق، أمن...">
                            </div>
                            
                            <div class="form-group">
                                <label>البريد الإلكتروني <span class="required">*</span></label>
                                <input type="email" name="email" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>رقم الجوال <span class="required">*</span></label>
                                <input type="tel" name="phone" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                       placeholder="+966 5XX XXX XXX" required>
                            </div>
                            
                            <div class="form-group">
                                <label>رقم واتساب</label>
                                <input type="tel" name="whatsapp" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>"
                                       placeholder="+966 5XX XXX XXX">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>المدينة <span class="required">*</span></label>
                                <input type="text" name="city" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['city'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>الدولة <span class="required">*</span></label>
                                <select name="country" class="form-control-neumorphic" required>
                                    <option value="السعودية" <?= ($user['country'] ?? '') == 'السعودية' ? 'selected' : '' ?>>السعودية</option>
                                    <option value="الإمارات" <?= ($user['country'] ?? '') == 'الإمارات' ? 'selected' : '' ?>>الإمارات</option>
                                    <option value="الكويت" <?= ($user['country'] ?? '') == 'الكويت' ? 'selected' : '' ?>>الكويت</option>
                                    <option value="قطر" <?= ($user['country'] ?? '') == 'قطر' ? 'selected' : '' ?>>قطر</option>
                                    <option value="البحرين" <?= ($user['country'] ?? '') == 'البحرين' ? 'selected' : '' ?>>البحرين</option>
                                    <option value="عمان" <?= ($user['country'] ?? '') == 'عمان' ? 'selected' : '' ?>>عمان</option>
                                    <option value="مصر" <?= ($user['country'] ?? '') == 'مصر' ? 'selected' : '' ?>>مصر</option>
                                    <option value="الأردن" <?= ($user['country'] ?? '') == 'الأردن' ? 'selected' : '' ?>>الأردن</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row-3">
                            <div class="form-group">
                                <label>الجنس <span class="required">*</span></label>
                                <select name="gender" class="form-control-neumorphic" required>
                                    <option value="">اختر الجنس</option>
                                    <option value="ذكر" <?= ($user['gender'] ?? '') == 'ذكر' ? 'selected' : '' ?>>ذكر</option>
                                    <option value="أنثى" <?= ($user['gender'] ?? '') == 'أنثى' ? 'selected' : '' ?>>أنثى</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>العمر <span class="required">*</span></label>
                                <input type="number" name="age" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['age'] ?? '') ?>" min="18" max="70"
                                       placeholder="العمر" required>
                            </div>
                            
                            <div class="form-group">
                                <label>الجنسية <span class="required">*</span></label>
                                <input type="text" name="nationality" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['nationality'] ?? '') ?>"
                                       placeholder="الجنسية" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>رقم الهوية/الإقامة <span class="required">*</span></label>
                                <input type="text" name="id_number" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['id_number'] ?? '') ?>"
                                       placeholder="رقم الهوية أو الإقامة" required>
                            </div>
                            
                            <div class="form-group">
                                <label>هل تمتلك رخصة قيادة؟</label>
                                <select name="has_driving_license" class="form-control-neumorphic">
                                    <option value="">اختر</option>
                                    <option value="نعم" <?= ($user['has_driving_license'] ?? '') == 'نعم' ? 'selected' : '' ?>>نعم</option>
                                    <option value="لا" <?= ($user['has_driving_license'] ?? '') == 'لا' ? 'selected' : '' ?>>لا</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Physical Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-ruler-combined"></i>
                            المعلومات الجسدية
                        </div>
                        
                        <div class="form-row-3">
                            <div class="form-group">
                                <label>الطول (سم)</label>
                                <input type="number" name="height" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['height'] ?? '') ?>" 
                                       placeholder="الطول بالسنتيمتر">
                            </div>
                            
                            <div class="form-group">
                                <label>الوزن (كجم)</label>
                                <input type="number" name="weight" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['weight'] ?? '') ?>" 
                                       placeholder="الوزن بالكيلوجرام">
                            </div>
                            
                            <div class="form-group">
                                <label>شكل الجسم</label>
                                <select name="body_shape" class="form-control-neumorphic">
                                    <option value="">اختر شكل الجسم</option>
                                    <option value="نحيف" <?= ($user['body_shape'] ?? '') == 'نحيف' ? 'selected' : '' ?>>نحيف</option>
                                    <option value="رياضي" <?= ($user['body_shape'] ?? '') == 'رياضي' ? 'selected' : '' ?>>رياضي</option>
                                    <option value="متوسط" <?= ($user['body_shape'] ?? '') == 'متوسط' ? 'selected' : '' ?>>متوسط</option>
                                    <option value="ممتلئ" <?= ($user['body_shape'] ?? '') == 'ممتلئ' ? 'selected' : '' ?>>ممتلئ</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>لون الشعر</label>
                                <select name="hair_color" class="form-control-neumorphic">
                                    <option value="">اختر لون الشعر</option>
                                    <option value="أسود" <?= ($user['hair_color'] ?? '') == 'أسود' ? 'selected' : '' ?>>أسود</option>
                                    <option value="بني" <?= ($user['hair_color'] ?? '') == 'بني' ? 'selected' : '' ?>>بني</option>
                                    <option value="أشقر" <?= ($user['hair_color'] ?? '') == 'أشقر' ? 'selected' : '' ?>>أشقر</option>
                                    <option value="أحمر" <?= ($user['hair_color'] ?? '') == 'أحمر' ? 'selected' : '' ?>>أحمر</option>
                                    <option value="رمادي" <?= ($user['hair_color'] ?? '') == 'رمادي' ? 'selected' : '' ?>>رمادي</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>لون العينين</label>
                                <select name="eye_color" class="form-control-neumorphic">
                                    <option value="">اختر لون العينين</option>
                                    <option value="أسود" <?= ($user['eye_color'] ?? '') == 'أسود' ? 'selected' : '' ?>>أسود</option>
                                    <option value="بني" <?= ($user['eye_color'] ?? '') == 'بني' ? 'selected' : '' ?>>بني</option>
                                    <option value="أزرق" <?= ($user['eye_color'] ?? '') == 'أزرق' ? 'selected' : '' ?>>أزرق</option>
                                    <option value="أخضر" <?= ($user['eye_color'] ?? '') == 'أخضر' ? 'selected' : '' ?>>أخضر</option>
                                    <option value="عسلي" <?= ($user['eye_color'] ?? '') == 'عسلي' ? 'selected' : '' ?>>عسلي</option>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($user['gender'] == 'أنثى'): ?>
                        <div class="form-group">
                            <label>نوع اللباس</label>
                            <select name="clothing_type" class="form-control-neumorphic">
                                <option value="">اختر نوع اللباس</option>
                                <option value="حجاب" <?= ($user['clothing_type'] ?? '') == 'حجاب' ? 'selected' : '' ?>>حجاب</option>
                                <option value="نقاب" <?= ($user['clothing_type'] ?? '') == 'نقاب' ? 'selected' : '' ?>>نقاب</option>
                                <option value="بدون" <?= ($user['clothing_type'] ?? '') == 'بدون' ? 'selected' : '' ?>>بدون</option>
                                <option value="تغطية كاملة" <?= ($user['clothing_type'] ?? '') == 'تغطية كاملة' ? 'selected' : '' ?>>تغطية كاملة</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Languages -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-language"></i>
                            اللغات
                        </div>
                        
                        <div class="form-group">
                            <label>اللغات الأخرى التي تتحدثها</label>
                            <div class="checkbox-grid">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="الإنجليزية" <?= strpos($user['languages'] ?? '', 'الإنجليزية') !== false ? 'checked' : '' ?>>
                                    <label>الإنجليزية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="الفرنسية" <?= strpos($user['languages'] ?? '', 'الفرنسية') !== false ? 'checked' : '' ?>>
                                    <label>الفرنسية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="الأوردية" <?= strpos($user['languages'] ?? '', 'الأوردية') !== false ? 'checked' : '' ?>>
                                    <label>الأوردية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="الفلبينية" <?= strpos($user['languages'] ?? '', 'الفلبينية') !== false ? 'checked' : '' ?>>
                                    <label>الفلبينية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="الهندية" <?= strpos($user['languages'] ?? '', 'الهندية') !== false ? 'checked' : '' ?>>
                                    <label>الهندية</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="languages[]" value="أخرى" <?= strpos($user['languages'] ?? '', 'أخرى') !== false ? 'checked' : '' ?>>
                                    <label>أخرى</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            حفظ المعلومات الأساسية
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Skills & Work Preferences Form -->
            <div class="form-card">
                <h4>
                    <i class="fas fa-briefcase" style="color: var(--primary-color);"></i>
                    المعلومات المهنية
                </h4>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_skills_info" value="1">
                    
                    <!-- Profile Headline -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-heading"></i>
                            العنوان الوظيفي
                        </div>
                        <div class="form-group">
                            <input type="text" name="headline" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['headline'] ?? '') ?>" 
                                   placeholder="مثال: مصمم جرافيك محترف">
                        </div>
                    </div>

                    <!-- Skills & Jobs -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-tools"></i>
                            المهارات والوظائف
                        </div>
                        
                        <div class="form-group">
                            <label>المهارات <span class="required">*</span></label>
                            <input type="text" name="skills" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($user['skills'] ?? '') ?>" 
                                   placeholder="مثال: خدمة العملاء, البيع, التسويق, التنظيم, الترجمة..." required>
                            <small style="color: #999; font-size: 12px; margin-top: 5px; display: block;">
                                افصل بين المهارات بفاصلة
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>الوظائف الممكن شغلها</label>
                            <div class="checkbox-grid">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="منظم حفلات" <?= strpos($user['available_jobs'] ?? '', 'منظم حفلات') !== false ? 'checked' : '' ?>>
                                    <label>منظم حفلات</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="سائق" <?= strpos($user['available_jobs'] ?? '', 'سائق') !== false ? 'checked' : '' ?>>
                                    <label>سائق</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="أمن" <?= strpos($user['available_jobs'] ?? '', 'أمن') !== false ? 'checked' : '' ?>>
                                    <label>أمن</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="خدمة عملاء" <?= strpos($user['available_jobs'] ?? '', 'خدمة عملاء') !== false ? 'checked' : '' ?>>
                                    <label>خدمة عملاء</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="مبيعات" <?= strpos($user['available_jobs'] ?? '', 'مبيعات') !== false ? 'checked' : '' ?>>
                                    <label>مبيعات</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="تنظيم" <?= strpos($user['available_jobs'] ?? '', 'تنظيم') !== false ? 'checked' : '' ?>>
                                    <label>تنظيم</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="ترجمة" <?= strpos($user['available_jobs'] ?? '', 'ترجمة') !== false ? 'checked' : '' ?>>
                                    <label>ترجمة</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available_jobs[]" value="تصوير" <?= strpos($user['available_jobs'] ?? '', 'تصوير') !== false ? 'checked' : '' ?>>
                                    <label>تصوير</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Work Preferences -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-clock"></i>
                            تفضيلات العمل
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>الحالة <span class="required">*</span></label>
                                <select name="availability" class="form-control-neumorphic" required>
                                    <option value="">اختر الحالة</option>
                                    <option value="متاح فوراً" <?= ($user['availability'] ?? '') == 'متاح فوراً' ? 'selected' : '' ?>>متاح فوراً</option>
                                    <option value="متاح خلال أسبوع" <?= ($user['availability'] ?? '') == 'متاح خلال أسبوع' ? 'selected' : '' ?>>متاح خلال أسبوع</option>
                                    <option value="متاح خلال شهر" <?= ($user['availability'] ?? '') == 'متاح خلال شهر' ? 'selected' : '' ?>>متاح خلال شهر</option>
                                    <option value="غير متاح حالياً" <?= ($user['availability'] ?? '') == 'غير متاح حالياً' ? 'selected' : '' ?>>غير متاح حالياً</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>ساعات العمل المفضلة <span class="required">*</span></label>
                                <select name="work_hours" class="form-control-neumorphic" required>
                                    <option value="">اختر ساعات العمل</option>
                                    <option value="دوام كامل" <?= ($user['work_hours'] ?? '') == 'دوام كامل' ? 'selected' : '' ?>>دوام كامل</option>
                                    <option value="دوام جزئي" <?= ($user['work_hours'] ?? '') == 'دوام جزئي' ? 'selected' : '' ?>>دوام جزئي</option>
                                    <option value="مرن" <?= ($user['work_hours'] ?? '') == 'مرن' ? 'selected' : '' ?>>مرن</option>
                                    <option value="مشاريع مؤقتة" <?= ($user['work_hours'] ?? '') == 'مشاريع مؤقتة' ? 'selected' : '' ?>>مشاريع مؤقتة</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>تفضيلات العمل الإضافية</label>
                            <div class="checkbox-grid">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="work_preferences[]" value="مؤقت" <?= strpos($user['work_preferences'] ?? '', 'مؤقت') !== false ? 'checked' : '' ?>>
                                    <label>مؤقت</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="work_preferences[]" value="مرن" <?= strpos($user['work_preferences'] ?? '', 'مرن') !== false ? 'checked' : '' ?>>
                                    <label>مرن</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="work_preferences[]" value="جزئي" <?= strpos($user['work_preferences'] ?? '', 'جزئي') !== false ? 'checked' : '' ?>>
                                    <label>جزئي</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="work_preferences[]" value="أسبوعي" <?= strpos($user['work_preferences'] ?? '', 'أسبوعي') !== false ? 'checked' : '' ?>>
                                    <label>أسبوعي</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="work_preferences[]" value="عطلة نهاية الأسبوع" <?= strpos($user['work_preferences'] ?? '', 'عطلة نهاية الأسبوع') !== false ? 'checked' : '' ?>>
                                    <label>عطلة نهاية الأسبوع</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>أيام العمل المفضلة</label>
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>اليوم</th>
                                        <th>صباحي</th>
                                        <th>مسائي</th>
                                        <th>كامل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $days = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
                                    $work_days = explode(',', $user['work_days'] ?? '');
                                    foreach ($days as $day): 
                                    ?>
                                    <tr>
                                        <td><?= $day ?></td>
                                        <td class="<?= in_array($day.'_صباحي', $work_days) ? 'selected' : '' ?>" data-day="<?= $day ?>" data-shift="صباحي">✓</td>
                                        <td class="<?= in_array($day.'_مسائي', $work_days) ? 'selected' : '' ?>" data-day="<?= $day ?>" data-shift="مسائي">✓</td>
                                        <td class="<?= in_array($day.'_كامل', $work_days) ? 'selected' : '' ?>" data-day="<?= $day ?>" data-shift="كامل">✓</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <input type="hidden" name="work_days[]" id="work_days_input">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>تفضيل الدوام</label>
                                <select name="shift_preference" class="form-control-neumorphic">
                                    <option value="">اختر تفضيل الدوام</option>
                                    <option value="صباحي" <?= ($user['shift_preference'] ?? '') == 'صباحي' ? 'selected' : '' ?>>صباحي</option>
                                    <option value="مسائي" <?= ($user['shift_preference'] ?? '') == 'مسائي' ? 'selected' : '' ?>>مسائي</option>
                                    <option value="ليلي" <?= ($user['shift_preference'] ?? '') == 'ليلي' ? 'selected' : '' ?>>ليلي</option>
                                    <option value="مرن" <?= ($user['shift_preference'] ?? '') == 'مرن' ? 'selected' : '' ?>>مرن</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>وسيلة التنقل</label>
                                <select name="transportation" class="form-control-neumorphic">
                                    <option value="">اختر وسيلة التنقل</option>
                                    <option value="سيارة خاصة" <?= ($user['transportation'] ?? '') == 'سيارة خاصة' ? 'selected' : '' ?>>سيارة خاصة</option>
                                    <option value="مواصلات عامة" <?= ($user['transportation'] ?? '') == 'مواصلات عامة' ? 'selected' : '' ?>>مواصلات عامة</option>
                                    <option value="تاكسي" <?= ($user['transportation'] ?? '') == 'تاكسي' ? 'selected' : '' ?>>تاكسي</option>
                                    <option value="يمكن الترتيب" <?= ($user['transportation'] ?? '') == 'يمكن الترتيب' ? 'selected' : '' ?>>يمكن الترتيب</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>إمكانية العمل في الأعياد</label>
                            <select name="holiday_work" class="form-control-neumorphic">
                                <option value="">اختر</option>
                                <option value="نعم" <?= ($user['holiday_work'] ?? '') == 'نعم' ? 'selected' : '' ?>>نعم</option>
                                <option value="لا" <?= ($user['holiday_work'] ?? '') == 'لا' ? 'selected' : '' ?>>لا</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Rates & Pricing -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-money-bill-wave"></i>
                            أسعار الخدمات
                        </div>
                        
                        <div class="form-group">
                            <label>نوع الأجر <span class="required">*</span></label>
                            <div class="rate-type-selector">
                                <button type="button" class="rate-type-btn <?= ($user['rate_type'] ?? 'ساعة') == 'ساعة' ? 'active' : '' ?>" data-type="ساعة">بالساعة</button>
                                <button type="button" class="rate-type-btn <?= ($user['rate_type'] ?? '') == 'يوم' ? 'active' : '' ?>" data-type="يوم">باليوم</button>
                                <button type="button" class="rate-type-btn <?= ($user['rate_type'] ?? '') == 'شهر' ? 'active' : '' ?>" data-type="شهر">بالشهر</button>
                                <button type="button" class="rate-type-btn <?= ($user['rate_type'] ?? '') == 'مشروع' ? 'active' : '' ?>" data-type="مشروع">بالمشروع</button>
                            </div>
                            <input type="hidden" name="rate_type" id="rate_type" value="<?= htmlspecialchars($user['rate_type'] ?? 'ساعة') ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group rate-input-group <?= ($user['rate_type'] ?? 'ساعة') == 'ساعة' ? 'active' : '' ?>" id="hourly_rate_group">
                                <label>الأجر بالساعة (<?= CURRENCY ?>) <span class="required">*</span></label>
                                <input type="number" name="hourly_rate" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['hourly_rate'] ?? '') ?>" 
                                       placeholder="150" min="0">
                            </div>
                            
                            <div class="form-group rate-input-group <?= ($user['rate_type'] ?? '') == 'يوم' ? 'active' : '' ?>" id="daily_rate_group">
                                <label>الأجر اليومي (<?= CURRENCY ?>)</label>
                                <input type="number" name="daily_rate" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['daily_rate'] ?? '') ?>" 
                                       placeholder="1200" min="0">
                            </div>
                            
                            <div class="form-group rate-input-group <?= ($user['rate_type'] ?? '') == 'شهر' ? 'active' : '' ?>" id="monthly_rate_group">
                                <label>الأجر الشهري (<?= CURRENCY ?>)</label>
                                <input type="number" name="monthly_rate" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['monthly_rate'] ?? '') ?>" 
                                       placeholder="24000" min="0">
                            </div>
                            
                            <div class="form-group rate-input-group <?= ($user['rate_type'] ?? '') == 'مشروع' ? 'active' : '' ?>" id="project_rate_group">
                                <label>أجر المشروع (<?= CURRENCY ?>)</label>
                                <input type="number" name="project_rate" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($user['project_rate'] ?? '') ?>" 
                                       placeholder="5000" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            حفظ المعلومات المهنية
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Bio & Media Form -->
            <div class="form-card">
                <h4>
                    <i class="fas fa-file-alt" style="color: var(--primary-color);"></i>
                    المعلومات الإضافية
                </h4>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_bio_info" value="1">
                    
                    <!-- Bio & Recommendations -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-user-circle"></i>
                            نبذة تعريفية
                        </div>
                        
                        <div class="form-group">
                            <label>نبذة عني <span class="required">*</span></label>
                            <textarea name="bio" class="form-control-neumorphic" rows="6" 
                                      placeholder="اكتب نبذة مختصرة عن خبراتك ومهاراتك وأهدافك المهنية..." required><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>التوصيات أو الشهادات</label>
                            <textarea name="recommendations" class="form-control-neumorphic" rows="4" 
                                      placeholder="أضف أي توصيات أو شهادات من عملاء سابقين..."><?= htmlspecialchars($user['recommendations'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-share-alt"></i>
                            وسائل التواصل الاجتماعي
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>رابط الملف الشخصي (LinkedIn)</label>
                                <input type="url" name="linkedin" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($social_links['linkedin'] ?? '') ?>"
                                       placeholder="https://linkedin.com/in/username">
                            </div>
                            
                            <div class="form-group">
                                <label>رابط الإنستغرام</label>
                                <input type="url" name="instagram" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($social_links['instagram'] ?? '') ?>"
                                       placeholder="https://instagram.com/username">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>رابط تويتر</label>
                                <input type="url" name="twitter" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($social_links['twitter'] ?? '') ?>"
                                       placeholder="https://twitter.com/username">
                            </div>
                            
                            <div class="form-group">
                                <label>رابط Behance (للمصممين)</label>
                                <input type="url" name="behance" class="form-control-neumorphic" 
                                       value="<?= htmlspecialchars($social_links['behance'] ?? '') ?>"
                                       placeholder="https://behance.net/username">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>رابط الموقع الشخصي/المحفظة</label>
                            <input type="url" name="portfolio" class="form-control-neumorphic" 
                                   value="<?= htmlspecialchars($social_links['portfolio'] ?? '') ?>"
                                   placeholder="https://yourportfolio.com">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            حفظ المعلومات الإضافية
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Media Section -->
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4>
                        <i class="fas fa-images" style="color: var(--primary-color);"></i>
                        الوسائط المتعددة
                    </h4>
                    <button class="btn-secondary-neumorphic" data-toggle="modal" data-target="#mediaModal">
                        <i class="fas fa-plus"></i>
                        إضافة وسائط
                    </button>
                </div>
                
                <?php if (empty($media)): ?>
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <h5>لا توجد وسائط مضافة بعد</h5>
                        <p>أضف صور وفيديوهات من أعمالك السابقة</p>
                    </div>
                <?php else: ?>
                    <div class="media-gallery">
                        <?php foreach ($media as $item): ?>
                            <div class="media-item">
                                <?php if ($item['media_type'] == 'image'): ?>
                                    <img src="<?= '../' . htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                <?php else: ?>
                                    <video controls>
                                        <source src="<?= '../' . htmlspecialchars($item['file_path']) ?>" type="video/mp4">
                                        متصفحك لا يدعم تشغيل الفيديو
                                    </video>
                                <?php endif; ?>
                                <div class="media-item-overlay">
                                    <div>
                                        <i class="fas fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Portfolio Section -->
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4>
                        <i class="fas fa-briefcase" style="color: var(--primary-color);"></i>
                        محفظة الأعمال
                    </h4>
                    <button class="btn-secondary-neumorphic" data-toggle="modal" data-target="#portfolioModal">
                        <i class="fas fa-plus"></i>
                        إضافة مشروع
                    </button>
                </div>
                
                <?php if (empty($portfolio)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h5>لا توجد مشاريع في محفظتك بعد</h5>
                        <p>أضف مشاريعك السابقة لعرض خبراتك للعملاء</p>
                    </div>
                <?php else: ?>
                    <div class="portfolio-grid">
                        <?php foreach ($portfolio as $item): ?>
                            <div class="portfolio-item">
                                <img src="<?= '../' . htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                <div class="portfolio-item-content">
                                    <h5><?= htmlspecialchars($item['title']) ?></h5>
                                    <p><?= htmlspecialchars($item['category']) ?></p>
                                    <?php if (!empty($item['description'])): ?>
                                        <p style="margin-top: 8px; font-size: 12px; color: #888;">
                                            <?= htmlspecialchars($item['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['url'])): ?>
                                        <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" style="font-size: 12px; color: var(--primary-color); text-decoration: none;">
                                            <i class="fas fa-external-link-alt"></i> عرض المشروع
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Experience Section -->
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4>
                        <i class="fas fa-briefcase" style="color: var(--primary-color);"></i>
                        الخبرات العملية
                    </h4>
                    <button class="btn-secondary-neumorphic" data-toggle="modal" data-target="#experienceModal">
                        <i class="fas fa-plus"></i>
                        إضافة خبرة
                    </button>
                </div>
                
                <?php if (empty($experiences)): ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <h5>لم تضف خبراتك العملية بعد</h5>
                        <p>أضف خبراتك لتعزيز فرصك في الحصول على المشاريع</p>
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
                                        <p><?= nl2br(htmlspecialchars($exp['description'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Education Section -->
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4>
                        <i class="fas fa-graduation-cap" style="color: var(--primary-color);"></i>
                        التعليم والشهادات
                    </h4>
                    <button class="btn-secondary-neumorphic" data-toggle="modal" data-target="#educationModal">
                        <i class="fas fa-plus"></i>
                        إضافة شهادة
                    </button>
                </div>
                
                <?php if (empty($educations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-university"></i>
                        <h5>لم تضف مؤهلاتك التعليمية بعد</h5>
                        <p>أضف شهاداتك الدراسية والدورات التدريبية</p>
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
                                        <p><?= nl2br(htmlspecialchars($edu['description'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ADD THIS NEW TESTIMONIALS CARD -->
<!-- Testimonials Section -->
<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4>
            <i class="fas fa-comment-dots" style="color: var(--primary-color);"></i>
            التوصيات والشهادات
        </h4>
    </div>
    
    <?php if (empty($testimonials)): ?>
        <div class="empty-state">
            <i class="fas fa-comment-slash"></i>
            <h5>لا توجد توصيات لعرضها بعد</h5>
            <p>التوصيات التي يكتبها المشرفون عنك ستظهر هنا.</p>
        </div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <p style="font-style: italic;">"<?= nl2br(htmlspecialchars($testimonial)) ?>"</p>
                        <div class="text-muted text-left small">- مشرف مهمة مكتملة</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<!-- END OF NEW TESTIMONIALS CARD -->
            
        </div>
        
    </div>

    <!-- Modal لتحديث صورة البروفايل -->
    <div class="modal fade" id="avatarModal" tabindex="-1" role="dialog" aria-labelledby="avatarModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="avatarModalLabel">تحديث صورة الملف الشخصي</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>اختر صورة جديدة</label>
                            <div class="upload-btn btn-primary-neumorphic" style="width: 100%; text-align: center; padding: 15px; position: relative; cursor: pointer;">
                                <i class="fas fa-cloud-upload-alt ml-2"></i>رفع صورة
                                <input type="file" name="profile_image" accept="image/*" required style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                            </div>
                            <small class="form-text text-muted">الحجم الأقصى: 2MB, الصيغ المسموحة: JPG, PNG, GIF</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-neumorphic" data-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_avatar" class="btn-primary-neumorphic">حفظ الصورة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal لإضافة مشروع جديد -->
    <div class="modal fade" id="portfolioModal" tabindex="-1" role="dialog" aria-labelledby="portfolioModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="portfolioModalLabel">إضافة مشروع جديد</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>عنوان المشروع</label>
                            <input type="text" name="portfolio_title" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>التصنيف</label>
                            <input type="text" name="portfolio_category" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>وصف المشروع</label>
                            <textarea name="portfolio_description" class="form-control-neumorphic" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>رابط المشروع (اختياري)</label>
                            <input type="url" name="portfolio_url" class="form-control-neumorphic">
                        </div>
                        <div class="form-group">
                            <label>صورة المشروع</label>
                            <div class="upload-btn btn-secondary-neumorphic" style="width: 100%; text-align: center; padding: 10px; position: relative; cursor: pointer;">
                                <i class="fas fa-cloud-upload-alt ml-2"></i>رفع صورة المشروع
                                <input type="file" name="portfolio_image" accept="image/*" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-neumorphic" data-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_portfolio" class="btn-primary-neumorphic">إضافة المشروع</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal لإضافة خبرة عملية -->
    <div class="modal fade" id="experienceModal" tabindex="-1" role="dialog" aria-labelledby="experienceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="experienceModalLabel">إضافة خبرة عملية</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>المسمى الوظيفي</label>
                            <input type="text" name="exp_role" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>اسم الشركة</label>
                            <input type="text" name="exp_company" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>الفترة (مثال: 2020 - 2023)</label>
                            <input type="text" name="exp_period" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>وصف المهام والإنجازات</label>
                            <textarea name="exp_description" class="form-control-neumorphic" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-neumorphic" data-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_experience" class="btn-primary-neumorphic">إضافة الخبرة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal لإضافة مؤهل تعليمي -->
    <div class="modal fade" id="educationModal" tabindex="-1" role="dialog" aria-labelledby="educationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="educationModalLabel">إضافة مؤهل تعليمي</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>المؤهل العلمي</label>
                            <input type="text" name="edu_degree" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>اسم المؤسسة التعليمية</label>
                            <input type="text" name="edu_institution" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>الفترة (مثال: 2016 - 2020)</label>
                            <input type="text" name="edu_period" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>وصف إضافي (اختياري)</label>
                            <textarea name="edu_description" class="form-control-neumorphic" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-neumorphic" data-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_education" class="btn-primary-neumorphic">إضافة المؤهل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal لإضافة وسائط -->
    <div class="modal fade" id="mediaModal" tabindex="-1" role="dialog" aria-labelledby="mediaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaModalLabel">إضافة وسائط جديدة</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>نوع الوسائط</label>
                            <select name="media_type" class="form-control-neumorphic" required>
                                <option value="">اختر النوع</option>
                                <option value="image">صورة</option>
                                <option value="video">فيديو</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>عنوان الوسائط</label>
                            <input type="text" name="media_title" class="form-control-neumorphic" required>
                        </div>
                        <div class="form-group">
                            <label>وصف الوسائط</label>
                            <textarea name="media_description" class="form-control-neumorphic" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>رفع الملف</label>
                            <div class="upload-btn btn-secondary-neumorphic" style="width: 100%; text-align: center; padding: 10px; position: relative; cursor: pointer;">
                                <i class="fas fa-cloud-upload-alt ml-2"></i>رفع ملف
                                <input type="file" name="media_file" accept="image/*,video/*" required style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-neumorphic" data-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_media" class="btn-primary-neumorphic">إضافة الوسائط</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // نظام اختيار نوع الأجر
        $(document).ready(function() {
            $('.rate-type-btn').click(function() {
                $('.rate-type-btn').removeClass('active');
                $(this).addClass('active');
                
                var rateType = $(this).data('type');
                $('#rate_type').val(rateType);
                
                $('.rate-input-group').removeClass('active');
                $('#' + rateType + '_rate_group').addClass('active');
            });
            
            // جدول أيام العمل
            $('.schedule-table td').click(function() {
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                } else {
                    $(this).addClass('selected');
                }
                updateWorkDaysInput();
            });
            
            function updateWorkDaysInput() {
                var selectedDays = [];
                $('.schedule-table td.selected').each(function() {
                    var day = $(this).data('day');
                    var shift = $(this).data('shift');
                    selectedDays.push(day + '_' + shift);
                });
                $('#work_days_input').val(selectedDays.join(','));
            }
            
            // Auto-calculate rates
            $('input[name="hourly_rate"]').on('input', function() {
                var hourly = $(this).val();
                if (hourly) {
                    $('input[name="daily_rate"]').val(hourly * 8);
                    $('input[name="monthly_rate"]').val(hourly * 160);
                }
            });
            
            $('input[name="daily_rate"]').on('input', function() {
                var daily = $(this).val();
                if (daily) {
                    $('input[name="hourly_rate"]').val(daily / 8);
                    $('input[name="monthly_rate"]').val(daily * 20);
                }
            });
            
            // File upload styling
            $('input[type="file"]').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    $(this).parent().find('i').text(' الملف: ' + fileName);
                }
            });
            
            // Initialize work days input
            updateWorkDaysInput();
        });
    </script>
</body>
</html>