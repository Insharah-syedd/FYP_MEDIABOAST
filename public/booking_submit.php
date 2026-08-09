<?php
require_once '../includes/config.php';
require_once '../includes/notify.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$name         = trim($_POST['name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$business     = trim($_POST['business_name'] ?? '');
$service_id   = $_POST['service_id'] ?: null;
$budget       = $_POST['budget_range'] ?? 'under_50k';
$message      = trim($_POST['message'] ?? '');

if (!$name || !$email || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO bookings (name, email, phone, business_name, service_id, budget_range, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$name, $email, $phone, $business, $service_id, $budget, $message]);

    // Send notification
    addNotification('New booking received from '.$name.' ('.$phone.')', 'booking', null, '../admin/bookings.php');
    // Also add as a lead automatically
    $db->prepare("INSERT INTO leads (name, email, phone, business_name, service_interest, source, status, ai_score) VALUES (?, ?, ?, ?, ?, 'website', 'new', ?)")
       ->execute([$name, $email, $phone, $business, 'Website Booking', rand(50, 85)]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
