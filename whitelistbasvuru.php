<?php
include 'db.php';

$error = '';
$success = '';
$discord_id_val = '';
$character_name_val = ''; 
$application_text_val = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $discord_id = $conn->real_escape_string($_POST['discord_id']);
    $character_name = $conn->real_escape_string($_POST['character_name']);
    $application_text = $conn->real_escape_string($_POST['application_text']);

    $discord_id_val = $_POST['discord_id'];
    $character_name_val = $_POST['character_name'];
    $application_text_val = $_POST['application_text'];

    $check_sql = "SELECT id FROM whitelist_applications WHERE discord_id = '$discord_id'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $error = "Bu Discord ID ile zaten bir başvuru yapılmış. Lütfen başvurunuzun sonucunu bekleyin.";
    } else {
        $insert_sql = "INSERT INTO whitelist_applications (discord_id, character_name, application_text, status) VALUES (?, ?, ?, 'Beklemede')";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sss", $discord_id, $character_name, $application_text);

        if ($stmt->execute()) {
            $success = "Başvurunuz başarıyla alındı! En kısa sürede incelenecektir.";
            echo "<script type='text/javascript'>
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('[Application Debug] Başvuru başarıyla veritabanına kaydedildi. Şimdi Node.js\'e bildirim gönderiliyor...');
                    const data = {
                        discord_id: '" . $discord_id . "',
                        character_name: '" . $character_name . "',
                        application_text: '" . str_replace(["\r\n", "\n", "\r"], '\\n', $application_text) . "' // Yeni satırları JS uyumlu yap
                    };

                    const nodeJsApiUrl = 'http://localhost:3000/submit-whitelist-application'; // Endpoint adı güncellendi
                    console.log('[Application Debug] Node.js API URL:', nodeJsApiUrl);

                    fetch(nodeJsApiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data),
                    })
                    .then(response => {
                        console.log('[Application Debug] Node.js sunucusundan yanıt alındı. Durum Kodu:', response.status);
                        return response.json();
                    })
                    .then(result => {
                        console.log('[Application Debug] Node.js sunucusundan yanıt içeriği:', result);
                        if (!result.success) {
                            console.error('[Application Debug] Node.js sunucusuna bildirim gönderilirken hata:', result.message);
                        } else {
                            console.log('[Application Debug] Node.js sunucusuna bildirim başarıyla gönderildi.');
                        }
                    })
                    .catch(error => {
                        console.error('[Application Debug] Node.js sunucusuna bildirim gönderilirken BAĞLANTI HATASI veya İŞLEM HATASI:', error);
                        console.error('Muhtemel sebep: Node.js sunucusu çalışmıyor veya adres/port yanlış.');
                    });
                });
            </script>";
            $discord_id_val = '';
            $character_name_val = '';
            $application_text_val = '';
        } else {
            $error = "Başvuru sırasında bir hata oluştu: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whitelist Başvurusu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold text-white text-center mb-6">Whitelist Başvurusu</h2>
        <p class="text-gray-400 text-center mb-4">Sunucumuza katılmak için lütfen aşağıdaki formu doldurun.</p>

        <?php if($error): ?><p class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></p><?php endif; ?>
        <?php if($success): ?><p class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $success; ?></p><?php endif; ?>

        <form method="post">
            <div class="mb-4">
                <label class="block text-gray-300 mb-2" for="discord_id">Discord ID'niz</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" type="text" name="discord_id" required placeholder="Örn: 123456789012345678" value="<?php echo htmlspecialchars($discord_id_val); ?>">
                <p class="text-xs text-gray-500 mt-1">Discord ID'nizi Discord ayarlarından Geliştirici Modu'nu açarak kopyalayabilirsiniz.</p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 mb-2" for="character_name">Oyun İçi Karakter Adı (İstediğiniz)</label>
                <input class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" type="text" name="character_name" required placeholder="Örn: John Doe" value="<?php echo htmlspecialchars($character_name_val); ?>">
            </div>
            <div class="mb-6">
                <label class="block text-gray-300 mb-2" for="application_text">Neden Whitelist Almak İstiyorsunuz?</label>
                <textarea class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" name="application_text" rows="8" required placeholder="Kendinizi ve roleplay beklentilerinizi anlatın..."><?php echo htmlspecialchars($application_text_val); ?></textarea>
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">Başvuruyu Gönder</button>
        </form>
        <p class="text-center text-gray-500 text-xs mt-4">
            Zaten bir hesabın var mı? <a href="login.php" class="text-blue-400 hover:underline">Giriş Yap</a>
        </p>
    </div>
</body>
</html>