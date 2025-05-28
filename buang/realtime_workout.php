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

// Fetch the latest mission data for the user (using min_pushups, min_situps, min_squatjumps from users table)
$stmt = $pdo->prepare("SELECT min_pushups, min_situps, min_squatjumps FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$mission = $stmt->fetch();

// Calculate simulated intensity (BPM) based on mission input
$intensity = 0;
if ($mission) {
    $intensity = ($mission['min_pushups'] + $mission['min_situps'] + $mission['min_squatjumps']) * 2; // Simple formula for intensity
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realtime Workout - Sports Hub</title>
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
        #workoutIcon {
            transition: opacity 0.5s ease;
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
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Realtime Workout Tracker</h2>
            <div class="flex justify-center mb-6">
                <img id="workoutIcon" src="https://via.placeholder.com/150x100?text=Workout+Icon" alt="Workout Icon" class="w-32 h-24">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="text-left">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Live Workout Stats</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Movement Detection (Reps)</label>
                        <input type="number" id="movementInput" class="input-field" readonly placeholder="Detected by ESP32-S3">
                        <div class="progress-bar"><div class="progress-fill" id="movementProgress" style="width: 0%;"></div></div>
                    </div>
                    <p id="motivationMessage" class="text-center text-gray-600 mt-4 hidden">Keep going! You're crushing it!</p>
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
    </div>
    <script>
        // GSAP animations
        gsap.from(".navbar", { opacity: 0, y: -50, duration: 1, ease: "power3.out" });
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, ease: "power3.out" });

        // Chart.js setup
        const ctx = document.getElementById('workoutChart').getContext('2d');
        const workoutChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Movement Reps', data: [], borderColor: '#3b82f6', fill: false, tension: 0.4 },
                    { label: 'Workout Intensity', data: [], borderColor: '#ef4444', fill: false, tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                plugins: { tooltip: { enabled: true }, legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // MQTT Configuration
        const broker = 'test.mosquitto.org'; // Replace with your ESP32-S3 broker
        const port = 8081; // WebSocket port
        const clientId = 'web_client_' + Math.random().toString(16).substr(2, 8);
        const movementTopic = 'sports/movement';
        const movementTypeTopic = 'sports/movement_type';

        const client = new Paho.MQTT.Client(broker, port, clientId);
        let currentMovementType = 'unknown'; // Track the current movement type for saving

        client.onConnectionLost = function (response) {
            console.log('Connection lost: ' + response.errorMessage);
        };

        client.onMessageArrived = function (message) {
            const topic = message.destinationName;
            const payload = message.payloadString;

            if (topic === movementTopic) {
                const value = parseInt(payload);
                const movementInput = document.getElementById('movementInput');
                movementInput.value = value;
                const maxReps = 100;
                const percentage = Math.min((value / maxReps) * 100, 100);
                document.getElementById('movementProgress').style.width = `${percentage}%`;

                workoutChart.data.labels.push(new Date().toLocaleTimeString());
                workoutChart.data.datasets[0].data.push(value);
                if (workoutChart.data.labels.length > 10) {
                    workoutChart.data.labels.shift();
                    workoutChart.data.datasets[0].data.shift();
                }
                workoutChart.data.datasets[1].data.push(<?php echo $intensity; ?>);
                if (workoutChart.data.datasets[1].data.length > 10) {
                    workoutChart.data.datasets[1].data.shift();
                }
                workoutChart.update();
                checkMotivation(value, <?php echo $intensity; ?>);

                // Save to database via AJAX
                if (currentMovementType !== 'unknown') {
                    fetch('save_workout.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ movement_type: currentMovementType, reps: value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Failed to save workout data:', data.message);
                        }
                    })
                    .catch(error => console.error('Error saving workout data:', error));
                }
            } else if (topic === movementTypeTopic) {
                currentMovementType = payload;
                const workoutIcon = document.getElementById('workoutIcon');
                let newSrc;
                switch (payload) {
                    case 'pushups':
                        newSrc = 'https://via.placeholder.com/150x100?text=Push-up+Icon';
                        workoutIcon.alt = 'Push-up Icon';
                        break;
                    case 'situps':
                        newSrc = 'https://via.placeholder.com/150x100?text=Sit-up+Icon';
                        workoutIcon.alt = 'Sit-up Icon';
                        break;
                    case 'squatjumps':
                        newSrc = 'https://via.placeholder.com/150x100?text=Squat+Jump+Icon';
                        workoutIcon.alt = 'Squat Jump Icon';
                        break;
                    default:
                        newSrc = 'https://via.placeholder.com/150x100?text=Workout+Icon';
                        workoutIcon.alt = 'Workout Icon';
                        currentMovementType = 'unknown';
                }
                // Animate the icon change with GSAP
                gsap.to(workoutIcon, { opacity: 0, duration: 0.3, onComplete: () => {
                    workoutIcon.src = newSrc;
                    gsap.to(workoutIcon, { opacity: 1, duration: 0.3 });
                }});
            }
        };

        function checkMotivation(reps, intensity) {
            const motivationMessage = document.getElementById('motivationMessage');
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
            client.subscribe(movementTopic);
            client.subscribe(movementTypeTopic);
        }

        document.getElementById('startWorkoutBtn').addEventListener('click', function() {
            this.textContent = 'Workout In Progress...';
            this.classList.add('cursor-not-allowed', 'opacity-75');
            this.disabled = true;
            gsap.to(this, { scale: 1.1, duration: 0.3, yoyo: true, repeat: 1 });
        });
    </script>
</body>
</html>