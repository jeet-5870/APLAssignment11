<?php
// ============================================================
// Assignment 10 - ICA2S 2026 Conference Website with Auth
// Scholar Number: 24U022009
// Name: Jeet Narayan
// ============================================================

session_start();

$db_file = __DIR__ . '/ica2s.db';

// Initialize SQLite database
function getDB($db_file) {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables if they don't exist
    $db->exec("CREATE TABLE IF NOT EXISTS ConferenceSections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section_name TEXT NOT NULL,
        content TEXT NOT NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS Users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed conference sections if empty
    $count = $db->query("SELECT COUNT(*) FROM ConferenceSections")->fetchColumn();
    if ($count == 0) {
        $sections = [
            ['Home', 'Welcome to the 2026 International Conference on Advanced Systems (ICA2S). This premier event brings together researchers, scientists, and industry practitioners to explore the latest innovations in technology. Our mission is to foster collaboration across diverse engineering disciplines.'],
            ['Committee', '<h3 class="font-bold text-blue-700 mb-2">Steering Committee</h3><ul class="list-disc pl-5 mb-4"><li>Dr. Aris Thompson (Chair)</li><li>Dr. Sarah Jenkins (Co-Chair)</li></ul><h3 class="font-bold text-blue-700 mb-2">Technical Program Committee</h3><ul class="list-disc pl-5"><li>Prof. Michael Chen - MIT</li><li>Dr. Linda Ross - Stanford University</li><li>Dr. Kevin Patel - IIT Delhi</li></ul>'],
            ['Important Dates', '<div class="overflow-x-auto"><table class="w-full border text-left"><tr class="bg-gray-200"><th class="p-2 border">Event</th><th class="p-2 border">Date</th></tr><tr><td class="p-2 border">Paper Submission</td><td class="p-2 border">October 15, 2025</td></tr><tr><td class="p-2 border">Acceptance Notification</td><td class="p-2 border">November 20, 2025</td></tr><tr><td class="p-2 border">Camera Ready Paper</td><td class="p-2 border">December 01, 2025</td></tr><tr><td class="p-2 border">Conference Dates</td><td class="p-2 border">February 26–28, 2026</td></tr></table></div>'],
            ['Speakers', '<strong>Keynote Speaker 1:</strong> Dr. Elena Rodriguez – "The Future of Quantum Computing in AI".<br><br><strong>Keynote Speaker 2:</strong> Mr. Julian Vane – "Sustainable Infrastructure for Smart Cities".'],
            ['Workshop', 'Join our full-day workshop on "Cloud-Native Architectures" led by industry experts from Google and AWS. Participants will receive a certificate of completion and hands-on lab access.'],
            ['Submission', 'All papers must be original and not simultaneously submitted to another journal or conference. Submissions should be made through the EasyChair portal. Use the standard double-column IEEE template.'],
            ['Special Session', 'We are hosting a special track on "Cyber-Physical Systems in Healthcare." If you wish to lead a sub-session, please contact the secretariat with your proposal by November 1st.'],
            ['Registration', '<ul class="list-disc pl-5 mb-4"><li>Regular Author: $450</li><li>Student Author: $250</li><li>Attendee: $150</li></ul><p>Late registration after Jan 1st will incur a $100 surcharge.</p>'],
            ['Sponsorship', 'Elevate your brand by sponsoring ICA2S 2026. We offer Diamond, Gold, and Silver tiers. Benefits include logo placement on the website, dedicated exhibit booths, and speaking slots.'],
            ['Contact', 'For general inquiries: info@ica2s.vercel.app<br>For submission help: support@ica2s.vercel.app<br>Phone: +1 (555) 123-4567'],
        ];
        $stmt = $db->prepare("INSERT INTO ConferenceSections (section_name, content) VALUES (?, ?)");
        foreach ($sections as $s) {
            $stmt->execute($s);
        }
    }

    return $db;
}

$db = getDB($db_file);
$message = '';
$messageType = '';

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // REGISTER
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$username || !$email || !$password || !$confirm) {
            $message = 'All fields are required.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email address.';
            $messageType = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
            $messageType = 'error';
        } elseif ($password !== $confirm) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hash]);
                $message = 'Registration successful! You can now log in.';
                $messageType = 'success';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                    $message = 'Username or email already exists.';
                } else {
                    $message = 'Registration failed. Please try again.';
                }
                $messageType = 'error';
            }
        }
    }

    // LOGIN
    if ($action === 'login') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $message = 'Email and password are required.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['email']     = $user['email'];
                $message = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
                $messageType = 'success';
            } else {
                $message = 'Invalid email or password.';
                $messageType = 'error';
            }
        }
    }

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch sections
$sections = $db->query("SELECT section_name, content FROM ConferenceSections ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICA2S 2026 – 24U022009</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { scroll-behavior: smooth; }
        section { min-height: 80vh; display: flex; flex-direction: column; justify-content: center; }
        .modal-overlay { backdrop-filter: blur(4px); }
        .tab-btn.active { background: #1e3a8a; color: #fff; }
        .tab-btn { transition: background 0.2s, color 0.2s; }
    </style>
</head>
<body class="bg-slate-50 text-gray-900 leading-relaxed">

<!-- ===== NAVBAR ===== -->
<nav class="fixed top-0 w-full bg-blue-900 text-white z-50 shadow-xl">
    <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
        <span class="font-black text-xl tracking-tighter">ICA2S 2026</span>

        <!-- Mobile menu button -->
        <button onclick="document.getElementById('m-menu').classList.toggle('hidden')" class="md:hidden">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>

        <!-- Desktop nav links -->
        <div class="hidden md:flex items-center space-x-2 lg:space-x-4 text-[11px] lg:text-[13px] font-bold uppercase">
            <?php foreach ($sections as $s): ?>
                <a href="#<?= str_replace(' ', '-', $s['section_name']) ?>" class="hover:text-yellow-400 transition">
                    <?= htmlspecialchars($s['section_name']) ?>
                </a>
            <?php endforeach; ?>

            <!-- Auth button -->
            <?php if ($isLoggedIn): ?>
                <div class="flex items-center space-x-2 ml-4 border-l border-blue-700 pl-4">
                    <span class="text-yellow-300 text-[11px]">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded-full text-[11px] font-bold transition">
                            Logout
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <button onclick="openModal('login')" class="ml-4 bg-yellow-400 hover:bg-yellow-300 text-blue-900 px-4 py-1.5 rounded-full text-[11px] font-black transition">
                    Login / Register
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile dropdown menu -->
    <div id="m-menu" class="hidden md:hidden bg-blue-800 flex flex-col p-4 space-y-2 uppercase text-xs font-bold">
        <?php foreach ($sections as $s): ?>
            <a href="#<?= str_replace(' ', '-', $s['section_name']) ?>" onclick="document.getElementById('m-menu').classList.add('hidden')">
                <?= htmlspecialchars($s['section_name']) ?>
            </a>
        <?php endforeach; ?>
        <hr class="border-blue-600">
        <?php if ($isLoggedIn): ?>
            <span class="text-yellow-300">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <form method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded font-bold w-full text-left">Logout</button>
            </form>
        <?php else: ?>
            <button onclick="openModal('login')" class="bg-yellow-400 text-blue-900 px-3 py-1 rounded font-bold">Login / Register</button>
        <?php endif; ?>
    </div>
</nav>

<!-- ===== AUTH MODAL ===== -->
<?php if (!$isLoggedIn): ?>
<div id="auth-modal" class="fixed inset-0 z-[100] hidden modal-overlay bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden relative">
        <!-- Close button -->
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl font-bold z-10">&times;</button>

        <!-- Header -->
        <div class="bg-blue-900 text-white px-8 pt-8 pb-4">
            <h2 class="text-2xl font-black">ICA2S 2026 Account</h2>
            <p class="text-blue-300 text-sm mt-1">Access exclusive conference features</p>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 bg-gray-50">
            <button id="tab-login" onclick="switchTab('login')" class="tab-btn active flex-1 py-3 text-sm font-bold uppercase tracking-wide">Login</button>
            <button id="tab-register" onclick="switchTab('register')" class="tab-btn flex-1 py-3 text-sm font-bold uppercase tracking-wide text-gray-500">Register</button>
        </div>

        <!-- Flash message -->
        <?php if ($message): ?>
            <div class="mx-6 mt-4 px-4 py-3 rounded-lg text-sm font-medium
                <?= $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="p-8">
            <!-- LOGIN FORM -->
            <div id="form-login" class="tab-content">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg transition text-sm tracking-wide uppercase">
                        Login
                    </button>
                    <p class="text-center text-xs text-gray-500">
                        No account? <button type="button" onclick="switchTab('register')" class="text-blue-700 font-semibold hover:underline">Register here</button>
                    </p>
                </form>
            </div>

            <!-- REGISTER FORM -->
            <div id="form-register" class="tab-content hidden">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="register">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" placeholder="johndoe" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(min 6 chars)</span></label>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit"
                        class="w-full bg-yellow-400 hover:bg-yellow-300 text-blue-900 font-bold py-2.5 rounded-lg transition text-sm tracking-wide uppercase">
                        Create Account
                    </button>
                    <p class="text-center text-xs text-gray-500">
                        Already registered? <button type="button" onclick="switchTab('login')" class="text-blue-700 font-semibold hover:underline">Login here</button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== SUCCESS BANNER (logged-in) ===== -->
<?php if ($isLoggedIn && $message): ?>
<div id="flash-banner" class="fixed top-16 left-0 right-0 z-40 flex justify-center pt-3 px-4">
    <div class="bg-green-600 text-white px-6 py-3 rounded-xl shadow-lg text-sm font-semibold flex items-center gap-3">
        ✅ <?= htmlspecialchars($message) ?>
        <button onclick="document.getElementById('flash-banner').remove()" class="ml-2 text-green-200 hover:text-white">&times;</button>
    </div>
</div>
<?php endif; ?>

<!-- ===== MAIN CONTENT ===== -->
<main class="pt-20 px-6 max-w-5xl mx-auto">
    <?php foreach ($sections as $s):
        $id = str_replace(' ', '-', $s['section_name']);
    ?>
    <section id="<?= htmlspecialchars($id) ?>" class="py-16 border-b border-gray-200">
        <h2 class="text-4xl font-extrabold text-blue-900 mb-8 border-l-8 border-yellow-500 pl-6 uppercase">
            <?= htmlspecialchars($s['section_name']) ?>
        </h2>
        <div class="bg-white p-10 rounded-2xl shadow-xl text-xl text-gray-600 border border-gray-100">
            <?= $s['content'] ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>

<footer class="bg-blue-950 text-white py-12 text-center mt-20">
    <p class="opacity-70">&copy; Made by &mdash; 24U022009, Jeet Narayan</p>
</footer>

<script>
    // Modal controls
    function openModal(tab) {
        document.getElementById('auth-modal').classList.remove('hidden');
        document.getElementById('auth-modal').classList.add('flex');
        switchTab(tab || 'login');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('auth-modal').classList.add('hidden');
        document.getElementById('auth-modal').classList.remove('flex');
        document.body.style.overflow = '';
    }

    function switchTab(tab) {
        document.getElementById('form-login').classList.add('hidden');
        document.getElementById('form-register').classList.add('hidden');
        document.getElementById('tab-login').classList.remove('active');
        document.getElementById('tab-register').classList.remove('active');

        document.getElementById('form-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab).classList.add('active');
    }

    // Click outside to close
    document.getElementById('auth-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Auto-open modal if there's a message (form was submitted)
    <?php if ($message && !$isLoggedIn): ?>
        openModal('<?= ($messageType === 'success' && ($_POST['action'] ?? '') === 'register') ? 'login' : ($_POST['action'] ?? 'login') ?>');
    <?php endif; ?>

    // Auto-dismiss flash banner after 4s
    setTimeout(() => {
        const b = document.getElementById('flash-banner');
        if (b) b.remove();
    }, 4000);
</script>

</body>
</html>
