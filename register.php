<?php
session_start();
require_once 'config/db.php';

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to encrypt data using AES-256-CBC
function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(16); // Generate a random IV
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return ['encrypted' => base64_encode($encrypted), 'iv' => $iv];
}

// Function to decrypt data using AES-256-CBC
function decryptData($encryptedData, $key, $iv) {
    return openssl_decrypt(
        base64_decode($encryptedData),
        'AES-256-CBC',
        $key,
        0,
        $iv
    );
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['name'], $_POST['password'], $_POST['csrf_token'])) {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'CSRF token validation failed';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $name = trim(strip_tags($_POST['name']));
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT email, iv FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            $decryptedEmail = decryptData($u['email'], ENCRYPTION_KEY, $u['iv']);
            if ($decryptedEmail === $email) {
                $error = 'This email is already registered. Please use a different email or log in.';
                break;
            }
        }

        if (empty($error)) {
            // Encrypt only the email
            $encryptedEmail = encryptData($email, ENCRYPTION_KEY);

            try {
                // Insert user with encrypted email, plain name, and default level 1
                $stmt = $pdo->prepare("INSERT INTO users (email, name, password, iv, level) VALUES (?, ?, ?, ?, ?)");
                $success = $stmt->execute([
                    $encryptedEmail['encrypted'],
                    $name, // Store name as plain text
                    $password,
                    $encryptedEmail['iv'],
                    1 // Default level 1
                ]);
                if (!$success) {
                    $error = 'Insert failed: An unexpected error occurred';
                    throw new Exception($error);
                }

                // Fetch the user to store in session
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$encryptedEmail['encrypted']]);
                $user = $stmt->fetch();
                if ($user) {
                    // Decrypt email for session storage
                    $user['email'] = openssl_decrypt(
                        base64_decode($user['email']),
                        'AES-256-CBC',
                        ENCRYPTION_KEY,
                        0,
                        $user['iv']
                    );
                    // Name is already plain text
                    $_SESSION['user'] = $user;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Failed to fetch user after registration';
                    throw new Exception($error);
                }
            } catch (PDOException $e) {
                $error = 'Registration failed: An unexpected error occurred. Please try again later.';
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sports App</title>
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
        }
        .warning-card {
            background: #fff1f2;
            border-left: 4px solid #e11d48;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .warning-icon {
            color: #e11d48;
            font-size: 1.5rem;
        }
        .warning-text {
            color: #4b5563;
            font-size: 0.875rem;
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
    </style>
</head>
<body>
    <div class="card">
        <h2 class="text-4xl font-bold text-center text-gray-900 mb-8">Register</h2>
        <?php if ($error): ?>
            <div class="warning-card">
                <span class="warning-icon">⚠️</span>
                <p class="warning-text"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div>
                <label class="block text-gray-700 mb-2">Name</label>
                <input type="text" name="name" class="input-field" required placeholder="Enter your name">
            </div>
            <div>
                <label class="block text-gray-700 mb-2">Email</label>
                <input type="email" name="email" class="input-field" required placeholder="Enter your email">
            </div>
            <div>
                <label class="block text-gray-700 mb-2">Password</label>
                <input type="password" name="password" class="input-field" required placeholder="Enter your password">
            </div>
            <button type="submit" class="gradient-btn">Register</button>
        </form>
        <p class="mt-8 text-center text-gray-600">Already have an account? <a href="login.php" class="text-cyan-700 hover:underline">Login</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, ease: "power3.out" });
        gsap.from(".warning-card", { opacity: 0, x: -20, duration: 0.8, ease: "power3.out" });
    </script>
</body>
</html>