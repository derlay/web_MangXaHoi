<?php
// app/views/auth/login.php
if (!isset($errors)) $errors = [];
if (!isset($old)) $old = [];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<div class="card" style="padding:28px;border-radius:12px;">
    <h3 style="margin-bottom:8px;">Đăng nhập</h3>
    <p class="text-muted" style="margin-bottom:16px;">Đăng nhập vào tài khoản của bạn</p>

    

    <form method="post" action="/public/login.php">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="text" class="form-control" required
                value="<?= isset($old['email']) ? htmlspecialchars($old['email']) : '' ?>">
        </div>

        <div class="mb-3 position-relative">
            <label class="form-label">Mật khẩu</label>

            <input name="password" type="password" id="password" class="form-control" required>

            <span onclick="togglePassword('password')" 
                style="position:absolute; top:50%; right:14px; transform:translateY(-1%); cursor:pointer; font-size:18px; color:#6b7280;">
                <i class="bi bi-eye"></i>
            </span>
        </div>
        

        <div class="d-flex align-items-center justify-content-between">
            <div>
                <button type="submit" class="btn btn-primary">Đăng nhập</button>
            </div>
            <div>
                <a href="/public/register.php" class="btn btn-link">Tạo tài khoản mới</a>
            </div>
        </div>
    </form>
</div>
