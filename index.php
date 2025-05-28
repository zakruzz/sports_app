<?php
session_start();
require_once 'config/db.php';

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Sports App</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460, #1a1a2e);
            background-size: 400%;
            animation: gradientAnimation 15s ease infinite;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 0;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .card {
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 100;
            overflow: hidden;
            text-align: center;
        }
        .welcome-text {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .sports-image {
            width: 100%;
            max-width: 200px;
            margin-bottom: 2rem;
        }
        .gradient-btn {
            background: linear-gradient(45deg, #00ddeb, #ff007a);
            transition: transform 0.3s ease;
            padding: 0.85rem;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 200px;
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
        .fallback-nav {
            margin-top: 1rem;
            display: none;
        }
        .fallback-btn {
            background: linear-gradient(45deg, #6b7280, #4b5563);
            margin: 0.5rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .fallback-btn:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="dumbell.gif" alt="Sports Icon" class="sports-image mx-auto">
        <h2 class="welcome-text text-4xl font-bold mb-8">Welcome to Sports Hub</h2>
        <p class="text-gray-600 mb-6">Join the ultimate sports community! Track your favorite games, join leagues, and stay updated with the latest scores.</p>
        <a href="login.php" class="gradient-btn">Get Started</a>
        <button id="voiceControl" class="voice-btn">Turn On Voice Control</button>
        <p id="status" class="status-text">Voice control is off</p>
        <div id="fallbackNav" class="fallback-nav">
            <p class="text-gray-600 mb-2">Voice control unavailable. Use buttons below:</p>
            <a href="login.php" class="fallback-btn">Login</a>
            <a href="register.php" class="fallback-btn">Register</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        // GSAP animations
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, ease: "power3.out" });

        // Voice recognition setup
        const voiceBtn = document.getElementById('voiceControl');
        const statusText = document.getElementById('status');
        const fallbackNav = document.getElementById('fallbackNav');
        let recognition;
        let isVoiceActive = false;

        // Check if Web Speech API is available
        if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
            recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.onresult = function(event) {
                const command = event.results[0][0].transcript.toLowerCase();
                statusText.textContent = `Heard: "${command}"`;
                
                if (command.includes('login')) {
                    window.location.href = 'login.php';
                } else if (command.includes('register')) {
                    window.location.href = 'register.php';
                } else {
                    statusText.textContent = 'Command not recognized. Say "Login" or "Register".';
                }
            };

            recognition.onerror = function(event) {
                statusText.textContent = 'Error occurred in recognition: ' + event.error;
                toggleVoiceControl(false);
            };

            recognition.onend = function() {
                if (isVoiceActive) {
                    recognition.start(); // Restart recognition if still active
                }
            };
        } else {
            voiceBtn.disabled = true;
            statusText.textContent = 'Speech recognition not supported in this browser.';
            fallbackNav.style.display = 'block'; // Show fallback navigation
        }

        function toggleVoiceControl(state) {
            if (!recognition) return;

            isVoiceActive = state;
            if (isVoiceActive) {
                voiceBtn.textContent = 'Turn Off Voice Control';
                voiceBtn.classList.remove('off');
                statusText.textContent = 'Listening for commands...';
                try {
                    recognition.start();
                } catch (error) {
                    statusText.textContent = 'Error starting recognition: ' + error.message;
                    toggleVoiceControl(false);
                }
            } else {
                voiceBtn.textContent = 'Turn On Voice Control';
                voiceBtn.classList.add('off');
                statusText.textContent = 'Voice control is off';
                recognition.stop();
            }
        }

        voiceBtn.addEventListener('click', () => {
            toggleVoiceControl(!isVoiceActive);
        });
    </script>
</body>
</html>