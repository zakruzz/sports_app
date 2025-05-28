<?php
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['id'];

// Fetch workout history from workout_logs table
$stmt = $pdo->prepare("SELECT movement_type, reps, created_at FROM workout_logs WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$workout_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout History - Sports Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <style>
        body {
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460, #1a1a2e);
            background-size: 400%;
            animation: gradientAnimation 15s ease infinite;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            min-height: 100vh;
            position: relative;
            z-index: 0;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            padding: 1rem 2rem;
            z-index: 100;
        }
        .navbar a {
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .navbar a:hover {
            color: #00ddeb;
            transform: scale(1.05);
        }
        .container {
            padding: 2rem;
            z-index: 10;
        }
        .card {
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            position: relative;
            z-index: 100;
            overflow: hidden;
        }
        .input-field {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #2d3748;
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
            box-sizing: border-box;
            z-index: 101;
        }
        .input-field:focus {
            border-color: #00ddeb;
            box-shadow: 0 0 15px rgba(0, 221, 235, 0.6);
            outline: none;
        }
        .input-field::placeholder {
            color: #a0aec0;
        }
        .gradient-btn {
            background: linear-gradient(45deg, #00ddeb, #ff007a);
            transition: transform 0.3s ease;
            padding: 0.85rem;
            border-radius: 0.5rem;
            width: 100%;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            z-index: 101;
        }
        .gradient-btn:hover {
            transform: scale(1.05);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 221, 235, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(0, 221, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 221, 235, 0); }
        }
        .progress-bar {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #00ddeb, #ff007a);
            transition: width 0.5s ease;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #2d3748;
        }
        td {
            color: #4a5568;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">Sports Hub</h1>
            <div class="space-x-6">
                <a href="dashboard.php" class="text-white hover:text-cyan-400">Dashboard</a>
                <a href="logout.php" class="text-white hover:text-cyan-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto">
        <div class="card">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Workout History</h2>
            <?php if (empty($workout_logs)): ?>
                <p class="text-gray-600">No workout history available. Start a workout to see your progress here!</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Movement Type</th>
                                <th>Reps</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workout_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['created_at']))); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($log['movement_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['reps']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // GSAP animations
        gsap.from(".navbar", { opacity: 0, y: -50, duration: 1, ease: "power3.out" });
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, ease: "power3.out" });
        gsap.from("table tr", { opacity: 0, y: 20, duration: 0.8, stagger: 0.1, ease: "power3.out" });
    </script>
</body>
</html>