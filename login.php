<?php
// db.php dosyanı ve session'ı başlatmayı unutma
include 'db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $password = $_POST['password'];

    $sql = "SELECT citizenid, name, webpassword, webadmin FROM players WHERE name = '$name'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['webpassword'] && password_verify($password, $user['webpassword'])) {
            // Giriş başarılı
            $_SESSION['loggedin'] = true;
            $_SESSION['citizenid'] = $user['citizenid'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['webadmin'] = $user['webadmin'];

            // === YÖNLENDİRME KONTROLÜ BURADA ===
            if ($user['webadmin'] == 1) {
                // Admin ise
                header("location: public/admin.php");
            } else {
                // Normal kullanıcı ise
                header("location: panel.php");
            }
            exit;
        } else {
            $error = "Yanlış şifre veya bu karakter için panel hesabı oluşturulmamış.";
        }
    } else {
        $error = "Bu karakter adına sahip bir hesap bulunamadı.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen">
    <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-full max-w-sm">
        <h2 class="text-2xl font-bold text-white text-center mb-6">Giriş Yap</h2>
        <?php if($error): ?><p class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></p><?php endif; ?>
        <form method="post">
            <div class="mb-4">
                <label class="block text-gray-300 mb-2" for="name">Karakter Adı</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" type="text" name="name" required placeholder="Örn: John Doe">
            </div>
            <div class="mb-6">
                <label class="block text-gray-300 mb-2" for="password">Şifre</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" type="password" name="password" required>
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">Giriş Yap</button>
        </form>
         <p class="text-center text-gray-500 text-xs mt-4">
            Hesabın yok mu? <a href="register.php" class="text-blue-400 hover:underline">Kayıt Ol</a>
        </p>
    </div>
</body>
</html>