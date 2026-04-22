<?php
// app/views/auth/layout_auth.php
// Minimal, clean auth layout.
// Variables available: $page ('login' or 'register'), $errors (array), $old (array), $title (string)

$page  = isset($page)  ? $page  : (isset($_GET['page']) ? $_GET['page'] : 'login');
$errors = isset($errors) ? $errors : [];
$old    = isset($old) ? $old : [];
$title  = isset($title) ? $title : ($page === 'register' ? 'Đăng ký' : 'Đăng nhập');
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Reuse project's CSS -->
    <link rel="stylesheet" href="/public/asset/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/css/base.css">
    <link rel="stylesheet" href="/public/css/components.css">
    <link rel="stylesheet" href="/public/css/auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* Small layout helpers for auth pages (keeps layout file minimal) */
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--bg-main, #f3f4f8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        .auth-wrap {
            width: 100%;
            max-width: 920px;
            margin: 32px;
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 32px;
            align-items: center;
        }

        .auth-panel {
            background: transparent;
        }

        .auth-card {
            background: var(--bg-card, #fff);
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .auth-logo {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: var(--shadow-light, 0 4px 15px rgba(15, 23, 42, 0.06));
        }

        .auth-meta {
            color: var(--text-muted, #6b7280);
            font-size: 14px;
        }

        .auth-aside {
            display: none;
            /* optional visual aside, hidden on small screens */
        }

        @media(min-width:1100px) {
            .auth-aside {
                display: block;
            }
        }

        /* simple error list */
        .auth-errors {
            margin-bottom: 14px;
        }

        .auth-errors ul {
            margin: 0;
            padding-left: 18px;
        }
    </style>
</head>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = event.target.tagName === "I" ? event.target : event.target.querySelector("i");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>


<body>
    <div class="auth-wrap">

        <!-- Optional left visual/marketing column (keeps layout balanced on wide screens) -->
        <div class="auth-aside">
            <div class="auth-card" style="text-align:center;">
                <div class="auth-brand" style="justify-content:center;">
                    <div class="auth-logo">MXH</div>
                    <div>
                        <div style="font-weight:700;font-size:18px;">Social Network</div>
                        <div class="auth-meta">Kết nối bạn bè. Chia sẻ khoảnh khắc.</div>
                    </div>
                </div>

                
            </div>
        </div>

        <!-- Right: auth panel -->
        <div class="auth-panel">
            <div class="auth-card">

                <!-- Header / brand -->
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <!-- <div class="auth-brand">
                        <div class="auth-logo">MXH</div>
                        <div>
                            <div style="font-weight:700;">Social Network</div>
                            <div class="auth-meta" style="font-size:13px;">
                                <?= $page === 'register' ? 'Tạo tài khoản mới' : 'Đăng nhập vào tài khoản' ?></div>
                        </div>
                    </div> -->
                </div>

                <!-- Flash / success message -->
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success" role="alert">Đăng ký thành công. Vui lòng đăng nhập.</div>
                <?php endif; ?>

                <!-- Errors -->
                <!-- <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger auth-errors" role="alert">
                        <ul>
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?> -->

                <!-- Include the specific form -->
                <div>
                    <?php if ($page === 'register'): ?>
                        <?php include __DIR__ . '/register.php'; ?>
                    <?php else: ?>
                        <?php include __DIR__ . '/login.php'; ?>
                    <?php endif; ?>
                </div>

                <!-- Small footer -->
                <div style="margin-top:14px;text-align:center;color:var(--text-muted,#9aa0ab);font-size:13px;">
                    Bằng việc tiếp tục, bạn đồng ý với Điều khoản và Chính sách bảo mật của chúng tôi.
                </div>

            </div>
        </div>

    </div>

    <script src="/public/asset/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>