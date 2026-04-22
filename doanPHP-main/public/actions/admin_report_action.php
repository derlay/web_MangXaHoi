<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'User') !== 'Admin') {
    http_response_code(403);
    exit('Forbidden');
}

$adminId = (int)$_SESSION['user_id'];
$reportId = (int)($_POST['report_id'] ?? 0);
$action   = $_POST['action'] ?? '';
if ($reportId <= 0 || !in_array($action, ['resolve', 'dismiss'], true)) {
    header('Location:/public/admin/index.php?tab=reports');
    exit;
}

$status = $action === 'resolve' ? 'resolved' : 'dismissed';
$u = $conn->prepare("UPDATE reports SET status=? WHERE report_id=?");
$u->bind_param('si', $status, $reportId);
$u->execute();
$u->close();

$logAction = $action === 'resolve' ? 'resolve_report' : 'dismiss_report';
$log = $conn->prepare("
  INSERT INTO admin_action_logs (admin_user_id, action_type, target_type, target_id, details, created_at)
  VALUES (?, ?, 'report', ?, ?, NOW())
");
$details = ucfirst($status) . " report #{$reportId}";
$log->bind_param('isis', $adminId, $logAction, $reportId, $details);
$log->execute();
$log->close();

header('Location:/public/admin/index.php?tab=reports');
