<?php
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Calculate mission targets based on user level
$level = $user['level'];
$target_pushups = $level * 10;
$target_situps = $level * 8;
$target_squatjumps = $level * 6;

// Fetch min_pushups, min_situps, min_squatjumps for intensity calculation
$stmt = $pdo->prepare("SELECT min_pushups, min_situps, min_squatjumps FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$mission = $stmt->fetch();

// Calculate simulated intensity (BPM)
$intensity = 0;
if ($mission) {
    $intensity = ($mission['min_pushups'] + $mission['min_situps'] + $mission['min_squatjumps']) * 2;
}

// Fetch the most recent workout date from workout_logs
$stmt = $pdo->prepare("SELECT DATE(created_at) as latest_date FROM workout_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_date_row = $stmt->fetch(PDO::FETCH_ASSOC);
$today = $latest_date_row ? $latest_date_row['latest_date'] : date('Y-m-d');

// Fetch today's progress from workout_logs
$stmt = $pdo->prepare("
    SELECT movement_type, SUM(reps) as total_reps
    FROM workout_logs
    WHERE user_id = ? AND DATE(created_at) = ?
    GROUP BY movement_type
");
$stmt->execute([$user_id, $today]);
$today_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize progress
$progress_pushups = 0;
$progress_situps = 0;
$progress_squatjumps = 0;

foreach ($today_logs as $log) {
    switch ($log['movement_type']) {
        case 'pushups':
            $progress_pushups = $log['total_reps'];
            break;
        case 'situps':
            $progress_situps = $log['total_reps'];
            break;
        case 'squatjumps':
            $progress_squatjumps = $log['total_reps'];
            break;
    }
}

// Calculate progress percentages
$pushup_percentage = $target_pushups > 0 ? min(($progress_pushups / $target_pushups) * 100, 100) : 0;
$situp_percentage = $target_situps > 0 ? min(($progress_situps / $target_situps) * 100, 100) : 0;
$squatjump_percentage = $target_squatjumps > 0 ? min(($progress_squatjumps / $target_squatjumps) * 100, 100) : 0;

// Fetch last 5 days of workout data
$last_5_days_data = [];
$dates = [];
$pushups_data = [];
$situps_data = [];
$squatjumps_data = [];

$stmt = $pdo->prepare("
    SELECT DATE(created_at) as workout_date, movement_type, SUM(reps) as total_reps
    FROM workout_logs
    WHERE user_id = ? AND DATE(created_at) >= DATE_SUB(?, INTERVAL 4 DAY)
    GROUP BY DATE(created_at), movement_type
    ORDER BY workout_date ASC
");
$stmt->execute([$user_id, $today]);
$graph_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$start_date = date('Y-m-d', strtotime($today . ' -4 days'));
for ($i = 0; $i < 5; $i++) {
    $current_date = date('Y-m-d', strtotime($start_date . " +$i days"));
    $dates[] = $current_date;
    $pushups_data[$current_date] = 0;
    $situps_data[$current_date] = 0;
    $squatjumps_data[$current_date] = 0;
}

foreach ($graph_logs as $log) {
    $date = $log['workout_date'];
    switch ($log['movement_type']) {
        case 'pushups':
            $pushups_data[$date] = $log['total_reps'];
            break;
        case 'situps':
            $situps_data[$date] = $log['total_reps'];
            break;
        case 'squatjumps':
            $squatjumps_data[$date] = $log['total_reps'];
            break;
    }
}

$pushups_array = array_values($pushups_data);
$situps_array = array_values($situps_data);
$squatjumps_array = array_values($squatjumps_data);

// Fetch workout history (last 30 days)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as workout_date, movement_type, SUM(reps) as total_reps
    FROM workout_logs
    WHERE user_id = ? AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at), movement_type
    ORDER BY workout_date DESC
");
$stmt->execute([$user_id]);
$history_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sports Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
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
        .voice-btn {
            background: linear-gradient(45deg, #34d399, #059669);
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .voice-btn:hover {
            transform: scale(1.05);
        }
        .voice-btn.off {
            background: linear-gradient(45deg, #ef4444, #b91c1c);
        }
        .status-text {
            color: #4b5563;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        #workoutIcon {
            transition: opacity 0.5s ease;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .history-table th, .history-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .history-table th {
            background: #f7fafc;
            font-weight: 600;
        }
        .history-table tr:hover {
            background: #f1f5f9;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">TechIns</h1>
            <div class="space-x-6">
                <a href="logout.php" class="text-white hover:text-cyan-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto">
        <!-- User Info and Voice Control -->
        <div class="card mb-6">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            <p class="text-gray-600 mb-6">Level: <?php echo $user['level']; ?> | Today's Targets: <?php echo $target_pushups; ?> Push-ups, <?php echo $target_situps; ?> Sit-ups, <?php echo $target_squatjumps; ?> Squat Jumps (Date: <?php echo $today; ?>)</p>
            <div class="text-center">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Voice Chat Control</h3>
                <button id="voiceChatBtn" class="voice-btn off">Start Voice Chat</button>
                <p id="voiceStatus" class="status-text">Voice chat is off</p>
            </div>
        </div>
        <!-- Today's Mission -->
        <div class="card mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="text-left">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Today's Mission</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Push-ups: <?php echo $progress_pushups; ?> / <?php echo $target_pushups; ?></label>
                        <div class="progress-bar"><div class="progress-fill" id="pushupProgress" style="width: <?php echo $pushup_percentage; ?>%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Sit-ups: <?php echo $progress_situps; ?> / <?php echo $target_situps; ?></label>
                        <div class="progress-bar"><div class="progress-fill" id="situpProgress" style="width: <?php echo $situp_percentage; ?>%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Squat Jumps: <?php echo $progress_squatjumps; ?> / <?php echo $target_squatjumps; ?></label>
                        <div class="progress-bar"><div class="progress-fill" id="squatjumpProgress" style="width: <?php echo $squatjump_percentage; ?>%;"></div></div>
                    </div>
                    <p id="motivationMessage" class="text-center text-gray-600 mt-4 hidden">Keep pushing! You're almost there!</p>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Progress Graph</h3>
                    <canvas id="progressChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Realtime Workout Tracker -->
        <div class="card mb-6">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Realtime Workout Tracker</h2>
            <div class="flex justify-center mb-6">
                <img id="workoutIcon" src="dumbell.gif" alt="Workout Icon" class="w-32 h-24">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="text-left">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Live Workout Stats</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Push-ups</label>
                        <input type="number" id="pushupInput" class="input-field" readonly placeholder="Detected by ESP32-S3">
                        <div class="progress-bar"><div class="progress-fill" id="pushupProgressRealtime" style="width: 0%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Sit-ups</label>
                        <input type="number" id="situpInput" class="input-field" readonly placeholder="Detected by ESP32-S3">
                        <div class="progress-bar"><div class="progress-fill" id=" CENTERED
situpProgressRealtime" style="width: 0%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Squat Jumps</label>
                        <input type="number" id="squatjumpInput" class="input-field" readonly placeholder="Detected by ESP32-S3">
                        <div class="progress-bar"><div class="progress-fill" id="squatjumpProgressRealtime" style="width: 0%;"></div></div>
                    </div>
                    <p id="realtimeMotivation" class="text-center text-gray-600 mt-4 hidden">Keep going! You're crushing it!</p>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Workout Progress</h3>
                    <canvas id="workoutChart"></canvas>
                </div>
            </div>
            <div class="text-center mt-6">
                <button id="startWorkoutBtn" class="gradient-btn">Start Workout</button>
            </div>
        </div>
        <!-- Workout History -->
        <div class="card">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Workout History</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Movement Type</th>
                        <th>Reps</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history_logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['workout_date']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($log['movement_type'])); ?></td>
                        <td><?php echo htmlspecialchars($log['total_reps']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        // GSAP animations
        gsap.from(".navbar", { opacity: 0, y: -50, duration: 1, ease: "power3.out" });
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, stagger: 0.3, ease: "power3.out" });

        // Progress Chart (Today's Mission)
        const ctxProgress = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctxProgress, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    { label: 'Push-ups', data: <?php echo json_encode($pushups_array); ?>, borderColor: '#3b82f6', fill: false, tension: 0.4 },
                    { label: 'Sit-ups', data: <?php echo json_encode($situps_array); ?>, borderColor: '#10b981', fill: false, tension: 0.4 },
                    { label: 'Squat Jumps', data: <?php echo json_encode($squatjumps_array); ?>, borderColor: '#ef4444', fill: false, tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                plugins: { tooltip: { enabled: true }, legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Workout Chart (Realtime Tracker)
        const ctxWorkout = document.getElementById('workoutChart').getContext('2d');
        const workoutChart = new Chart(ctxWorkout, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Push-ups', data: [], borderColor: '#3b82f6', fill: false, tension: 0.4, hidden: false },
                    { label: 'Sit-ups', data: [], borderColor: '#10b981', fill: false, tension: 0.4, hidden: false },
                    { label: 'Squat Jumps', data: [], borderColor: '#ef4444', fill: false, tension: 0.4, hidden: false }
                ]
            },
            options: {
                responsive: true,
                plugins: { tooltip: { enabled: true }, legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // MQTT Configuration
        const broker = 'test.mosquitto.org';
        const port = 8081;
        const clientId = 'web_client_' + Math.random().toString(16).substr(2, 8);
        const topics = {
            pushups: 'sports/pushups',
            situps: 'sports/situps',
            squatjumps: 'sports/squatjumps',
            movementType: 'sports/movement_type'
        };

        const client = new Paho.MQTT.Client(broker, port, clientId);
        let currentMovementType = 'unknown';
        let movementData = {
            pushups: 0,
            situps: 0,
            squatjumps: 0
        };

        client.onConnectionLost = function(response) {
            console.log('Connection lost: ' + response.errorMessage);
        };

        client.onMessageArrived = function(message) {
            const topic = message.destinationName;
            const payload = message.payloadString;

            const maxReps = 100;
            let movementType, inputId, progressId, datasetIndex;

            if (topic === topics.pushups) {
                movementType = 'pushups';
                inputId = 'pushupInput';
                progressId = 'pushupProgressRealtime';
                datasetIndex = 0;
                movementData.pushups = parseInt(payload);
            } else if (topic === topics.situps) {
                movementType = 'situps';
                inputId = 'situpInput';
                progressId = 'situpProgressRealtime';
                datasetIndex = 1;
                movementData.situps = parseInt(payload);
            } else if (topic === topics.squatjumps) {
                movementType = 'squatjumps';
                inputId = 'squatjumpInput';
                progressId = 'squatjumpProgressRealtime';
                datasetIndex = 2;
                movementData.squatjumps = parseInt(payload);
            } else if (topic === topics.movementType) {
                currentMovementType = payload;
                const workoutIcon = document.getElementById('workoutIcon');
                let newSrc;
                switch (payload) {
                    case 'pushups':
                        newSrc = 'pushup.gif';
                        workoutIcon.alt = 'Push-up Icon';
                        workoutChart.data.datasets.forEach((ds, i) => ds.hidden = i !== 0);
                        break;
                    case 'situps':
                        newSrc = 'situp.gif';
                        workoutIcon.alt = 'Sit-up Icon';
                        workoutChart.data.datasets.forEach((ds, i) => ds.hidden = i !== 1);
                        break;
                    case 'squatjumps':
                        newSrc = 'squatjump.gif';
                        workoutIcon.alt = 'Squat Jump Icon';
                        workoutChart.data.datasets.forEach((ds, i) => ds.hidden = i !== 2);
                        break;
                    default:
                        newSrc = 'dumbell.gif';
                        workoutIcon.alt = 'Workout Icon';
                        currentMovementType = 'unknown';
                        workoutChart.data.datasets.forEach(ds => ds.hidden = true);
                }
                gsap.to(workoutIcon, {
                    opacity: 0,
                    duration: 0.3,
                    onComplete: () => {
                        workoutIcon.src = newSrc;
                        gsap.to(workoutIcon, { opacity: 1, duration: 0.3 });
                    }
                });
                workoutChart.update();
                return;
            } else {
                return;
            }

            const value = parseInt(payload);
            document.getElementById(inputId).value = value;
            document.getElementById(progressId).style.width = `${Math.min((value / maxReps) * 100, 100)}%`;

            workoutChart.data.labels.push(new Date().toLocaleTimeString());
            workoutChart.data.datasets[datasetIndex].data.push(value);
            if (workoutChart.data.labels.length > 10) {
                workoutChart.data.labels.shift();
                workoutChart.data.datasets.forEach(ds => ds.data.shift());
            }
            workoutChart.update();

            checkRealtimeMotivation(value, <?php echo $intensity; ?>);

            if (movementType !== 'unknown') {
                fetch('save_workout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ movement_type: movementType, reps: value })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to save workout data:', data.message);
                    }
                })
                .catch(error => console.error('Error saving workout data:', error));
            }
        };

        function checkRealtimeMotivation(reps, intensity) {
            const motivationMessage = document.getElementById('realtimeMotivation');
            if (reps > 50 || intensity > 120) {
                motivationMessage.textContent = "Keep going! You're crushing it!";
                motivationMessage.classList.remove('hidden');
                motivationMessage.classList.remove('text-gray-600');
                motivationMessage.classList.add('text-green-600', 'font-semibold');
            } else if (reps > 0 || intensity > 80) {
                motivationMessage.textContent = "Stay strong! You're doing great!";
                motivationMessage.classList.remove('hidden');
                motivationMessage.classList.remove('text-green-600');
                motivationMessage.classList.add('text-gray-600');
            } else {
                motivationMessage.classList.add('hidden');
            }
        }

        client.connect({ onSuccess: onConnect });

        function onConnect() {
            console.log('Connected to MQTT broker');
            client.subscribe(topics.pushups);
            client.subscribe(topics.situps);
            client.subscribe(topics.squatjumps);
            client.subscribe(topics.movementType);
        }

        // Workout Button Logic
        const startWorkoutBtn = document.getElementById('startWorkoutBtn');
        function toggleWorkout(state) {
            if (state) {
                startWorkoutBtn.textContent = 'Workout In Progress...';
                startWorkoutBtn.classList.add('cursor-not-allowed', 'opacity-75');
                startWorkoutBtn.disabled = true;
                gsap.to(startWorkoutBtn, { scale: 1.1, duration: 0.3, yoyo: true, repeat: 1 });
            } else {
                startWorkoutBtn.textContent-layer1
                startWorkoutBtn.textContent = 'Start Workout';
                startWorkoutBtn.classList.remove('cursor-not-allowed', 'opacity-75');
                startWorkoutBtn.disabled = false;
            }
        }

        startWorkoutBtn.addEventListener('click', () => {
            toggleWorkout(true);
        });

        // Today's Mission Motivation
        const pushupProgress = <?php echo $progress_pushups; ?>;
        const situpProgress = <?php echo $progress_situps; ?>;
        const squatjumpProgress = <?php echo $progress_squatjumps; ?>;
        const targetPushups = <?php echo $target_pushups; ?>;
        const targetSitups = <?php echo $target_situps; ?>;
        const targetSquatjumps = <?php echo $target_squatjumps; ?>;
        const motivationMessage = document.getElementById('motivationMessage');

        function checkProgress() {
            if (pushupProgress >= targetPushups && situpProgress >= targetSitups && squatjumpProgress >= targetSquatjumps) {
                motivationMessage.textContent = "Great job! You've completed today's mission!";
                motivationMessage.classList.remove('hidden');
                motivationMessage.classList.remove('text-gray-600');
                motivationMessage.classList.add('text-green-600', 'font-semibold');
            } else if (pushupProgress > 0 || situpProgress > 0 || squatjumpProgress > 0) {
                motivationMessage.textContent = "Keep pushing! You're almost there!";
                motivationMessage.classList.remove('hidden');
                motivationMessage.classList.remove('text-green-600');
                motivationMessage.classList.add('text-gray-600');
            } else {
                motivationMessage.classList.add('hidden');
            }
        }

        checkProgress();

        // Voice Control
        const voiceBtn = document.getElementById('voiceChatBtn');
        const statusText = document.getElementById('voiceStatus');
        let recognition;
        let isVoiceActive = false;
        let retryCount = 0;
        const maxRetries = 3;
        const isVisuallyImpaired = sessionStorage.getItem('isVisuallyImpaired') === 'true';
        const hasAnswered = sessionStorage.getItem('hasAnswered') === 'true';

        function speak(text, callback) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-US';
            utterance.rate = 0.7;
            utterance.pitch = 1.2;
            utterance.volume = 1.0;

            function setVoice() {
                const voices = window.speechSynthesis.getVoices();
                const englishVoice = voices.find(voice => voice.lang === 'en-US') || voices[0];
                utterance.voice = englishVoice;
                window.speechSynthesis.speak(utterance);
            }

            utterance.onend = () => {
                setTimeout(() => {
                    if (callback) callback();
                }, 2000);
            };
            utterance.onerror = (event) => {
                statusText.textContent = 'Voice chat error: ' + event.error;
            };

            if (window.speechSynthesis.getVoices().length > 0) {
                setVoice();
            } else {
                window.speechSynthesis.onvoiceschanged = () => {
                    setVoice();
                    window.speechSynthesis.onvoiceschanged = null;
                };
            }
        }

        if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
            recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            recognition.continuous = true;

            recognition.onresult = function(event) {
                const command = event.results[0][0].transcript.toLowerCase();
                statusText.textContent = `Heard: "${command}"`;
                retryCount = 0;

                if (!isVoiceActive) return;

                if (!hasAnswered) {
                    if (command.includes('yes')) {
                        sessionStorage.setItem('isVisuallyImpaired', 'true');
                        sessionStorage.setItem('hasAnswered', 'true');
                        speak('Voice navigation enabled.', () => {
                            speak('Say "progress" for mission progress.', () => {
                                speak('Say "start workout" to begin tracking.', () => {
                                    speak('Say "read stats" for live stats.', () => {
                                        speak('Say "view history" for workout history.', () => {
                                            speak('Or say "exit" to exit.');
                                        });
                                    });
                                });
                            });
                        });
                        statusText.textContent = 'Voice navigation enabled.';
                    } else if (command.includes('no')) {
                        sessionStorage.setItem('isVisuallyImpaired', 'false');
                        sessionStorage.setItem('hasAnswered', 'true');
                        speak('Alright, enjoy your workout!', () => {
                            toggleVoiceChat(false);
                        });
                        statusText.textContent = 'Voice chat stopped.';
                    } else {
                        speak('Please say "yes" or "no".');
                        statusText.textContent = 'Please say "yes" or "no".';
                    }
                } else if (isVisuallyImpaired) {
                    if (command.includes('progress')) {
                        const progressText = `Today's progress: ${pushupProgress} out of ${targetPushups} push-ups, ${situpProgress} out of ${targetSitups} sit-ups, ${squatjumpProgress} out of ${targetSquatjumps} squat jumps.`;
                        speak(progressText, () => {
                            speak('Say "progress" again for progress.', () => {
                                speak('Say "start workout" to begin.', () => {
                                    speak('Say "read stats" for live stats.', () => {
                                        speak('Say "view history" for history.', () => {
                                            speak('Or say "exit" to exit.');
                                        });
                                    });
                                });
                            });
                        });
                        statusText.textContent = 'Reading progress.';
                    } else if (command.includes('start workout')) {
                        if (!startWorkoutBtn.disabled) {
                            toggleWorkout(true);
                            speak('Workout started.', () => {
                                speak('Say "stop workout" to stop.', () => {
                                    speak('Say "read stats" for live stats.', () => {
                                        speak('Say "view history" for history.', () => {
                                            speak('Or say "exit" to exit.');
                                        });
                                    });
                                });
                            });
                            statusText.textContent = 'Workout started.';
                        } else {
                            speak('Workout is already in progress.');
                            statusText.textContent = 'Workout already in progress.';
                        }
                    } else if (command.includes('stop workout')) {
                        if (startWorkoutBtn.disabled) {
                            toggleWorkout(false);
                            speak('Workout stopped.', () => {
                                speak('Say "start workout" to begin again.', () => {
                                    speak('Say "read stats" for live stats.', () => {
                                        speak('Say "view history" for history.', () => {
                                            speak('Or say "exit" to exit.');
                                        });
                                    });
                                });
                            });
                            statusText.textContent = 'Workout stopped.';
                        } else {
                            speak('No workout is in progress.');
                            statusText.textContent = 'No workout in progress.';
                        }
                    } else if (command.includes('read stats')) {
                        const statsText = `Current workout: ${currentMovementType !== 'unknown' ? currentMovementType : 'no movement detected'}, push-ups ${movementData.pushups} reps, sit-ups ${movementData.situps} reps, squat jumps ${movementData.squatjumps} reps, intensity ${<?php echo $intensity; ?>} BPM.`;
                        speak(statsText, () => {
                            speak('Say "read stats" again for live stats.', () => {
                                speak('Say "stop workout" to stop.', () => {
                                    speak('Say "view history" for history.', () => {
                                        speak('Or say "exit" to exit.');
                                    });
                                });
                            });
                        });
                        statusText.textContent = 'Reading live stats.';
                    } else if (command.includes('view history')) {
                        const historyText = "Opening workout history. Here are your recent workouts.";
                        speak(historyText, () => {
                            document.querySelector('.card:last-child').scrollIntoView({ behavior: 'smooth' });
                            <?php if (!empty($history_logs)): ?>
                                const recentLog = <?php echo json_encode($history_logs[0]); ?>;
                                speak(`Most recent: ${recentLog.workout_date}, ${recentLog.movement_type}, ${recentLog.total_reps} reps.`, () => {
                                    speak('Scroll to view more history.', () => {
                                        speak('Say "view history" again, or "exit" to exit.');
                                    });
                                });
                            <?php else: ?>
                                speak('No workout history available.', () => {
                                    speak('Say "view history" again, or "exit" to exit.');
                                });
                            <?php endif; ?>
                        });
                        statusText.textContent = 'Viewing workout history.';
                    } else if (command.includes('exit')) {
                        speak('Exiting.', () => {
                            window.location.href = 'logout.php';
                        });
                        statusText.textContent = 'Exiting.';
                    } else {
                        speak('Command not recognized.', () => {
                            speak('Say "progress" for mission progress.', () => {
                                speak('Say "start workout" to begin.', () => {
                                    speak('Say "read stats" for live stats.', () => {
                                        speak('Say "view history" for history.', () => {
                                            speak('Or say "exit" to exit.');
                                        });
                                    });
                                });
                            });
                        });
                        statusText.textContent = 'Command not recognized.';
                    }
                }
            };

            recognition.onerror = function(event) {
                statusText.textContent = 'Voice recognition error: ' + event.error;
                if (isVoiceActive && retryCount < maxRetries) {
                    retryCount++;
                    setTimeout(() => {
                        try {
                            recognition.start();
                        } catch (error) {
                            statusText.textContent = 'Error restarting recognition.';
                            toggleVoiceChat(false);
                        }
                    }, 1000);
                } else if (isVoiceActive) {
                    statusText.textContent = 'Too many recognition errors. Stopping voice chat.';
                    toggleVoiceChat(false);
                }
            };

            recognition.onend = function() {
                if (isVoiceActive) {
                    try {
                        recognition.start();
                    } catch (error) {
                        if (retryCount < maxRetries) {
                            retryCount++;
                            setTimeout(() => {
                                try {
                                    recognition.start();
                                } catch (err) {
                                    statusText.textContent = 'Error restarting recognition.';
                                    toggleVoiceChat(false);
                                }
                            }, 1000);
                        } else {
                            statusText.textContent = 'Too many recognition errors. Stopping voice chat.';
                            toggleVoiceChat(false);
                        }
                    }
                }
            };
        } else {
            voiceBtn.disabled = true;
            statusText.textContent = 'Voice chat not supported in this browser.';
        }

        function toggleVoiceChat(state) {
            if (!recognition) return;

            isVoiceActive = state;
            retryCount = 0;
            if (isVoiceActive) {
                voiceBtn.textContent = 'Stop Voice Chat';
                voiceBtn.classList.remove('off');
                statusText.textContent = 'Voice chat is on. Listening...';
                try {
                    recognition.start();
                    if (!hasAnswered) {
                        speak('Are you visually impaired?', () => {
                            speak('Please say "yes" or "no".');
                        });
                    } else if (isVisuallyImpaired) {
                        speak('Voice navigation enabled.', () => {
                            speak('Say "progress" for mission progress.', () => {
                                speak('Say "start workout" to begin tracking.', () => {
                                    speak('Say "read stats" for live stats.', () => {
                                        speak('Say "view history" for workout history.', () => {
                                            speak('Or say "exit" to exit.');
                                        });
                                    });
                                });
                            });
                        });
                    } else {
                        speak('Alright, enjoy your workout!', () => {
                            toggleVoiceChat(false);
                        });
                    }
                } catch (error) {
                    statusText.textContent = 'Error starting voice chat.';
                    toggleVoiceChat(false);
                }
            } else {
                voiceBtn.textContent = 'Start Voice Chat';
                voiceBtn.classList.add('off');
                statusText.textContent = 'Voice chat is off';
                recognition.stop();
                window.speechSynthesis.cancel();
            }
        }

        voiceBtn.addEventListener('click', () => {
            toggleVoiceChat(!isVoiceActive);
        });
    </script>
</body>
</html>