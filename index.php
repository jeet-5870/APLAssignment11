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

    // Updated Users table for Assignment 12
    $db->exec("CREATE TABLE IF NOT EXISTS Users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        full_name TEXT,
        contact_number TEXT,
        education TEXT,
        role TEXT,
        state TEXT,
        city TEXT,
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

    // UPDATE ACCOUNT DETAILS
    if ($action === 'update_profile' && isset($_SESSION['user_id'])) {
        try {
            $stmt = $db->prepare("UPDATE Users SET full_name=?, contact_number=?, education=?, role=?, state=?, city=? WHERE id=?");
            $stmt->execute([
                $_POST['full_name'], $_POST['contact_number'], $_POST['education'], 
                $_POST['role'], $_POST['state'], $_POST['city'], $_SESSION['user_id']
            ]);
            $message = "Details updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Failed to update profile.";
            $messageType = "error";
        }
    }

    // PASSWORD UPDATE
    if ($action === 'update_password' && isset($_SESSION['user_id'])) {
        $new_pw = $_POST['new_password'];
        if (strlen($new_pw) < 6) {
            $message = "Password must be at least 6 characters.";
            $messageType = "error";
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE Users SET password=? WHERE id=?");
            $stmt->execute([$hash, $_SESSION['user_id']]);
            $message = "Password updated successfully!";
            $messageType = "success";
        }
    }

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch user data for My Account
$userData = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT * FROM Users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
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

<nav class="fixed top-0 w-full bg-blue-900 text-white z-50 shadow-xl">
    <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
        <span class="font-black text-xl tracking-tighter">ICA2S 2026</span>

        <button onclick="document.getElementById('m-menu').classList.toggle('hidden')" class="md:hidden">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>

        <div class="hidden md:flex items-center space-x-2 lg:space-x-4 text-[11px] lg:text-[13px] font-bold uppercase">
            <?php foreach ($sections as $s): ?>
                <a href="#<?= str_replace(' ', '-', $s['section_name']) ?>" class="hover:text-yellow-400 transition">
                    <?= htmlspecialchars($s['section_name']) ?>
                </a>
            <?php endforeach; ?>

            <?php if ($isLoggedIn): ?>
                <div class="flex items-center space-x-2 ml-4 border-l border-blue-700 pl-4">
                    <a href="#my-account" class="text-yellow-400 hover:text-white transition text-[11px] font-bold">My Account</a>
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

    <div id="m-menu" class="hidden md:hidden bg-blue-800 flex flex-col p-4 space-y-2 uppercase text-xs font-bold">
        <?php foreach ($sections as $s): ?>
            <a href="#<?= str_replace(' ', '-', $s['section_name']) ?>" onclick="document.getElementById('m-menu').classList.add('hidden')">
                <?= htmlspecialchars($s['section_name']) ?>
            </a>
        <?php endforeach; ?>
        <hr class="border-blue-600">
        <?php if ($isLoggedIn): ?>
            <a href="#my-account" class="text-yellow-300" onclick="document.getElementById('m-menu').classList.add('hidden')">My Account</a>
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

<?php if (!$isLoggedIn): ?>
<div id="auth-modal" class="fixed inset-0 z-[100] hidden modal-overlay bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden relative">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl font-bold z-10">&times;</button>
        <div class="bg-blue-900 text-white px-8 pt-8 pb-4">
            <h2 class="text-2xl font-black">ICA2S 2026 Account</h2>
            <p class="text-blue-300 text-sm mt-1">Access exclusive conference features</p>
        </div>
        <div class="flex border-b border-gray-200 bg-gray-50">
            <button id="tab-login" onclick="switchTab('login')" class="tab-btn active flex-1 py-3 text-sm font-bold uppercase tracking-wide">Login</button>
            <button id="tab-register" onclick="switchTab('register')" class="tab-btn flex-1 py-3 text-sm font-bold uppercase tracking-wide text-gray-500">Register</button>
        </div>
        <div class="p-8">
            <div id="form-login" class="tab-content">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="login">
                    <input type="email" name="email" placeholder="you@example.com" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="password" name="password" placeholder="••••••••" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg transition text-sm tracking-wide uppercase">Login</button>
                </form>
            </div>
            <div id="form-register" class="tab-content hidden">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="register">
                    <input type="text" name="username" placeholder="johndoe" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm">
                    <input type="email" name="email" placeholder="you@example.com" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm">
                    <input type="password" name="password" placeholder="•••••••• (min 6)" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm">
                    <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-blue-900 font-bold py-2.5 rounded-lg transition text-sm tracking-wide uppercase">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($message): ?>
<div id="flash-banner" class="fixed top-16 left-0 right-0 z-40 flex justify-center pt-3 px-4">
    <div class="bg-green-600 text-white px-6 py-3 rounded-xl shadow-lg text-sm font-semibold flex items-center gap-3">
        ✅ <?= htmlspecialchars($message) ?>
        <button onclick="document.getElementById('flash-banner').remove()" class="ml-2 text-green-200 hover:text-white">&times;</button>
    </div>
</div>
<?php endif; ?>

<main class="pt-20 px-6 max-w-5xl mx-auto">

    <?php if ($isLoggedIn): ?>
    <section id="my-account" class="py-16 border-b border-gray-200">
        <h2 class="text-4xl font-extrabold text-blue-900 mb-8 border-l-8 border-yellow-500 pl-6 uppercase">My Account</h2>
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
                <h3 class="text-xl font-bold mb-4 text-blue-700">User Details</h3>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="update_profile">
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Full Name</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Contact Number</label>
                        <input type="text" name="contact_number" value="<?= htmlspecialchars($userData['contact_number'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Education</label>
                        <input type="text" name="education" value="<?= htmlspecialchars($userData['education'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Role</label>
                        <select name="role" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="Student" <?= ($userData['role'] ?? '') == 'Student' ? 'selected' : '' ?>>Student</option>
                            <option value="Faculty" <?= ($userData['role'] ?? '') == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase">State</label>
                            <input type="text" name="state" value="<?= htmlspecialchars($userData['state'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase">City</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($userData['city'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-900 text-white font-bold py-2 rounded-lg mt-2 uppercase text-xs">Update Details</button>
                </form>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 h-fit">
                <h3 class="text-xl font-bold mb-4 text-blue-700">Update Password</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_password">
                    <input type="password" name="new_password" placeholder="New Password" required class="w-full border rounded-lg px-4 py-2 text-sm">
                    <button type="submit" class="w-full bg-yellow-500 text-blue-900 font-bold py-2 rounded-lg uppercase text-xs">Change Password</button>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
