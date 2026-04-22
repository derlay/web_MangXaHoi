<?php
// app/views/auth/register.php
if (!isset($errors)) $errors = [];
if (!isset($old)) $old = [];
?>
<div class="card" style="padding:28px;border-radius:12px;">
    <h3 style="margin-bottom:8px;">Đăng ký</h3>
    <p class="text-muted" style="margin-bottom:16px;">Tạo tài khoản mới</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/public/register.php">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" type="text" class="form-control" required
                value="<?= isset($old['username']) ? htmlspecialchars($old['username']) : '' ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Họ và tên</label>
            <input name="full_name" type="text" class="form-control" required
                value="<?= isset($old['full_name']) ? htmlspecialchars($old['full_name']) : '' ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required
                value="<?= isset($old['email']) ? htmlspecialchars($old['email']) : '' ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input name="password" type="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Xác nhận mật khẩu</label>
            <input name="password_confirm" type="password" class="form-control" required>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <div>
                <button type="submit" class="btn btn-primary">Đăng ký</button>
            </div>
            <div>
                <a href="/public/login.php" class="btn btn-link">Đã có tài khoản? Đăng nhập</a>
            </div>
        </div>
    </form>
</div>