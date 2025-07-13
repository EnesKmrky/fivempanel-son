<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// DELİKANLI KOD (Önce var mı diye bak, sonra ne diye bak)
if (!isset($_SESSION['loggedin']) || $_SESSION['webadmin'] != 1) {
    // Kapı dışarı et, login sayfasına postala
    header('location: ../login.php'); // Bir üst dizindeki login.php'ye yönlendir
    exit;
}
                                                                                               
include '../db.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FiveM Admin Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">
    <button class="mobile-menu-button" id="admin-mobile-menu-button">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="admin-sidebar-overlay"></div>

    <header class="bg-gray-800 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold text-teal-400">FiveM Admin Panel</h1>
            <nav class="flex items-center space-x-4">
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors duration-200">Çıkış Yap</a>
            </nav>
        </div>
    </header>

    <div class="flex flex-1">
        <aside class="sidebar" id="admin-main-sidebar">
            <nav>
                <ul>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item active" data-target="player-management-section">
                            <i class="fas fa-users-cog mr-3"></i> Oyuncu Yönetimi
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="whitelist-applications-section">
                            <i class="fas fa-user-check mr-3"></i> Whitelist Başvuruları
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full ml-auto" id="new-applications-badge-whitelist">0</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="lspd-applications-section">
                            <i class="fas fa-shield-halved mr-3"></i> LSPD Başvuruları
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full ml-auto" id="new-applications-badge-lspd">0</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="lss-applications-section">
                            <i class="fas fa-hospital-user mr-3"></i> LSS Başvuruları
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full ml-auto" id="new-applications-badge-lss">0</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="lsbb-applications-section">
                            <i class="fas fa-building mr-3"></i> LSBB Başvuruları
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full ml-auto" id="new-applications-badge-lsbb">0</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="lsh-applications-section">
                            <i class="fas fa-truck-medical mr-3"></i> LOS SANTOS HASTANESİ BAŞVURULARI
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full ml-auto" id="new-applications-badge-lsh">0</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="bcso-applications-section">
                            <i class="fas fa-thin fa-user-sheriff mr-3"></i> BLAINE COUNTY SHERIFFS OFFICE BAŞVURULARI
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full ml-auto" id="new-applications-badge-bcso">0</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="server-logs-section">
                            <i class="fas fa-clipboard-list mr-3"></i> Sunucu Logları
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="sidebar-nav-item" data-target="announcements-section">
                            <i class="fas fa-bullhorn mr-3"></i> Duyurular
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <section id="player-management-section" class="content-section active grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 card p-6 flex flex-col">
                <button id="refresh-data-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mb-4 transition-colors duration-200"><i class="fas fa-sync-alt mr-2"></i>Verileri Yenile</button>
                    <h2 class="text-2xl font-semibold text-teal-300 mb-4 flex justify-between items-center">
                        Aktif Oyuncular
                        <span class="bg-gray-700 text-gray-300 text-sm px-3 py-1 rounded-full" id="player-count">0</span>
                    </h2>
                    <div id="player-list" class="flex-grow overflow-y-auto pr-2" style="max-height: calc(100vh - 200px);">
                        <p class="text-gray-400 text-center py-4">Oyuncular yükleniyor...</p>
                    </div>
                </div>

                <div class="lg:col-span-2 card p-6">
                    <div id="welcome-message" class="text-center text-gray-400 py-10">
                        <h2 class="text-2xl font-semibold mb-3">Hoş Geldiniz!</h2>
                        <p>Sol taraftan bir oyuncu seçerek işlem yapmaya başlayın.</p>
                    </div>

                    <div id="action-panel" class="hidden">
                        <h2 class="text-2xl font-semibold text-teal-300 mb-4">
                            Seçili Oyuncu: <span id="selected-player-name" class="text-white"></span> (ID: <span id="selected-player-id" class="text-white"></span>)
                        </h2>

                        <div class="action-group">
                            <h3 class="text-xl font-semibold text-gray-300 mb-3">Bilgi Gönder</h3>
                            <div class="input-group">
                                <input type="text" id="info-message" placeholder="Gönderilecek mesaj..." class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <button id="send-info-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors duration-200">Gönder</button>
                            </div>
                        </div>

                        <div class="action-group">
                            <h3 class="text-xl font-semibold text-gray-300 mb-3">Para İşlemleri</h3>
                            <div class="mb-3">
                                <label class="inline-flex items-center mr-4">
                                    <input type="radio" name="money-type" value="cash" checked class="form-radio text-blue-600">
                                    <span class="ml-2 text-gray-300">Nakit</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="money-type" value="bank" class="form-radio text-blue-600">
                                    <span class="ml-2 text-gray-300">Banka</span>
                                </label>
                            </div>
                            <div class="input-group mb-3">
                                <input type="number" id="money-amount" placeholder="Miktar..." class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <button id="give-money-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition-colors duration-200">Para Ver</button>
                                <button id="remove-money-btn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition-colors duration-200">Para Sil</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="action-group col-span-full md:col-span-1">
                                <h3 class="text-xl font-semibold text-gray-300 mb-3">Genel Yönetim</h3>
                                <div class="space-y-2">
                                    <div class="input-group">
                                        <input type="text" id="kick-reason" placeholder="Atma nedeni..." class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600">
                                        <button id="kick-player-btn" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded transition-colors duration-200">Kickle</button>
                                    </div>
                                    <button id="revive-player-btn" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded transition-colors duration-200">Canlandır</button>
                                    <button id="ck-player-btn" class="w-full bg-red-800 hover:bg-red-900 text-white px-4 py-2 rounded transition-colors duration-200">CK At</button>
                                </div>
                            </div>

                            <div class="action-group col-span-full md:col-span-1">
                                <h3 class="text-xl font-semibold text-gray-300 mb-3">Oyuncuya Araç Ver</h3>
                                <div class="space-y-2">
                                    <label for="giveVehiclePlayerId" class="block text-gray-300 text-sm mb-1">Oyuncu ID (CitizenID):</label>
                                    <input type="text" class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" id="giveVehiclePlayerId" placeholder="Oyuncu CitizenID'si" readonly>
                                    <small class="text-gray-400 block mb-2">Yukarıdan seçtiğiniz oyuncunun CitizenID'si otomatik dolacaktır.</small>
                                    
                                    <label for="giveVehicleModel" class="block text-gray-300 text-sm mb-1">Araç Modeli:</label>
                                    <input type="text" class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" id="giveVehicleModel" placeholder="Araç Modeli (örn: 'adder', 'turismo2')">
                                    <small class="text-gray-400 block mb-2">`pa-vehicleshop/vehicles.lua` dosyasındaki model kodunu girin.</small>
                                    
                                    <button type="button" class="w-full bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded transition-colors duration-200" id="btnGiveVehicle">Aracı Oyuncuya Ver</button>
                                </div>
                            </div>
                            
                            <div class="action-group col-span-full md:col-span-1">
                                <h3 class="text-xl font-semibold text-gray-300 mb-3">Karakter İşlemleri</h3>
                                <div class="space-y-2">
                                    <button id="skin-menu-btn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded transition-colors duration-200">Skin Menü Ver</button>
                                    <button id="teleport-motel-btn" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded transition-colors duration-200">Motele Işınla</button>
                                </div>
                            </div>
                        </div>

                        <div class="action-group mt-6">
                            <h3 class="text-xl font-semibold text-gray-300 mb-3">Oyuncunun Araçları</h3>
                            <p class="text-gray-400 mb-3">Seçili oyuncuya ait araçlar burada listelenecektir.</p>
                            <div id="player-vehicles-list" class="space-y-2">
                                <p class="text-gray-500">Oyuncu seçildiğinde araçlar yüklenecek...</p>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <section id="whitelist-applications-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">Whitelist Başvuruları</h2>
                <div id="whitelist-applications-list" class="space-y-4">
                    <p class="text-gray-400">Başvurular yükleniyor...</p>
                </div>
            </section>

            <section id="lspd-applications-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">LSPD Başvuruları</h2>
                <div id="lspd-applications-list" class="space-y-4">
                    <p class="text-gray-400">Başvurular yükleniyor...</p>
                </div>
            </section>

            <section id="lss-applications-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">LSS Başvuruları</h2>
                <div id="lss-applications-list" class="space-y-4">
                    <p class="text-gray-400">Başvurular yükleniyor...</p>
                </div>
            </section>

            <section id="lsbb-applications-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">LSBB Başvuruları</h2>
                <div id="lsbb-applications-list" class="space-y-4">
                    <p class="text-gray-400">Başvurular yükleniyor...</p>
                </div>
            </section>

            <section id="lsh-applications-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">LOS SANTOS HASTANESİ BAŞVURULARI</h2>
                <div id="lsh-applications-list" class="space-y-4">
                    <p class="text-gray-400">Başvurular yükleniyor...</p>
                </div>
            </section>

            <section id="bcso-applications-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">BLAINE COUNTY SHERIFFS OFFICE BAŞVURULARI</h2>
                <div id="bcso-applications-list" class="space-y-4">
                    <p class="text-gray-400">Başvurular yükleniyor...</p>
                </div>
            </section>


            <section id="server-logs-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">Sunucu Logları</h2>
                <div class="card p-6">
                    <p class="text-gray-400">Sunucu logları bu bölümde görüntülenecek.</p>
                </div>
            </section>

            <section id="announcements-section" class="content-section hidden">
                <h2 class="text-2xl font-semibold text-teal-300 mb-4">Duyurular</h2>
                <div class="card p-6">
                    <p class="text-gray-400">Duyuruları yönetmek için arayüz bu bölümde olacak.</p>
                </div>
            </section>

        </main>
    </div>

    <div id="rejection-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden">
        <div class="rejection-modal-content">
            <h2 class="text-xl font-bold mb-4">Başvuruyu Reddet</h2>
            <div class="mb-4">
                <label class="block text-gray-300 mb-2" for="rejection-reason-text">Reddetme Sebebi:</label>
                <textarea id="rejection-reason-text" class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" rows="4" placeholder="Neden reddedildiğini yazın..."></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button id="cancel-rejection" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">İptal</button>
                <button id="confirm-rejection" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Reddet</button>
            </div>
        </div>
    </div>


<script>
    // Bu fonksiyon, bir oyuncu seçildiğinde 'Oyuncuya Araç Ver' formundaki CitizenID alanını doldurur.
    // Oyuncu listeleme JS kodunuzda bir oyuncu seçildiğinde (örn. bir satıra tıklandığında) bu fonksiyonu çağırın.
    function fillPlayerForVehicle(citizenId) {
        document.getElementById('giveVehiclePlayerId').value = citizenId;
        // İsteğe bağlı olarak seçilen oyuncunun diğer bilgilerini de güncelleyebilirsin
        document.getElementById('selectedPlayerInfo').innerText = 'Seçilen Oyuncu CitizenID: ' + citizenId;
        // Buradan diğer AJAX isteklerini tetikleyerek oyuncu parası, itemları vb. bilgileri çekebilirsin.
    }

    // YENİ: Oyuncu seçildiğinde araç listesini çekmek için bu fonksiyonu çağır
    function fetchPlayerVehicles(citizenId) {
        // script.js dosyasındaki Node.js API'si ile iletişim kuran fonksiyonu çağıracağız.
        // Bu kısmı script.js içinde yazacağız.
    }
</script>
    <script src="script.js"></script>
</body>
</html>