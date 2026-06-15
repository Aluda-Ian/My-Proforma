<?php
session_start();

// Protect this page! Only logged-in admins can create new users.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require '../src/db.php';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];

    // Securely hash the password before saving to the database
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (:username, :password)");
        $stmt->execute(['username' => $new_username, 'password' => $hashed_password]);
        $message = "Success! The user '{$new_username}' has been created.";
    } catch (PDOException $e) {
        // If the username column is unique, this catches duplicates
        $error = "Error: That username might already exist.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin User | My Proforma</title>
    <link href="../public/css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">

    <!-- Top Navigation -->
    <nav class="bg-[#002056] text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="font-bold text-xl tracking-wide flex items-center gap-2">
                    <span class="bg-[#DA251C] text-white px-2 py-0.5 rounded text-xs uppercase">Admin</span>
                    My Proforma
                </div>
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white text-sm font-bold transition">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 border-t-8 border-t-[#DA251C]">
            <h2 class="text-2xl font-bold mb-2 text-[#002056]">Create New Admin</h2>
            <p class="text-gray-500 text-sm mb-6 pb-4 border-b border-gray-200">Generate access for other organizing committee members.</p>

            <!-- Success / Error Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm font-bold">
                    ✅ <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm font-bold">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-[#002056] text-sm font-bold mb-2">New Username</label>
                    <input type="text" name="new_username" required class="w-full border border-gray-300 p-3 rounded-lg bg-gray-50 focus:ring-2 focus:ring-[#002056] focus:outline-none">
                </div>
                <div class="mb-6">
                    <label class="block text-[#002056] text-sm font-bold mb-2">Assign Password</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full border border-gray-300 p-3 rounded-lg bg-gray-50 focus:ring-2 focus:ring-[#002056] focus:outline-none">
                </div>
                <button type="submit" class="w-full bg-[#002056] hover:bg-[#001333] text-white font-bold py-3 rounded-lg transition shadow-md">
                    Create User
                </button>
            </form>
        </div>

    </div>

</body>
</html>