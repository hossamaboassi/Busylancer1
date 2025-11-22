<?php
// Set PHP to display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use a reliable path to include essential files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

// Set the header AFTER including files to prevent errors
header('Content-Type: application/json');

// Ensure the user is a freelancer
requireRole('freelancer');
```

$input = json_decode(file_get_contents('php://input'), true);
$gig_id = (int)($input['gig_id'] ?? 0);
$lat = (float)($input['lat'] ?? 0);
$lng = (float)($input['lng'] ?? 0);
$user_id = $_SESSION['user_id'];

function distanceMeters($lat1,$lon1,$lat2,$lon2){
  $R=6371000;
  $phi1=deg2rad($lat1); $phi2=deg2rad($lat2);
  $dphi=deg2rad($lat2-$lat1); $dl=deg2rad($lon2-$lon1);
  $a=sin($dphi/2)**2 + cos($phi1)*cos($phi2)*sin($dl/2)**2;
  return 2*$R*asin(min(1,sqrt($a)));
}

$stmt=$pdo->prepare("SELECT * FROM gigs WHERE id=? AND freelancer_id=?");
$stmt->execute([$gig_id,$user_id]);
$g=$stmt->fetch();
if(!$g){ echo json_encode(['ok'=>false,'message'=>'Gig not found']); exit; }

$dist = distanceMeters($lat,$lng,(float)$g['lat'],(float)$g['lng']);
if($dist > (int)$g['geofence_m']){ echo json_encode(['ok'=>false,'message'=>'يجب تسجيل الخروج داخل الموقع.']); exit; }

$att=$pdo->prepare("SELECT * FROM gig_attendance WHERE gig_id=? AND freelancer_id=?");
$att->execute([$gig_id,$user_id]);
$a=$att->fetch();
if(!$a || !$a['checkin_time']){ echo json_encode(['ok'=>false,'message'=>'لم يتم تسجيل الدخول.']); exit; }

$checkin=new DateTime($a['checkin_time']);
$checkout=new DateTime('now');
$total = max(1, (int)round(($checkout->getTimestamp() - $checkin->getTimestamp())/60));

$start=new DateTime($g['start_time']);
$late=max(0,(int)round(($checkin->getTimestamp() - $start->getTimestamp())/60));

$pdo->prepare("UPDATE gig_attendance SET checkout_time=?, checkout_lat=?, checkout_lng=?, total_minutes=?, late_minutes=? 
               WHERE id=?")
    ->execute([$checkout->format('Y-m-d H:i:s'),$lat,$lng,$total,$late,$a['id']]);

$pdo->prepare("UPDATE gigs SET status='awaiting_payment' WHERE id=?")->execute([$gig_id]);

echo json_encode(['ok'=>true,'message'=>'تم تسجيل الخروج. المدة: '.$total.' دقيقة']);