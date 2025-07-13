<?php
include 'db.php';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $webkey = isset($_POST['webkey']) ? $conn->real_escape_string($_POST['webkey']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $discord_id = isset($_POST['discord_id']) ? $conn->real_escape_string($_POST['discord_id']) : ''; // Yeni: Discord ID'yi alıyoruz

    if ($password !== $password_confirm) {
        $error = "Şifreler eşleşmiyor!";
    } else {
        // Webkey'in geçerli olup olmadığını ve daha önce şifre atanmadığını kontrol et
        // Ayrıca Discord ID'nin boş olup olmadığını da kontrol edebiliriz, ama input required olduğu için genelde dolu gelir.
        $sql = "SELECT citizenid FROM players WHERE webkey = '$webkey' AND webpassword IS NULL";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $player = $result->fetch_assoc();
            $citizenid = $player['citizenid'];

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Güncelleme sorgusuna discord_id kolonunu ekliyoruz
            $update_sql = "UPDATE players SET webpassword = '$hashed_password', discord_id = '$discord_id' WHERE citizenid = '$citizenid'";
            
            if ($conn->query($update_sql) === TRUE) {
                $success = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
            } else {
                $error = "Hata: " . $conn->error;
            }
        } else {
            $error = "Geçersiz veya daha önce kullanılmış bir web anahtarı girdiniz.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Rave Roleplay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen p-4">
    <div class="bg-gray-800 p-6 sm:p-8 rounded-lg shadow-lg w-full max-w-sm sm:max-w-md"> <h2 class="text-2xl sm:text-3xl font-bold text-white text-center mb-6">KAYIT OL</h2>
        <?php if($error): ?><p class="bg-red-600 text-white p-3 rounded mb-4 text-center text-sm"><?php echo $error; ?></p><?php endif; ?>
        <?php if($success): ?><p class="bg-green-600 text-white p-3 rounded mb-4 text-center text-sm"><?php echo $success; ?></p><?php endif; ?>
        <form method="post">
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-semibold mb-2" for="webkey">Web Anahtarı</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" type="text" name="webkey" required placeholder="Oyun içi /webkey yazarak al">
                <p class="text-xs text-gray-500 mt-1">Oyun içinde `/webkey` yazarak alabilirsin.</p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-semibold mb-2" for="discord_id">Discord ID</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" type="text" name="discord_id" required placeholder="Discord ID'ni buraya yapıştır">
                <p class="text-xs text-gray-500 mt-1">Discord ID'ni nasıl bulacağını bilmiyorsan, sunucumuzdaki rehbere bakabilirsin.</p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-semibold mb-2" for="password">Şifre</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" type="password" name="password" required placeholder="Şifre oluştur">
            </div>
            <div class="mb-6">
                <label class="block text-gray-300 text-sm font-semibold mb-2" for="password_confirm">Şifre Tekrar</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" type="password" name="password_confirm" required placeholder="Şifreyi tekrar gir">
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200" type="submit">KAYIT OL</button>
        </form>
        <p class="text-center text-gray-400 text-xs mt-4">
            Zaten bir hesabın var mı? <a href="login.php" class="text-blue-400 hover:underline">Giriş Yap</a>
        </p>
    </div>
</body>
</html>