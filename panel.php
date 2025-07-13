<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; // Veritabanı bağlantısı için

// Giriş kontrolü
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['citizenid'])) {
    header("location: login.php");
    exit;
}

// Oyuncu bilgilerini çek (Normal kullanıcılar için panel içeriği)
$citizenid = isset($_SESSION['citizenid']) ? $_SESSION['citizenid'] : '';
$player_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Bilinmeyen Oyuncu';

// Eğer citizenid hala boşsa (çok nadir bir durum, oturum hatası olabilir), tekrar giriş sayfasına yönlendir
if (empty($citizenid)) {
    session_destroy();
    echo "<p class='text-red-500 text-center'>Oturum bilgileriniz eksik. Lütfen tekrar giriş yapın.</p>";
    echo "<meta http-equiv='refresh' content='3;url=login.php'>";
    exit;
}

// SQL sorgusunu güncelleyerek phone_phones tablosundan telefon numarasını ve Discord ID'yi çekiyoruz
// players tablosundan charinfo, money ve discord_id bilgilerini de çekiyoruz.
$sql = "SELECT p.name, p.charinfo, p.money, ph.phone_number, p.discord_id
        FROM players p
        LEFT JOIN phone_phones ph ON p.citizenid = ph.owner_id
        WHERE p.citizenid = '$citizenid'";
$result = $conn->query($sql);

$player = $result->fetch_assoc();

// Eğer oyuncu bulunamazsa veya veriler eksikse hata yönetimi
if (!$player) {
    session_destroy();
    echo "<p class='text-red-500 text-center'>Karakter bilgileriniz bulunamadı veya bir hata oluştu. Lütfen tekrar giriş yapın.</p>";
    echo "<meta http-equiv='refresh' content='3;url=login.php'>";
    exit;
}

$charinfo = json_decode($player['charinfo'], true);
$money = json_decode($player['money'], true);
$phone_number = $player['phone_number'] ? $player['phone_number'] : 'Bulunamadı';
$discord_id_display = "Bilgi Yok";
if (isset($player['discord_id']) && !empty($player['discord_id'])) {
    $discord_id_display = htmlspecialchars($player['discord_id']);
}
$steam_id_display = "Bilgi Yok"; // 'p.identifier' kaldırıldığı için varsayılan olarak "Bilgi Yok" yaptık

// charinfo'nun doğru parse edildiğinden emin ol
if (!is_array($charinfo)) {
    $charinfo = [
        'firstname' => 'Bilinmiyor',
        'lastname' => 'Bilinmiyor',
        'birthdate' => 'Bilinmiyor',
        'gender' => 'Bilinmiyor',
    ];
}

// money'nin doğru parse edildiğinden emin ol
if (!is_array($money)) {
    $money = [
        'cash' => 0,
        'bank' => 0,
    ];
}

// Yeni: Oyuncunun araçlarını çekiyoruz
$vehicles = [];
$sql_vehicles = "SELECT vehicle, plate, garage, state, mods
                 FROM player_vehicles
                 WHERE citizenid = '$citizenid'";
$result_vehicles = $conn->query($sql_vehicles);
if ($result_vehicles && $result_vehicles->num_rows > 0) {
    while ($row = $result_vehicles->fetch_assoc()) {
        $vehicles[] = $row;
    }
}

// Araç model adlarını ve markalarını daha okunur hale getirmek için eşleme
// FiveM'in docs sitesindeki verilere göre bu mapping'i genişletmelisin.
// Sadece örnek birkaç tane ekledim.
$vehicle_display_names = [
    'zentorno' => ['name' => 'Pegassi Zentorno', 'brand' => 'Pegassi'],
    'tola6' => ['name' => 'Mercedes-Benz AMG G63', 'brand' => 'Mercedes-Benz'],
    'f620' => ['name' => 'Fathom F620', 'brand' => 'Fathom'],
    'champion' => ['name' => 'Pegassi Champion', 'brand' => 'Pegassi'],
    'sultan' => ['name' => 'Karin Sultan', 'brand' => 'Karin'],
    'zion' => ['name' => 'Dewbauchee Zion', 'brand' => 'Dewbauchee'],
    'panto' => ['name' => 'Benefactor Panto', 'brand' => 'Benefactor'],
    'asbo' => ['name' => 'Weeny Issi Sport', 'brand' => 'Weeny'], // Örnek olarak ekledim
    // Bu liste FiveM'in resmi araç adlarını içermeli.
    // FiveM Docs'tan alabilirsin: https://docs.fivem.net/docs/game-references/vehicles/
];

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rave RolePlay | User Control Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="panel.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex">

    <button class="mobile-menu-button" id="mobile-menu-button">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="dropdown" id="profile-dropdown">
        <img src="https://via.placeholder.com/55/1a1a2e/e0e0f0?text=DC" alt="Discord Avatar" class="profile-avatar" id="discord-profile-avatar">
        <div class="dropdown-content">
            <a href="#" id="open-change-password-modal-dropdown"><i class="fas fa-key mr-2"></i> PROFİL AYARLARI</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> ÇIKIŞ YAP</a>
        </div>
    </div>

    <aside class="sidebar" id="main-sidebar">
        <div class="text-center mb-6">
            <img src="https://files.fivemerr.com/images/6bd0b5d6-b103-4315-804b-3a31d549349e.png" alt="Sunucu Logosu" class="mx-auto mb-3">
            <h1>RAVE RP</h1>
        </div>

        <nav class="mb-6">
            <ul>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item active" data-target="home-section">
                        <i class="fas fa-home mr-3"></i> ANA SAYFA
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item donatemarket" data-target="donate-section">
                        <i class="fas fa-shopping-cart mr-3"></i> DONATE MARKET
                    </a>
                </li>
            </ul>
        </nav>

        <h2 class="text-xl font-bold mb-4">KARAKTERİNİZ</h2>
        <nav class="mb-6">
            <ul>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" data-target="character-info-section"> <i class="fas fa-user mr-3"></i> KARAKTER BİLGİLERİ
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" data-target="vehicles-section"> <i class="fas fa-car mr-3"></i> ARAÇLARIM
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" data-target="support-tickets-section">
                        <i class="fas fa-headset mr-3"></i> DESTEK TALEPLERİ
                    </a>
                </li>
            </ul>
        </nav>

        <h2 class="text-xl font-bold mb-4">BAŞVURULAR</h2>
        <nav class="mb-6">
            <ul>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" id="open-lspd-application-modal">
                        <i class="fas fa-shield-halved mr-3"></i> LSPD BAŞVURUSU
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" id="open-lss-application-modal">
                        <i class="fas fa-hospital-user mr-3"></i> LSSD BAŞVURUSU
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" id="open-lsbb-application-modal">
                        <i class="fas fa-building mr-3"></i> LSBB BAŞVURUSU
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" id="open-lsh-application-modal">
                        <i class="fas fa-truck-medical mr-3"></i> HASTANE BAŞVURUSU
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" id="open-bcso-application-modal">
                        <i class="fas fa-user-sheriff mr-3"></i> BCSO BAŞVURUSU
                    </a>
                </li>
            </ul>
        </nav>
        <h2 class="text-xl font-bold mb-4">SUNUCUMUZ</h2>
        <nav class="mb-6">
            <ul>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" data-target="server-rules-section">
                        <i class="fas fa-gavel mr-3"></i> SUNUCU KURALLARI
                    </a>
                </li>
                <li class="mb-2">
                    <a href="#" class="sidebar-nav-item" data-target="ingame-rules-section">
                        <i class="fas fa-scroll mr-3"></i> OYUN İÇİ KURALLAR
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <section id="home-section" class="content-section active">
            <h2 class="text-2xl font-bold mb-6">SUNUCU İSTATİSTİKLERİ</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card p-6">
                    <h3 class="text-xl font-semibold mb-2">AKTİF OYUNCU</h3>
                    <p class="text-5xl font-bold" id="aktif-oyuncu">Yükleniyor...</p>
                </div>
                <div class="card p-6">
                    <h3 class="text-xl font-semibold mb-2">KAYITLI OYUNCU</h3>
                    <p class="text-5xl font-bold" id="kayitli-oyuncu">Yükleniyor...</p>
                </div>
                <div class="card p-6">
                    <h3 class="text-xl font-semibold mb-2">DİSCORD AKTİF</h3>
                    <p class="text-5xl font-bold" id="discord-uye">Yükleniyor...</p>
                </div>
                <div class="card p-6">
                    <h3 class="text-xl font-semibold mb-2">DİSCORD TOPLAM</h3>
                    <p class="text-5xl font-bold" id="discord-total-members">Yükleniyor...</p>
                </div>
            </div>

            <h2 class="text-2xl font-bold mb-6">SUNUCUMUZ HAKKINDA</h2>
            <div class="card p-6 mx-auto" style="max-width: 900px;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <img src="https://via.placeholder.com/400x200/2D3748/A0AEC0?text=FiveM+Resim+1" alt="Sunucu Resmi 1" class="rounded-md w-full h-auto">
                    <img src="https://via.placeholder.com/400x200/2D3748/A0AEC0?text=FiveM+Resim+2" alt="Sunucu Resmi 2" class="rounded-md w-full h-auto">
                </div>
                <p class="text-left w-full text-gray-300">
                    Rave RolePlay sunucusuna hoş geldiniz! Biz, oyuncularına benzersiz ve sürükleyici bir roleplay deneyimi sunmayı amaçlayan dinamik bir FiveM topluluğuyuz. Sunucumuz, gerçekçilik ve eğlenceyi bir araya getirerek, her türlü roleplay senaryosuna olanak tanıyan geniş bir harita ve zengin içerik sunar. İster bir suç örgütünün lideri olun, ister kanun uygulayıcı bir polis memuru, ister şehrin sakin bir vatandaşı... Herkes için bir yer var! Topluluğumuzun temelinde saygı, yaratıcılık ve karşılıklı anlayış yatar. Sunucumuzda geçirdiğiniz her anın unutulmaz olması için sürekli çalışıyor, yeni özellikler ve etkinliklerle deneyiminizi zenginleştiriyoruz. Bize katılın ve Los Santos'ta kendi hikayenizi yazın!
                    <br><br>
                    Amacımız, her oyuncunun keyif alacağı, kurallara saygılı ve adil bir ortam sağlamaktır. Yönetim ekibimiz her zaman sizinle iletişimde kalmaya ve sorunlarınıza çözüm bulmaya hazırız. Dinamik ekonomi sistemi, gelişmiş meslekler, özelleştirilebilir araçlar ve evler gibi birçok özellikle dolu sunucumuzda kendinizi gerçek bir şehrin parçası gibi hissedeceksiniz. Yeni başlayanlar için rehberler ve deneyimli oyuncular için derinlemesine RP fırsatları sunuyoruz. Discord sunucumuza katılarak topluluğumuzla tanışabilir, güncel duyurularımızdan haberdar olabilir ve anlık destek alabilirsiniz.
                </p>
            </div>
        </section>

        <section id="character-info-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">PROFİL BİLGİLERİ</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8 mx-auto" style="max-width: 900px;">
                <div class="card p-6">
                    <h3 class="text-xl font-semibold mb-4">KARAKTER DETAYLARI</h3>
                    <p class="text-left w-full"><strong>İsim:</strong> <?php echo htmlspecialchars($charinfo['firstname']); ?></p>
                    <p class="text-left w-full"><strong>Soyisim:</strong> <?php echo htmlspecialchars($charinfo['lastname']); ?></p>
                    <p class="text-left w-full"><strong>Doğum Tarihi:</strong> <?php echo htmlspecialchars($charinfo['birthdate']); ?></p>
                    <p class="text-left w-full"><strong>Cinsiyet:</strong> <?php echo $charinfo['gender'] == 0 ? 'Erkek' : 'Kadın'; ?></p>
                    <p class="text-left w-full"><strong>Telefon:</strong> <?php echo htmlspecialchars($phone_number); ?></p>
                    <p class="text-left w-full"><strong>Discord ID:</strong> <?php echo $discord_id_display; ?></p>
                </div>
                <div class="card p-6">
                    <h3 class="text-xl font-semibold mb-4">HESAP BAKİYESİ</h3>
                    <p class="text-left w-full"><strong>Nakit:</strong> $<?php echo number_format($money['cash']); ?></p>
                    <p class="text-left w-full"><strong>Banka:</strong> $<?php echo number_format($money['bank']); ?></p>
                </div>
            </div>
        </section>

        <section id="vehicles-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">ARAÇLARIM</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8 mx-auto" style="max-width: 900px;">
                <?php if (!empty($vehicles)): ?>
                    <?php foreach ($vehicles as $vehicle):
                        $vehicle_model_lower = strtolower($vehicle['vehicle']);
                        
                        // Kendi sunucundaki yerel resim yolu ve URL'si
                        $local_image_path_absolute = __DIR__ . '/../pa-vehicleshop/html/files/' . $vehicle_model_lower . '.png'; 
                        $local_image_url_relative = '../pa-vehicleshop/html/files/' . $vehicle_model_lower . '.png'; 

                        // FiveM'in kendi CDN'i (cfx.re) üzerindeki resim yolu.
                        // FiveM varsayılan araçları için en güvenilir kaynaklardan biri budur.
                        $fivem_cdn_image_url = 'https://cfx.re/images/vehicles/' . $vehicle_model_lower . '.png';

                        // Varsayılan placeholder URL'si (eğer hiçbiri bulunamazsa)
                        $default_placeholder_url = 'https://via.placeholder.com/150/1a1a2e/e0e0f0?text=RES%20YOK'; // "RES YOK" yazsın
                        
                        $final_image_src = '';

                        // Mantık: Önce model adı "tol" ile başlıyorsa yerelden çek
                        if (strpos($vehicle_model_lower, 'tol') === 0) {
                            if (file_exists($local_image_path_absolute) && is_file($local_image_path_absolute)) {
                                $final_image_src = $local_image_url_relative;
                            } else {
                                // "tol" aracı yerelde yoksa, doğrudan placeholder kullan
                                $final_image_src = $default_placeholder_url;
                                // Hata ayıklama için sunucu loguna düşebiliriz
                                // error_log("TOL aracı yerelde bulunamadı: " . $vehicle_model_lower . " - " . $local_image_path_absolute);
                            }
                        } 
                        // Diğer araçlar için FiveM'in resmi CDN'inden çekmeye çalış
                        else {
                            $final_image_src = $fivem_cdn_image_url;
                            // Eğer FiveM CDN'den de yüklenemezse onerror devreye girecek.
                        }
                        
                        // Resmin gerçekten gösterilecek adı ve markası
                        $vehicle_info = $vehicle_display_names[$vehicle_model_lower] ?? ['name' => ucfirst($vehicle['vehicle']), 'brand' => 'Bilinmiyor'];
                        
                        // Konum durumu kontrolü ve ataması
                        $location_status = '';
                        if (isset($vehicle['state']) && $vehicle['state'] == 0 && !empty($vehicle['garage'])) {
                            $location_status = 'Garajda: ' . htmlspecialchars($vehicle['garage']);
                        } else {
                            $location_status = 'Dışarıda';
                        }
                    ?>
                        <div class="card p-4">
                            <img src="<?= htmlspecialchars($final_image_src) ?>" 
                                 alt="<?= htmlspecialchars($vehicle_info['name'] ?? $vehicle['vehicle']) ?>" 
                                 class="w-full h-32 object-cover mb-3 rounded" 
                                 onerror="this.onerror=null;this.src='<?= htmlspecialchars($default_placeholder_url) ?>'; console.error('Araç resmi yüklenemedi: <?= htmlspecialchars($vehicle_model_lower) ?>. Denenen Kaynak: <?= htmlspecialchars($final_image_src) ?>');">
                            <h4 class="text-lg font-semibold text-white mb-2"><?= htmlspecialchars($vehicle_info['name'] ?? ucfirst($vehicle['vehicle'])) ?></h4>
                            <p class="text-gray-400">Marka: <strong><?= htmlspecialchars($vehicle_info['brand'] ?? 'Bilinmiyor') ?></strong></p>
                            <p class="text-gray-400">Plaka: <strong><?= htmlspecialchars($vehicle['plate']) ?></strong></p>
                            <p class="text-gray-400">Konum: <strong><?= htmlspecialchars($location_status) ?></strong></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full card p-4">
                        <p class="text-gray-400">Henüz sahip olduğunuz bir araç bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="support-tickets-section" class="content-section">
            <div class="ticket-section-header">
                <h2 class="text-2xl font-bold mb-0">DESTEK TALEPLERİM</h2>
                <button id="create-ticket-section-button">
                    <i class="fas fa-plus-circle"></i> YENİ TALEP OLUŞTUR
                </button>
            </div>
            <div id="tickets-list-grid" class="ticket-grid mt-6">
                </div>
        </section>

        <section id="ticket-detail-section" class="content-section">
            <button id="back-to-tickets-button" class="back-to-tickets-btn"><i class="fas fa-arrow-left"></i> TÜM TALEPLERE DÖN</button>
            <div class="ticket-header">
                <h2 id="detail-ticket-title-section"></h2>
                <p>Durum: <strong id="detail-ticket-status-section"></strong></p>
            </div>

            <div id="ticket-messages-container-section" class="messages-container">
                </div>

            <div class="message-input-form">
                <form id="send-ticket-message-form-section">
                    <textarea id="ticket-reply-message-section" placeholder="Mesajınızı buraya yazın..." required></textarea>
                    <button type="submit" id="send-ticket-message-button-section">MESAJ GÖNDER</button>
                    <p id="send-ticket-message-status-section" class="mt-4 text-center"></p>
                </form>
            </div>
        </section>

        <section id="change-password-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">ŞİFRE DEĞİŞTİR</h2>
            <div class="card p-6 mx-auto" style="max-width: 600px;">
                <p class="text-gray-400 mb-4">Şifrenizi güvenliğiniz için periyodik olarak değiştirmeniz önerilir.</p>
                <p class="text-400">Şifre değiştirme formuna sağ üstteki profil resminize tıklayıp "PROFİL AYARLARI" seçeneğinden ulaşabilirsiniz.</p>
            </div>
        </section>

        <section id="server-rules-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">SUNUCU KURALLARI</h2>
            <div class="card p-6 mx-auto" style="max-width: 900px;">
                <h3 class="text-xl font-semibold mb-4">GENEL SUNUCU KURALLARI</h3>
                <ul class="list-disc list-inside text-gray-300 space-y-2 text-md text-left">
                    <li>**Saygı ve Hoşgörü:** Sunucumuzdaki tüm oyunculara ve yönetim ekibine karşı saygılı ve hoşgörülü olmak zorunludur. Irkçılık, ayrımcılık, küfür ve argo kelimeler kesinlikle yasaktır.</li>
                    <li>**Meta Gaming (MG) ve Power Gaming (PG):** Roleplay'i bozacak Meta Gaming (RP dışı bilgi kullanma) ve Power Gaming (karakterinin yapamayacağı, aşırı güç kullanımı) davranışlarından kaçının. Karakteriniz gerçek bir kişi gibi hareket etmelidir.</li>
                    <li>**OOC (Out Of Character) Konuşmalar:** Oyun içinde OOC konuşmalar yapmak yasaktır. OOC iletişim sadece Discord sunucumuzda veya belirtilen OOC kanallarında yapılmalıdır.</li>
                    <li>**Karakter Gelişimi:** Karakterinizin bir hikayesi ve kişiliği olmalıdır. Karakterinizin yaşadığı olaylar RP içinde kalmalı, ani ve mantıksız karakter değişikliklerinden kaçınılmalıdır.</li>
                    <li>**Yönetim Kararları:** Yönetim ekibinin aldığı kararlara uymak ve itirazları saygı çerçevesinde iletmek zorunludur. Haksız olduğunu düşündüğünüz durumlarda destek talebi açabilirsiniz.</li>
                    <li>**Hile ve Bug Kullanımı:** Oyun içi hile (mod, script vb.) veya bug kullanmak kesinlikle yasaktır ve kalıcı ban sebebidir. Tespit edilen her durum yönetim ekibine bildirilmelidir.</li>
                    <li>**Ekonomi Kuralları:** Sunucu ekonomisini bozmaya yönelik her türlü aktivite yasaktır (örneğin, sürekli aynı işi yaparak aşırı para kasma, bug ile para çoğaltma).</li>
                    <li>**Fair Play:** Her zaman adil ve dürüst oynamaya özen gösterin. Diğer oyuncuların RP deneyimini baltalayacak davranışlardan kaçının.</li>
                    <li>**Sesli İletişim:** Mikrofonunuzu aktif olarak kullanmanız gerekmektedir. Mikrofonunuz kapalıyken RP yapmak, veya kulaklığınızdan ses geldiğinde bildirim gelmiyorsa bu kurallara dikkat ediniz.</li>
                    <li>**Stream Sniping:** Yayıncıları izleyerek oyun içi avantaj sağlamaya çalışmak yasaktır.</li>
                </ul>
            </div>
        </section>

        <section id="ingame-rules-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">OYUN İÇİ KURALLAR</h2>
            <div class="card p-6 mx-auto" style="max-width: 900px;">
                <p class="text-gray-400 mb-4 text-left">Aşağıdaki kurallar, farklı meslekler ve gruplar için oyun içi davranışları düzenlemektedir. Lütfen karakterinize uygun olan kuralları dikkatlice okuyunuz.</p>
                <div class="space-y-3 w-full">
                    <div class="collapsible-header" data-target="gang-rules-content">
                        <i class="fas fa-users-line mr-3"></i> ÇETE KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="gang-rules-content">
                        <li>Çete Savaşları belirlenen bölgelerde ve saatlerde yapılmalıdır.</li>
                        <li>Rakip çetelere karşı RP'yi bozacak davranışlardan kaçının.</li>
                        <li>Çatışmalarda sayı üstünlüğü sağlamamaya özen gösterin (ör: 4v4 kuralı).</li>
                        <li>Rehin alma ve fidye olaylarında kurallara uyun.</li>
                        <li>OOC küfürleşme ve toxiclik yasaktır.</li>
                    </ul>

                    <div class="collapsible-header" data-target="family-rules-content">
                        <i class="fas fa-people-arrows mr-3"></i> AİLE KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="family-rules-content">
                        <li>Ailenin itibarına ve düzenine zarar verecek davranışlardan kaçının.</li>
                        <li>Diğer ailelerle ilişkilerde diplomatik olun.</li>
                        <li>Aile içi rolü gerçekçi tutun.</li>
                        <li>Aile içi çatışmalar RP dahilinde ve mantık çerçevesinde olmalıdır.</li>
                    </ul>

                    <div class="collapsible-header" data-target="mafia-rules-content">
                        <i class="fas fa-user-secret mr-3"></i> MAFYA KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="mafia-rules-content">
                        <li>Organize suç işlerinde dikkatli ve planlı olun.</li>
                        <li>Sivil halka karşı aşırı şiddetten kaçının.</li>
                        <li>Diğer suç örgütleriyle rekabeti RP içinde tutun.</li>
                        <li>İş anlaşmalarında dürüst ve RP'ye uygun davranın.</li>
                    </ul>

                    <div class="collapsible-header" data-target="sivil-rules-content">
                        <i class="fas fa-person mr-3"></i> SİVİL KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="sivil-rules-content">
                        <li>Hayatınızın değerini bilin, kolayca ölümü kabul etmeyin (Fear RP).</li>
                        <li>Her olaya karışmayın, sivillerin kendi sınırları vardır.</li>
                        <li>RP ortamını bozacak, gerçek hayata dair konuşmalardan kaçının.</li>
                        <li>Sivil mesleklerde gerçekçiliği koruyun.</li>
                    </ul>

                    <div class="collapsible-header" data-target="lspd-rules-content">
                        <i class="fas fa-handcuffs mr-3"></i> LSPD KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="lspd-rules-content">
                        <li>Görevinizi kötüye kullanmayın.</li>
                        <li>Kanunları ve yönetmelikleri uygulayın.</li>
                        <li>Sivil halka karşı adil ve ölçülü olun.</li>
                        <li>Yetkilerinizi aşmayın.</li>
                        <li>Suçlulara karşı orantılı güç kullanın.</li>
                    </ul>

                    <div class="collapsible-header" data-target="lssd-rules-content">
                        <i class="fas fa-person-military-rifle mr-3"></i> LSSD KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="lssd-rules-content">
                        <li>Cezaevi güvenliğini sağlayın.</li>
                        <li>Mahkumlarla etkileşimde kurallara uyun.</li>
                        <li>Ekipler arası işbirliğini koruyun.</li>
                        <li>Firar girişimlerinde protokollere uyun.</li>
                    </ul>

                    <div class="collapsible-header" data-target="ems-rules-content">
                        <i class="fas fa-truck-medical mr-3"></i> EMS KURALLARI <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="ems-rules-content">
                        <li>Her zaman tarafsız ve profesyonel olun.</li>
                        <li>Hayat kurtarma önceliğinizdir.</li>
                        <li>Olay yerinde güvenliği sağlayın.</li>
                        <li>Tedavide hızlı ve doğru kararlar alın.</li>
                        <li>Mesleki gizliliğe riayet edin.</li>
                    </ul>

                    <div class="collapsible-header" data-target="coming-soon-rules-content">
                        <i class="fas fa-hourglass-half mr-3"></i> YAKINDA EKLENECEKLER <i class="fas fa-chevron-down ml-auto"></i>
                    </div>
                    <ul class="collapsible-content" id="coming-soon-rules-content">
                        <li>Yeni meslekler ve gruplar için kurallar çok yakında eklenecektir.</li>
                        <li>Örn: Tamirci Kuralları</li>
                        <li>Örn: Avukat Kuralları</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="lspd-application-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">LSPD BAŞVURUSU</h2>
            <div class="card p-6 text-center mx-auto" style="max-width: 600px;">
                <p class="text-gray-400 mb-4">LSPD başvurusu için sol menüden "LSPD BAŞVURUSU" seçeneğine tıklayın.</p>
            </div>
        </section>
        <section id="lss-application-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">LSSD BAŞVURUSU</h2>
            <div class="card p-6 text-center mx-auto" style="max-width: 600px;">
                <p class="text-gray-400 mb-4">LSSD başvurusu için sol menüden "LSSD BAŞVURUSU" seçeneğine tıklayın.</p>
            </div>
        </section>
        <section id="lsbb-application-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">LSBB BAŞVURUSU</h2>
            <div class="card p-6 text-center mx-auto" style="max-width: 600px;">
                <p class="text-gray-400 mb-4">LSBB başvurusu için sol menüden "LSBB BAŞVURUSU" seçeneğine tıklayın.</p>
            </div>
        </section>
        <section id="lsh-application-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">LOS SANTOS HASTANESİ BAŞVURUSU</h2>
            <div class="card p-6 text-center mx-auto" style="max-width: 600px;">
                <p class="text-gray-400 mb-4">Los Santos Hastanesi başvurusu için sol menüden "HASTANE BAŞVURUSU" seçeneğine tıklayın.</p>
            </div>
        </section>
        <section id="bcso-application-section" class="content-section">
            <h2 class="text-2xl font-bold mb-6">BLAINE COUNTY SHERIFFS OFFICE BAŞVURUSU</h2>
            <div class="card p-6 text-center mx-auto" style="max-width: 600px;">
                <p class="text-gray-400 mb-4">Blaine County Sheriffs Office başvurusu için sol menüden "BCSO BAŞVURUSU" seçeneğine tıklayın.</p>
            </div>
        </section>

    </main>

    <div id="lspd-application-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal="lspd-application-modal">&times;</span>
            <h2>LSPD BAŞVURUSU</h2>
            <form id="lspd-application-form">
                <div class="mb-4">
                    <label for="lspd-app-discord-id">Discord ID'niz</label>
                    <input type="text" id="lspd-app-discord-id" required placeholder="Örn: 123456789012345678">
                </div>
                <div class="mb-4">
                    <label for="lspd-app-character-name">Oyun İçi Karakter Adı</label>
                    <input type="text" id="lspd-app-character-name" required placeholder="Örn: John Doe">
                </div>
                <div class="mb-6">
                    <label for="lspd-app-text">Neden LSPD'ye Katılmak İstiyorsunuz?</label>
                    <textarea id="lspd-app-text" rows="8" required placeholder="Kendinizi ve LSPD'ye katılım beklentilerinizi anlatın..."></textarea>
                </div>
                <button type="submit">BAŞVURUYU GÖNDER</button>
            </form>
            <p id="lspd-application-status" class="mt-4 text-center"></p>
        </div>
    </div>

    <div id="lss-application-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal="lss-application-modal">&times;</span>
            <h2>LSSD BAŞVURUSU</h2>
            <form id="lss-application-form">
                <div class="mb-4">
                    <label for="lss-app-discord-id">Discord ID'niz</label>
                    <input type="text" id="lss-app-discord-id" required placeholder="Örn: 123456789012345678">
                </div>
                <div class="mb-4">
                    <label for="lss-app-character-name">Oyun İçi Karakter Adı</label>
                    <input type="text" id="lss-app-character-name" required placeholder="Örn: John Doe">
                </div>
                <div class="mb-6">
                    <label for="lss-app-text">Neden LSSD'e Katılmak İstiyorsunuz?</label>
                    <textarea id="lss-app-text" rows="8" required placeholder="Kendinizi ve LSSD'e katılım beklentilerinizi anlatın..."></textarea>
                </div>
                <button type="submit">BAŞVURUYU GÖNDER</button>
            </form>
            <p id="lss-application-status" class="mt-4 text-center"></p>
        </div>
    </div>

    <div id="lsbb-application-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal="lsbb-application-modal">&times;</span>
            <h2>LSBB BAŞVURUSU</h2>
            <form id="lsbb-application-form">
                <div class="mb-4">
                    <label for="lsbb-app-discord-id">Discord ID'niz</label>
                    <input type="text" id="lsbb-app-discord-id" required placeholder="Örn: 123456789012345678">
                </div>
                <div class="mb-4">
                    <label for="lsbb-app-character-name">Oyun İçi Karakter Adı</label>
                    <input type="text" id="lsbb-app-character-name" required placeholder="Örn: John Doe">
                </div>
                <div class="mb-6">
                    <label for="lsbb-app-text">Neden LSBB'ye Katılmak İstiyorsunuz?</label>
                    <textarea id="lsbb-app-text" rows="8" required placeholder="Kendinizi ve LSBB'ye katılım beklentilerinizi anlatın..."></textarea>
                </div>
                <button type="submit">BAŞVURUYU GÖNDER</button>
            </form>
            <p id="lsbb-application-status" class="mt-4 text-center"></p>
        </div>
    </div>

    <div id="lsh-application-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal="lsh-application-modal">&times;</span>
            <h2>LOS SANTOS HASTANESİ BAŞVURUSU</h2>
            <form id="lsh-application-form">
                <div class="mb-4">
                    <label for="lsh-app-discord-id">Discord ID'niz</label>
                    <input type="text" id="lsh-app-discord-id" required placeholder="Örn: 123456789012345678">
                </div>
                <div class="mb-4">
                    <label for="lsh-app-character-name">Oyun İçi Karakter Adı</label>
                    <input type="text" id="lsh-app-character-name" required placeholder="Örn: John Doe">
                </div>
                <div class="mb-6">
                    <label for="lsh-app-text">Neden Los Santos Hastanesi'ne Katılmak İstiyorsunuz?</label>
                    <textarea id="lsh-app-text" rows="8" required placeholder="Kendinizi ve LSH'ye katılım beklentilerinizi anlatın..."></textarea>
                </div>
                <button type="submit">BAŞVURUYU GÖNDER</button>
            </form>
            <p id="lsh-application-status" class="mt-4 text-center"></p>
        </div>
    </div>

    <div id="bcso-application-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal="bcso-application-modal">&times;</span>
            <h2>BLAINE COUNTY SHERIFFS OFFICE BAŞVURUSU</h2>
            <form id="bcso-application-form">
                <div class="mb-4">
                    <label for="bcso-app-discord-id">Discord ID'niz</label>
                    <input type="text" id="bcso-app-discord-id" required placeholder="Örn: 123456789012345678">
                </div>
                <div class="mb-4">
                    <label for="bcso-app-character-name">Oyun İçi Karakter Adı</label>
                    <input type="text" id="bcso-app-character-name" required placeholder="Örn: John Doe">
                </div>
                <div class="mb-6">
                    <label for="bcso-app-text">Neden BCSO'ya Katılmak İstiyorsunuz?</label>
                    <textarea id="bcso-app-text" rows="8" required placeholder="Kendinizi ve BCSO'ya katılım beklentilerinizi anlatın..."></textarea>
                </div>
                <button type="submit">BAŞVURUYU GÖNDER</button>
            </form>
            <p id="bcso-application-status" class="mt-4 text-center"></p>
        </div>
    </div>


    <div id="create-ticket-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal="create-ticket-modal">&times;</span>
            <h2>DESTEK TALEBİ OLUŞTUR</h2>
            <form id="create-ticket-form">
                <div class="mb-4">
                    <label for="ticket-title">BAŞLIK</label>
                    <input type="text" id="ticket-title" required>
                </div>
                <div class="mb-4">
                    <label for="ticket-message">MESAJINIZ</label>
                    <textarea id="ticket-message" rows="5" required></textarea>
                </div>
                <button type="submit">TALEP OLUŞTUR</button>
            </form>
            <p id="create-ticket-status" class="mt-4 text-center"></p>
        </div>
    </div>


    <input type="hidden" id="hidden-citizenid" value="<?php echo htmlspecialchars($citizenid); ?>">
    <input type="hidden" id="hidden-playername" value="<?php echo htmlspecialchars($player_name); ?>">

    <script src="panel.js"></script>
</body>
</html>