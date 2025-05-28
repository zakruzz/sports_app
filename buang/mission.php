<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pushups = (int)$_POST['pushups'];
    $situps = (int)$_POST['situps'];
    $squatjumps = (int)$_POST['squatjumps'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $completed = $pushups >= $user['min_pushups'] && $situps >= $user['min_situps'] && $squatjumps >= $user['min_squatjumps'];
    $stmt = $pdo->prepare("INSERT INTO daily_missions (user_id, date, pushups, situps, squatjumps, completed) VALUES (?, CURDATE(), ?, ?, ?, ?)");
    $stmt->execute([$user_id, $pushups, $situps, $squatjumps, $completed]);
    $stmt = $pdo->prepare("INSERT INTO history (user_id, date, pushups, situps, squatjumps) VALUES (?, CURDATE(), ?, ?, ?)");
    $stmt->execute([$user_id, $pushups, $situps, $squatjumps]);
    if ($completed) {
        $new_level = $user['level'] + 1;
        $new_min_pushups = max(10, $user['min_pushups'] - 2);
        $new_min_situps = max(10, $user['min_situps'] - 2);
        $new_min_squatjumps = max(10, $user['min_squatjumps'] - 2);
        $stmt = $pdo->prepare("UPDATE users SET level = ?, min_pushups = ?, min_situps = ?, min_squatjumps = ? WHERE id = ?");
        $stmt->execute([$new_level, $new_min_pushups, $new_min_situps, $new_min_squatjumps, $user_id]);
        $_SESSION['user']['level'] = $new_level;
    }
    header('Location: dashboard.php');
    exit;
}
?>