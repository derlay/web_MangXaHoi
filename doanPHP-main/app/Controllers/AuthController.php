<?php
require_once __DIR__ . '/../config/database.php';


class AuthController
{
    protected $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function showLogin($errors = [], $old = [])
    {
        require __DIR__ . '/../views/auth/layout_auth.php';
    }

    public function showRegister($errors = [], $old = [])
    {
        require __DIR__ . '/../views/auth/layout_auth.php';
    }

    public function handleLogin()
    {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $errors = [];
        if ($email === '')  $errors[] = 'Vui lòng nhập email hoặc username.';
        if ($password === '') $errors[] = 'Vui lòng nhập mật khẩu.';
        if (!empty($errors)) {
            $this->showLogin($errors, ['email' => $email]);
            return;
        }

        $isEmail = strpos($email, '@') !== false;
        $sql = $isEmail
            ? "SELECT user_id, full_name, email, password_hash, username FROM users WHERE email = ? LIMIT 1"
            : "SELECT user_id, full_name, email, password_hash, username FROM users WHERE username = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->showLogin(['Database error: ' . $this->conn->error], ['email' => $email]);
            return;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$user) {
            $this->showLogin(['Tài khoản không tồn tại.'], ['email' => $email]);
            return;
        }
        if (!password_verify($password, $user['password_hash'])) {
            $this->showLogin(['Mật khẩu không đúng.'], ['email' => $email]);
            return;
        }

        // Lấy các role theo schema user_roles + roles
        $rstmt = $this->conn->prepare("
            SELECT r.role_name
            FROM user_roles ur
            JOIN roles r ON r.role_id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $rstmt->bind_param('i', $user['user_id']);
        $rstmt->execute();
        $res = $rstmt->get_result();
        $roleNames = array_map(fn($row) => $row['role_name'], $res->fetch_all(MYSQLI_ASSOC));
        $rstmt->close();

        // Chọn role 
        $role = 'User';
        if (in_array('Admin', $roleNames, true))       $role = 'Admin';
        elseif (in_array('Moderator', $roleNames, true)) $role = 'Moderator';

        $_SESSION['user_id']   = (int)$user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $role;

        // Điều hướng
        if ($role === 'Admin') header('Location: /public/admin/index.php');
        else                   header('Location: /public/index.php');
        exit;
    }

    public function handleRegister()
    {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

        $errors = [];
        if ($username === '') $errors[] = 'Vui lòng nhập username.';
        if ($full_name === '') $errors[] = 'Vui lòng nhập tên.';
        if ($email === '') $errors[] = 'Vui lòng nhập email.';
        if ($password === '') $errors[] = 'Vui lòng nhập mật khẩu.';
        if ($password !== $password_confirm) $errors[] = 'Mật khẩu xác nhận không khớp.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';

        if (!empty($errors)) {
            $this->showRegister($errors, ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }

        // Unique checks
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            $this->showRegister(['Lỗi hệ thống (DB).'], ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            $this->showRegister(['Username đã được sử dụng.'], ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $this->showRegister(['Lỗi hệ thống (DB).'], ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            $this->showRegister(['Email đã được sử dụng.'], ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }
        $stmt->close();

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("
            INSERT INTO users (username, email, password_hash, full_name, profile_picture_url, cover_photo_url, bio, created_at)
            VALUES (?, ?, ?, ?, 'default_avatar.jpg', 'default_cover.jpg', '', NOW())
        ");
        if (!$stmt) {
            $this->showRegister(['Lỗi hệ thống (DB). Vui lòng thử lại.'], ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }
        $stmt->bind_param('ssss', $username, $email, $password_hash, $full_name);
        if (!$stmt->execute()) {
            $stmt->close();
            $this->showRegister(['Không thể tạo tài khoản, vui lòng thử lại.'], ['username' => $username, 'full_name' => $full_name, 'email' => $email]);
            return;
        }
        $stmt->close();

        header('Location: /public/login.php?registered=1');
        exit;
    }
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
        }
        session_destroy();
        header('Location: /public/login.php');
        exit;
    }
}
