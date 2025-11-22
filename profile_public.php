<?php
require_once '../config.php';

// ?u=freelancer_id
$uid = (int)($_GET['u'] ?? 0);
$u = $pdo->prepare("SELECT id, first_name, last_name, city, avatar FROM users WHERE id=?");
$u->execute([$uid]);
$user = $u->fetch(); if(!$user) die('المستخدم غير موجود');

$stats = $pdo->prepare("
  SELECT 
    SUM(a.total_minutes>0) as gigs_done,
    AVG(r.stars) as avg_star,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, g.start_time, a.checkin_time) <= 5 THEN 1 ELSE 0 END) / NULLIF(SUM(a.checkin_time IS NOT NULL),0) * 100 as ontime
  FROM gigs g
  LEFT JOIN gig_attendance a ON a.gig_id=g.id AND a.freelancer_id=g.freelancer_id
  LEFT JOIN gig_ratings r ON r.gig_id=g.id
  WHERE g.freelancer_id=?");
$stats->execute([$uid]);
$s = $stats->fetch();

$recent = $pdo->prepare("SELECT g.title, g.venue, g.start_time FROM gigs g 
                         JOIN gig_attendance a ON a.gig_id=g.id 
                         WHERE g.freelancer_id=? AND a.total_minutes>0
                         ORDER BY g.start_time DESC LIMIT 6");
$recent->execute([$uid]);
$items = $recent->fetchAll();
?>
<!doctype html><html lang="ar" dir="rtl"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>الملف الموثوق</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-white">
<div class="container py-4">
  <div class="text-center mb-3">
    <img src="<?= htmlspecialchars($user['avatar'] ?: '/img/default.png') ?>" style="width:90px;height:90px;border-radius:50%">
    <h3 class="mt-2"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></h3>
    <div class="text-muted"><?= htmlspecialchars($user['city']) ?></div>
    <div class="mt-2">
      ⭐ <?= number_format((float)$s['avg_star'],1) ?: 'خمسة' ?>/5 •
      ✅ <?= (int)$s['gigs_done'] ?> مهام موثقة •
      🕒 الانضباط: <?= (int)$s['ontime'] ?>%
    </div>
  </div>
  <hr>
  <h5>أحدث المهمات:</h5>
  <ul>
    <?php foreach($items as $it): ?>
      <li><?= htmlspecialchars($it['title']) ?> — <?= date('Y-m-d', strtotime($it['start_time'])) ?> @ <?= htmlspecialchars($it['venue']) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
</body></html>