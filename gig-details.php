<?php
require_once '../config.php';
require_once '../functions.php';
requireRole('freelancer');

// --- Data Fetching ---
$gig_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT g.*, a.checkin_time, a.checkout_time FROM gigs g LEFT JOIN gig_attendance a ON g.id = a.gig_id WHERE g.id = ? AND g.freelancer_id = ?");
$stmt->execute([$gig_id, $user_id]);
$gig = $stmt->fetch();

if (!$gig) { die("المهمة غير موجودة."); }

// --- Time & State Logic ---
$now = new DateTime();
$startTime = new DateTime($gig['start_time']);
$endTime = new DateTime($gig['end_time']);
$checkinAllowedTime = (clone $startTime)->modify('-30 minutes');

$gigState = 'upcoming';
$timer_target_timestamp = $startTime->getTimestamp();
$timer_label = 'الوقت المتبقي للبدء';

if ($gig['checkin_time'] && !$gig['checkout_time']) {
    $gigState = 'active';
    $timer_target_timestamp = $endTime->getTimestamp();
    $timer_label = 'الوقت المتبقي للانتهاء';
} elseif ($gig['checkin_time'] && $gig['checkout_time']) {
    $gigState = 'completed';
    $timer_label = 'المهمة مكتملة';
} elseif ($now > $endTime) {
    $gigState = 'expired';
    $timer_label = 'المهمة منتهية';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">
    <title>إدارة المهمة | <?= htmlspecialchars($gig['title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root { --bg: #e9ecef; --light: #ffffff; --dark: rgba(174, 174, 192, 0.4); --primary: #174F84; --success: #00BF9A; --danger: #FA5252; --text: #343a40; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body { background: var(--bg); font-family: 'Cairo', sans-serif; font-weight: 700; display: flex; align-items: center; justify-content: center; padding: 15px; }
        .mobile-frame { width: 100%; max-width: 400px; background: var(--bg); border-radius: 30px; padding: 20px; box-shadow: 12px 12px 24px var(--dark), -12px -12px 24px var(--light); display: flex; flex-direction: column; height: 98vh; max-height: 850px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: var(--text); margin: 0; }
        .icon-btn { width: 45px; height: 45px; border-radius: 50%; border: none; background: var(--bg); box-shadow: 6px 6px 12px var(--dark), -6px -6px 12px var(--light); color: var(--text); font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .icon-btn:active { box-shadow: inset 4px 4px 8px var(--dark), inset -4px -4px 8px var(--light); }
        .timer-display { text-align: center; margin: 2rem 0; }
        .timer-time { font-size: 4rem; font-weight: 700; color: var(--text); text-shadow: 3px 3px 6px var(--dark), -3px -3px 6px var(--light); }
        .timer-label { font-size: 1rem; color: #6c757d; }
        .actions-container { flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 20px; }
        .action-circle { width: 160px; height: 160px; border-radius: 50%; border: none; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
        .action-circle i { font-size: 3rem; }
        .action-circle span { font-size: 1.2rem; font-weight: 700; margin-top: 10px; }
        .btn-checkin { background-color: var(--success); box-shadow: 8px 8px 16px rgba(0,191,154, 0.4); }
        .btn-checkout { background-color: var(--danger); box-shadow: 8px 8px 16px rgba(250,82,82, 0.4); }
        .btn-checked-in { background: var(--bg); color: var(--success); box-shadow: inset 6px 6px 12px var(--dark), inset -6px -6px 12px var(--light); cursor: default; }
        .btn-faded { opacity: 0.4; cursor: not-allowed; box-shadow: none; }
        .swipe-container { width: 100%; background: var(--bg); border-radius: 20px; box-shadow: inset 5px 5px 10px var(--dark), inset -5px -5px 10px var(--light); padding: 10px; text-align: center; position: relative; overflow: hidden; }
        .swipe-label { color: #6c757d; z-index: 1; position: relative; }
        .swipe-button { position: absolute; top: 0; right: 0; height: 100%; width: 70px; background: var(--primary); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; cursor: grab; z-index: 2; }
        .supervisor-actions { display: none; flex-direction: column; gap: 10px; margin-top: 1rem; }
        .supervisor-actions a { text-decoration: none; color: white; padding: 12px; border-radius: 10px; font-size: 1rem; }
    </style>
</head>
<body>

    <div class="mobile-frame">
        <header class="header">
            <a href="my-jobs.php" class="icon-btn"><i class="fas fa-arrow-right"></i></a>
            <h1><?= htmlspecialchars($gig['title']) ?></h1>
            <a href="edit-field-gig.php?id=<?= $gig_id ?>" class="icon-btn"><i class="fas fa-cog"></i></a>
        </header>

        <div class="timer-display">
            <div id="timerDisplay" class="timer-time">--:--:--</div>
            <div class="timer-label" id="timerLabel"><?= $timer_label ?></div>
        </div>

        <div class="actions-container">
            <button id="btnCheckin" class="action-circle <?= $gigState === 'upcoming' ? '' : 'btn-faded' ?> <?= $gig['checkin_time'] ? 'btn-checked-in' : 'btn-checkin' ?>" <?= $gig['checkin_time'] ? 'disabled' : '' ?>>
                <i class="fas <?= $gig['checkin_time'] ? 'fa-check' : 'fa-sign-in-alt' ?>"></i>
                <span><?= $gig['checkin_time'] ? 'تم الدخول' : 'تسجيل دخول' ?></span>
            </button>
            <button id="btnCheckout" class="action-circle <?= $gigState === 'active' ? 'btn-checkout' : 'btn-faded' ?>" <?= $gigState !== 'active' ? 'disabled' : '' ?>>
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل خروج</span>
            </button>
        </div>

        <footer class="swipe-container" id="swipeContainer">
            <div class="swipe-label" id="swipeLabel">اسحب للتواصل مع المشرف</div>
            <div class="swipe-button" id="swipeButton"><i class="fas fa-chevron-left"></i></div>
            <div class="supervisor-actions" id="supervisorActions">
                <a href="#" id="ratingLink" class="btn" style="background-color: var(--primary);"><i class="fas fa-star"></i> إرسال رابط التقييم</a>
                <a href="#" id="paymentLink" class="btn" style="background-color: var(--success);"><i class="fas fa-dollar-sign"></i> إرسال تذكير بالدفع</a>
            </div>
        </footer>
        <p id="statusMsg" class="text-center mt-2" style="height: 20px; font-size: 0.9rem; color: var(--primary);"></p>
    </div>

<script>
    const gigId = <?= (int)$gig_id ?>;
    const gigState = '<?= $gigState ?>';
    const targetTimestamp = <?= $timer_target_timestamp ?>;
    
    // --- Countdown Timer Logic (Correct) ---
    const timerDisplay = document.getElementById('timerDisplay');
    const timerLabel = document.getElementById('timerLabel');
    function updateCountdown() {
        if (gigState === 'completed' || gigState === 'expired') {
            timerDisplay.textContent = '00:00:00';
            if(window.timerInterval) clearInterval(window.timerInterval); return;
        }
        const secondsLeft = targetTimestamp - Math.floor(Date.now() / 1000);
        if (secondsLeft < 0) {
            timerDisplay.textContent = '00:00:00';
            if (gigState === 'upcoming') timerLabel.textContent = 'حان وقت البدء!';
            if (gigState === 'active') timerLabel.textContent = 'انتهى الوقت الرسمي';
            if(window.timerInterval) clearInterval(window.timerInterval); return;
        }
        const h = String(Math.floor(secondsLeft / 3600)).padStart(2, '0');
        const m = String(Math.floor((secondsLeft % 3600) / 60)).padStart(2, '0');
        const s = String(secondsLeft % 60).padStart(2, '0');
        timerDisplay.textContent = `${h}:${m}:${s}`;
    }
    window.timerInterval = setInterval(updateCountdown, 1000);
    updateCountdown();

    // --- Swipe Action Logic (Correct) ---
    const swipeButton = document.getElementById('swipeButton');
    const swipeContainer = document.getElementById('swipeContainer');
    const supervisorActions = document.getElementById('supervisorActions');
    const swipeLabel = document.getElementById('swipeLabel');
    let isSwiping = false;
    swipeButton.addEventListener('mousedown', () => { isSwiping = true; });
    window.addEventListener('mouseup', () => { if(isSwiping) { isSwiping = false; resetSwipe(); } });
    window.addEventListener('mousemove', (e) => {
        if (!isSwiping) return;
        const containerRect = swipeContainer.getBoundingClientRect();
        let newRight = containerRect.right - e.clientX - (swipeButton.offsetWidth / 2);
        newRight = Math.max(0, Math.min(newRight, containerRect.width - swipeButton.offsetWidth));
        swipeButton.style.right = `${newRight}px`;
        if (newRight < 10) {
            isSwiping = false;
            swipeButton.style.display = 'none';
            swipeLabel.style.display = 'none';
            supervisorActions.style.display = 'flex';
        }
    });
    function resetSwipe() { swipeButton.style.right = '0'; }

    // --- Supervisor Message Templates (CORRECTED PATHS) ---
    // The create-confirm-link.php is in the main 'freelancer' folder. This path is now correct.
    document.getElementById('ratingLink').href = `api/create_confirm_link.php?gig_id=${gigId}`;
    document.getElementById('ratingLink').target = `_blank`;

    
    const paymentMessage = `مرحباً، أود التذكير بشأن مستحقاتي المالية لمهمة (<?= htmlspecialchars($gig['title']) ?>). الرجاء تحويل المبلغ إلى:\n[أضف تفاصيل حسابك البنكي هنا]\nشكراً لك.`;
    document.getElementById('paymentLink').href = `https://wa.me/<?= preg_replace('/\D/', '', $gig['supervisor_phone']) ?>?text=${encodeURIComponent(paymentMessage)}`;
    
    // --- API Calls (CORRECTED PATHS and HTTPS CHECK) ---
    const statusMsg = document.getElementById('statusMsg');
    function sendRequest(endpoint, payload) {
        // The path MUST go to the 'api' folder. This path is now correct.
        return fetch(`api/${endpoint}`, { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify(payload) 
        }).then(res => {
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return res.json();
            } else {
                return res.text().then(text => { throw new Error("Received non-JSON response from server:\n" + text) });
            }
        });
    }

    function getPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                return reject(new Error('خدمات الموقع غير مدعومة في هذا المتصفح.'));
            }
            // FIX: This check now correctly allows HTTP for localhost.
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                return reject(new Error('يجب استخدام اتصال آمن (HTTPS) لتفعيل خدمات الموقع.'));
            }
            navigator.geolocation.getCurrentPosition(
                pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                err => {
                    let message = 'فشل تحديد الموقع. ';
                    switch(err.code) {
                        case err.PERMISSION_DENIED: message += 'الرجاء السماح بالوصول للموقع.'; break;
                        case err.POSITION_UNAVAILABLE: message += 'معلومات الموقع غير متاحة.'; break;
                        case err.TIMEOUT: message += 'انتهت مهلة طلب الموقع.'; break;
                        default: message += 'حدث خطأ غير معروف.'; break;
                    }
                    reject(new Error(message));
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }

    async function handleApiAction(button, action, payload) {
        if(!button) return;
        button.disabled = true;
        statusMsg.innerText = 'جاري تنفيذ الإجراء...';
        try {
            const pos = await getPosition();
            payload.lat = pos.lat;
            payload.lng = pos.lng;
            const res = await sendRequest(action, payload);
            alert(res.message);
            if (res.ok) window.location.reload();
        } catch (err) {
            alert(err.message);
            console.error(err);
        } finally {
            button.disabled = false;
            statusMsg.innerText = '';
        }
    }

    document.getElementById('btnCheckin')?.addEventListener('click', (e) => handleApiAction(e.currentTarget, 'checkin.php', { gig_id: gigId }));
    document.getElementById('btnCheckout')?.addEventListener('click', (e) => handleApiAction(e.currentTarget, 'checkout.php', { gig_id: gigId }));
</script>
</body>
</html>