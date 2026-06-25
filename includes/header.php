<?php
// includes/header.php
// Assumes config/database.php and includes/functions.php are already included
$currentUserEmail = '';
if (isLoggedIn() && isset($pdo)) {
    $stmtUser = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $currentUserEmail = $stmtUser->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STDC Program Registration System</title>
    <link rel="stylesheet" href="/stdc-program-onboarding-system/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Top Navbar -->
<header class="top-navbar">
    <div class="top-nav-left" style="display: flex; align-items: center; gap: 1rem;">
        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('app-sidebar');
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('mobile-open');
                } else {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            }
        </script>
        <button class="top-bar-toggle" onclick="toggleSidebar()" style="background: transparent; border: none; cursor: pointer; color: var(--text-dark); display: flex; align-items: center; justify-content: center; padding: 6px; margin-left: -6px; border-radius: 4px; transition: background-color 0.2s;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <img src="/stdc-program-onboarding-system/assets/img/LogoSTDC.png" alt="STDC Logo" style="height: 30px; width: auto;">
    </div>
    <div class="top-nav-right">
        <span class="badge badge-active"><?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></span>
        <a href="/stdc-program-onboarding-system/user/profile.php" class="top-profile-link" style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-dark); text-decoration: none; padding: 6px; border-radius: 4px; transition: background-color 0.2s;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-light);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span class="user-name" style="font-weight: 500; font-size: 0.875rem;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </a>
    </div>
</header>
<div class="app-container">
    <aside class="sidebar" id="app-sidebar">
        <script>
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.getElementById('app-sidebar').classList.add('collapsed');
            }
        </script>
        <nav class="sidebar-nav">
            <ul>
                <?php 
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                function isActive($path, $currentPath) {
                    if ($currentPath === $path) return 'class="active"';
                    
                    // Keep Manage Programs highlighted for sub-pages
                    if ($path === '/stdc-program-onboarding-system/admin/programs.php') {
                        if (strpos($currentPath, '/stdc-program-onboarding-system/admin/create_program.php') === 0 ||
                            strpos($currentPath, '/stdc-program-onboarding-system/admin/edit_program.php') === 0 ||
                            strpos($currentPath, '/stdc-program-onboarding-system/admin/attendees.php') === 0 ||
                            strpos($currentPath, '/stdc-program-onboarding-system/admin/attendee_details.php') === 0) {
                            return 'class="active"';
                        }
                    }
                    return '';
                }
                ?>
                <?php if (isAdmin()): ?>
                    <li><a href="/stdc-program-onboarding-system/admin/index.php" <?php echo isActive('/stdc-program-onboarding-system/admin/index.php', $currentPath); ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span class="nav-text">Dashboard</span>
                    </a></li>
                    <li><a href="/stdc-program-onboarding-system/admin/programs.php" <?php echo isActive('/stdc-program-onboarding-system/admin/programs.php', $currentPath); ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span class="nav-text">Manage Programs</span>
                    </a></li>
                    <li><a href="/stdc-program-onboarding-system/admin/users.php" <?php echo isActive('/stdc-program-onboarding-system/admin/users.php', $currentPath); ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span class="nav-text">Manage Users</span>
                    </a></li>
                    <?php if (isset($currentUserEmail) && $currentUserEmail === 'admin@stdc.com'): ?>
                        <li><a href="/stdc-program-onboarding-system/admin/manage_admins.php" <?php echo isActive('/stdc-program-onboarding-system/admin/manage_admins.php', $currentPath); ?>>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span class="nav-text">Manage Admins</span>
                        </a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="/stdc-program-onboarding-system/user/index.php" <?php echo isActive('/stdc-program-onboarding-system/user/index.php', $currentPath); ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span class="nav-text">Dashboard</span>
                    </a></li>
                    <li><a href="/stdc-program-onboarding-system/user/profile.php" <?php echo isActive('/stdc-program-onboarding-system/user/profile.php', $currentPath); ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span class="nav-text">My Profile</span>
                    </a></li>
                <?php endif; ?>
                <li><a href="/stdc-program-onboarding-system/auth/logout.php" style="color: var(--danger);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span class="nav-text">Logout</span>
                </a></li>
            </ul>
        </nav>
    </aside>
    <main class="main-content">
        <?php displayAlert(); ?>
<?php else: ?>
    <!-- For non-logged in users (e.g. login/register pages) -->
    <?php displayAlert(); ?>
<?php endif; ?>
