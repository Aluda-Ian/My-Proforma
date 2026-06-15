<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require '../src/db.php';
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | My Proforma</title>
    <link href="../public/css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">

    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full border-t-8 border-[#002056]">
        <div class="text-center mb-8">
            <span class="bg-[#DA251C] text-white px-3 py-1 rounded-full text-xs font-bold tracking-widest uppercase">Admin Panel</span>
            <h1 class="text-3xl font-bold mt-4 text-[#002056]">Welcome Back</h1>
            <p class="text-gray-500 mt-2">Sign in to manage the campaign.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-[#002056] text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" required class="w-full border border-gray-300 p-3 rounded-lg bg-gray-50 focus:ring-2 focus:ring-[#002056] focus:outline-none">
            </div>
            <div class="mb-6">
                <label class="block text-[#002056] text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-300 p-3 rounded-lg bg-gray-50 focus:ring-2 focus:ring-[#002056] focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-[#002056] hover:bg-[#001333] text-white font-bold py-3 rounded-lg transition shadow-md">
                Secure Login
            </button>
        </form>
    </div>

</body>
</html>