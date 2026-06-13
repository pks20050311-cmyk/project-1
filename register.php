<?php
// ============================================================
//  register.php  –  Registration page + handler
// ============================================================
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db_connect.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // --- Validation ---
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be 3–50 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $db = get_db();

        // Check uniqueness
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $ins->execute([$username, $email, $hash]);
            $success = 'Account created! You can now <a href="login.php" class="underline font-semibold">sign in</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account – To Do List</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink:    #0d0d12;
    --paper:  #f5f3ee;
    --accent: #e8643a;
    --mid:    #c9c4ba;
  }
  body { font-family: 'DM Sans', sans-serif; background: var(--paper); color: var(--ink); }
  .syne { font-family: 'Syne', sans-serif; }
  .btn-primary {
    background: var(--ink);
    color: var(--paper);
    transition: background .2s, transform .15s;
  }
  .btn-primary:hover { background: var(--accent); transform: translateY(-1px); }
  .input-field {
    background: #fff;
    border: 1.5px solid var(--mid);
    transition: border-color .2s, box-shadow .2s;
  }
  .input-field:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(232,100,58,.15);
  }
  .noise {
    position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 180px;
  }
  .logo-dot { color: var(--accent); }
  .slide-up { animation: slideUp .5s cubic-bezier(.22,1,.36,1) both; }
  @keyframes slideUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:none; } }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">
<div class="noise"></div>

<!-- Decorative blob -->
<div class="fixed top-0 right-0 w-96 h-96 rounded-full opacity-10 pointer-events-none"
     style="background:radial-gradient(circle,#e8643a,transparent 70%); transform:translate(30%,-30%)"></div>

<div class="relative z-10 w-full max-w-md slide-up">

  <!-- Brand -->
  <div class="mb-8 text-center">
    <h1 class="syne text-4xl font-extrabold tracking-tight">To Do List<span class="logo-dot">.</span></h1>
    <p class="text-sm mt-1" style="color:#7a7568">Your intelligent task manager</p>
  </div>

  <!-- Card -->
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="syne text-2xl font-bold mb-1">Create your account</h2>
    <p class="text-sm mb-6" style="color:#7a7568">Join thousands getting things done</p>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-lg text-sm font-medium" style="background:#d1fae5;color:#065f46">
        <?= $success ?>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="mb-4 p-3 rounded-lg text-sm" style="background:#fee2e2;color:#991b1b">
        <ul class="list-disc list-inside space-y-0.5">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="space-y-4">

        <div>
          <label class="block text-sm font-medium mb-1" for="username">Username</label>
          <input id="username" name="username" type="text" required autocomplete="username"
                 placeholder="e.g. johndoe"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 class="input-field w-full rounded-lg px-4 py-3 text-sm">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 class="input-field w-full rounded-lg px-4 py-3 text-sm">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="password">Password</label>
          <div class="relative">
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   placeholder="Min. 8 characters"
                   class="input-field w-full rounded-lg px-4 py-3 pr-12 text-sm">
            <button type="button" onclick="togglePwd('password','eye1')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
              <svg id="eye1" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="confirm">Confirm Password</label>
          <div class="relative">
            <input id="confirm" name="confirm" type="password" required autocomplete="new-password"
                   placeholder="Repeat your password"
                   class="input-field w-full rounded-lg px-4 py-3 pr-12 text-sm">
            <button type="button" onclick="togglePwd('confirm','eye2')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
              <svg id="eye2" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit"
                class="btn-primary w-full py-3 rounded-lg font-semibold text-sm tracking-wide mt-2">
          Create Account
        </button>
      </div>
    </form>

    <p class="text-center text-sm mt-6" style="color:#7a7568">
      Already have an account?
      <a href="login.php" class="font-semibold hover:underline" style="color:var(--accent)">Sign in</a>
    </p>
  </div>
</div>

<script>
function togglePwd(fieldId, eyeId) {
  const f = document.getElementById(fieldId);
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
