<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

$token = $_GET['token'] ?? '';
if (empty($token)) { die('الرابط غير صالح.'); }

// Find the token and get ALL the required data in one query
$stmt = $pdo->prepare("
    SELECT 
        t.*, 
        g.title as gig_title, 
        g.supervisor_name,
        g.freelancer_id,
        u.first_name as freelancer_first_name,
        u.last_name as freelancer_last_name,
        a.total_minutes,
        a.late_minutes
    FROM gig_confirm_tokens t
    JOIN gigs g ON g.id = t.gig_id
    JOIN users u ON u.id = g.freelancer_id
    LEFT JOIN gig_attendance a ON a.gig_id = g.id
    WHERE t.token = ? AND t.used = 0 AND t.expires_at > NOW()
");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) { die('الرابط غير صالح أو منتهي الصلاحية.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
      // Get the ratings from the form
      $punctuality = (int)($_POST['punctuality'] ?? 5);
      $instructions = (int)($_POST['instructions'] ?? 5);
      $performance = (int)($_POST['performance'] ?? 5);
      $testimonial = clean($_POST['testimonial'] ?? '');

      // Calculate the final star rating (average of the three questions)
      $final_stars = round(($punctuality + $instructions + $performance) / 3);

      // Insert the rating
      $pdo->prepare("INSERT INTO gig_ratings (gig_id, freelancer_id, stars, comment, rater_name) VALUES (?, ?, ?, ?, ?)")
          ->execute([$row['gig_id'], $row['freelancer_id'], $final_stars, $testimonial, $row['supervisor_name']]);
      
      // Mark the token as used
      $pdo->prepare("UPDATE gig_confirm_tokens SET used = 1 WHERE id = ?")->execute([$row['id']]);

      // Display a professional success message
      echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><title>شكراً لك</title><link href='https://fonts.googleapis.com/css2?family=Cairo:wght@700&display=swap' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css' rel='stylesheet'><style>body{background-color:#e9ecef; font-family: Cairo, sans-serif; display:flex; align-items:center; justify-content:center; height:100vh;}</style></head><body><div style='text-align:center; padding: 50px; font-size: 1.2rem; color: #00BF9A;'><i class='fas fa-check-circle' style='font-size: 4rem; margin-bottom: 20px;'></i><h3 style='font-weight:700;'>تم إرسال تقييمك بنجاح.</h3><p>شكراً لك على وقتك.</p></div></body></html>";
      exit;

  } catch(PDOException $e) {
      error_log("Confirmation Error: " . $e->getMessage());
      die('حدث خطأ في قاعدة البيانات أثناء حفظ التقييم.');
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>تقييم أداء المستقل</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --neumorphic-bg: #e9ecef; --primary-color: #174F84; --light-shadow: #ffffff; --dark-shadow: rgba(174, 174, 192, 0.4); }
        body { background-color: var(--neumorphic-bg); font-family: 'Cairo', sans-serif; }
        .main-card { border-radius: 20px; box-shadow: 10px 10px 20px var(--dark-shadow), -10px -10px 20px var(--light-shadow); border: none; }
        .form-control-neumorphic { width: 100%; border: none; border-radius: 10px; background: var(--neumorphic-bg); box-shadow: inset 4px 4px 8px var(--dark-shadow), inset -4px -4px 8px var(--light-shadow); }
        .rating-scale { display: flex; justify-content: space-between; border-radius: 10px; box-shadow: inset 4px 4px 8px var(--dark-shadow), inset -4px -4px 8px var(--light-shadow); padding: 5px; }
        .rating-scale label { flex: 1; text-align: center; padding: 10px; border-radius: 8px; cursor: pointer; transition: all 0.2s; font-weight: 600; }
        .rating-scale input[type="radio"] { display: none; }
        .rating-scale input[type="radio"]:checked + label { background-color: var(--primary-color); color: white; box-shadow: 4px 4px 8px var(--dark-shadow), -4px -4px 8px var(--light-shadow); }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="main-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <img src="/images/logo.png" alt="Logo" style="max-width: 150px;">
                            <h4 class="mt-3 font-weight-bold">تقييم أداء المستقل</h4>
                        </div>

                        <!-- Auto-filled Information -->
                        <div class="mb-4 p-3" style="background: var(--neumorphic-bg); border-radius: 10px; box-shadow: inset 3px 3px 6px var(--dark-shadow), inset -3px -3px 6px var(--light-shadow);">
                            <p class="mb-1"><strong>المهمة:</strong> <?= htmlspecialchars($row['gig_title']) ?></p>
                            <p class="mb-1"><strong>اسم المستقل:</strong> <?= htmlspecialchars($row['freelancer_first_name'] . ' ' . $row['freelancer_last_name']) ?></p>
                            <p class="mb-0"><strong>اسم المشرف:</strong> <?= htmlspecialchars($row['supervisor_name']) ?></p>
                        </div>

                        <form method="post">
                            <!-- Question 1: Punctuality -->
                            <div class="form-group">
                                <label class="font-weight-bold">1. الالتزام بالوقت</label>
                                <?php if($row['late_minutes'] > 5): ?>
                                    <div class="alert alert-warning small p-2">ملاحظة: سجل النظام أن المستقل تأخر <?= (int)$row['late_minutes'] ?> دقيقة.</div>
                                <?php else: ?>
                                     <div class="alert alert-success small p-2">ملاحظة: سجل النظام أن المستقل وصل في الوقت المحدد.</div>
                                <?php endif; ?>
                                <div class="rating-scale">
                                    <input type="radio" name="punctuality" id="punc1" value="1"><label for="punc1">1</label>
                                    <input type="radio" name="punctuality" id="punc2" value="2"><label for="punc2">2</label>
                                    <input type="radio" name="punctuality" id="punc3" value="3"><label for="punc3">3</label>
                                    <input type="radio" name="punctuality" id="punc4" value="4"><label for="punc4">4</label>
                                    <input type="radio" name="punctuality" id="punc5" value="5" checked><label for="punc5">5</label>
                                </div>
                            </div>
                            
                            <!-- Question 2: Instructions -->
                            <div class="form-group">
                                <label class="font-weight-bold">2. اتباع التعليمات</label>
                                <div class="rating-scale">
                                    <input type="radio" name="instructions" id="inst1" value="1"><label for="inst1">1</label>
                                    <input type="radio" name="instructions" id="inst2" value="2"><label for="inst2">2</label>
                                    <input type="radio" name="instructions" id="inst3" value="3"><label for="inst3">3</label>
                                    <input type="radio" name="instructions" id="inst4" value="4"><label for="inst4">4</label>
                                    <input type="radio" name="instructions" id="inst5" value="5" checked><label for="inst5">5</label>
                                </div>
                            </div>

                            <!-- Question 3: Performance -->
                            <div class="form-group">
                                <label class="font-weight-bold">3. الأداء العام والمظهر</label>
                                <div class="rating-scale">
                                    <input type="radio" name="performance" id="perf1" value="1"><label for="perf1">1</label>
                                    <input type="radio" name="performance" id="perf2" value="2"><label for="perf2">2</label>
                                    <input type="radio" name="performance" id="perf3" value="3"><label for="perf3">3</label>
                                    <input type="radio" name="performance" id="perf4" value="4"><label for="perf4">4</label>
                                    <input type="radio" name="performance" id="perf5" value="5" checked><label for="perf5">5</label>
                                </div>
                            </div>
                            
                            <!-- Testimonial (Optional) -->
                            <div class="form-group">
                                <label class="font-weight-bold">شهادة / توصية (اختياري)</label>
                                <textarea name="testimonial" class="form-control form-control-neumorphic" rows="4" placeholder="إذا كنت ترغب في كتابة توصية للمستقل، يمكنك كتابتها هنا..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg mt-4 font-weight-bold">إرسال التقييم النهائي</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>