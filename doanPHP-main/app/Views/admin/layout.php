<?php
$tab = $_GET['tab'] ?? 'dashboard';
$pendingReports = $data['pendingReports'] ?? 0;
$username = $_SESSION['username'] ?? 'admin';
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/public/css/admin.css">
</head>

<body class="admin">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="brand">
                <div class="brand-square">M</div>
                <span>Social Media</span>
            </div>
            <nav class="menu">
                <a href="/public/admin/index.php?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i
                        data-lucide="layout-dashboard" class="nav-icon"></i>
                    Tổng quan</a>
                <a href="/public/admin/index.php?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>"><i
                        data-lucide="users" class="nav-icon"></i>
                    Users</a>
                <a href="/public/admin/index.php?tab=reports" class="<?= $tab === 'reports' ? 'active' : '' ?>">
                    <i data-lucide="shield-alert" class="nav-icon"></i>
                    Reports
                    <?php if ($pendingReports > 0): ?><span class="badge"><?= $pendingReports ?></span><?php endif; ?>
                </a>
                <a href="/public/admin/index.php?tab=logs" class="<?= $tab === 'logs' ? 'active' : '' ?>"><i
                        data-lucide="history" class="nav-icon"></i>
                    Logs</a>
                <a href="/public/admin/index.php?tab=posts" class="<?= $tab === 'posts' ? 'active' : '' ?>"><i
                        data-lucide="file-text" class="nav-icon"></i> Posts</a>
                <div class="menu-sep"></div>
                <a href="/public/admin/index.php?tab=settings" class="<?= $tab === 'settings' ? 'active' : '' ?>"><i
                        data-lucide="settings" class="nav-icon"></i>Cài đặt</a>
            </nav>
            <div class="sidebar-user">
                <div class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
                <div class="info">
                    <div class="name"><?= htmlspecialchars($username) ?></div>
                    <div class="online"><span class="dot"></span> Online</div>
                </div>
                <div class="logout-box">
                    <a href="/public/logout.php" aria-label="Đăng xuất" title="Đăng xuất">
                        <i data-lucide="log-out"></i>
                    </a>
                </div>

            </div>

        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($title) ?></h1>
                <div class="hdr-right">
                    <a class="notif" href="/public/admin/index.php?tab=reports" aria-label="Xem Reports"
                        title="Xem Reports">
                        <i data-lucide="bell"></i>
                        <?php if ($pendingReports > 0): ?><span class="dot"></span><?php endif; ?></a>
                </div>
            </header>
            <section class="admin-content">
                <?php
                switch ($tab) {
                    case 'users':
                        include __DIR__ . '/tabs/users.php';
                        break;
                    case 'reports':
                        include __DIR__ . '/tabs/reports.php';
                        break;
                    case 'logs':
                        include __DIR__ . '/tabs/logs.php';
                        break;
                    case 'posts':
                        include __DIR__ . '/tabs/posts.php';
                        break;
                    case 'settings':
                        include __DIR__ . '/tabs/settings.php';
                        break;
                    case 'dashboard':
                    default:
                        include __DIR__ . '/tabs/dashboard.php';
                        break;
                }
                ?>
            </section>
        </main>
    </div>
</body>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons({
            attrs: {
                class: 'nav-icon',
                stroke: 'currentColor',
                'stroke-width': 1.8
            }
        });
    });
</script>

</html>