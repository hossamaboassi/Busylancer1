<?php
// This line gets the name of the current file being viewed (e.g., "dashboard.php")
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
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
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo">
            <img src="/images/logo.png" alt="BusyLancer Logo">
        </a>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <img src="<?= !empty($user['profile_photo']) ? '../' . htmlspecialchars($user['profile_photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] ?? 'User') . '&background=174F84&color=fff&size=140' ?>" alt="Avatar">
        </div>
        <div class="user-info">
            <h6><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h6>
            <small>حساب مستقل</small>
            <div class="user-rating">
                <div class="stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <div class="rating-number">4.8</div>
            </div>
        </div>
    </div>
    
    <div class="nav-menu">
        <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>لوحة التحكم</span>
        </a>
        <a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>الملف الشخصي</span>
        </a>
        <a href="find-work.php" class="<?= $currentPage === 'find-work.php' ? 'active' : '' ?>">
            <i class="fas fa-briefcase"></i>
            <span>الوظائف المتاحة</span>
        </a>
        
        <a href="my-jobs.php" class="<?= $currentPage === 'my-jobs.php' || $currentPage === 'applications.php' ? 'active' : '' ?>">
            <i class="fas fa-tasks"></i>
            <span>وظائفي </span>
        </a>
        <a href="finances.php" class="<?= $currentPage === 'finances.php' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i>
            <span>الأرباح</span>
        </a>
        <a href="messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i>
            <span>الرسائل</span>
        </a>
        <a href="calendar.php" class="<?= $currentPage === 'calendar.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>التقويم</span>
        </a>
        <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>الإعدادات</span>
        </a>
    </div>
    
    <div class="sidebar-footer">
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
        </a>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });
        }
    });
</script>