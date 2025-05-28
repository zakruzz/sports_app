<?php
session_start();
require_once 'config/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if the request is a POST with required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['movement_type']) || !isset($data['reps'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$movement_type = $data['movement_type'];
$reps = (int)$data['reps'];

try {
    $stmt = $pdo->prepare("INSERT INTO workout_logs (user_id, movement_type, reps) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $movement_type, $reps]);
    echo json_encode(['success' => true, 'message' => 'Workout data saved']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>