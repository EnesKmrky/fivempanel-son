<?php 
include 'db.php'; 
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rave Roleplay - Anasayfa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Sadece FiveM'e özel renk tonlarını ve çok genel fontu burada tanımlayalım */
        body {
            font-family: 'Inter', sans-serif; /* Daha genel bir font */
        }
        .text-fivem-blue { color: #3b82f6; } /* Mavi tonları */
        .bg-fivem-dark { background-color: #1f2937; } /* Koyu gri */
        .text-fivem-accent { color: #10b981; } /* Yeşil aksan */

        /* Slider için basit animasyonlar */
        .slide {
            transition: opacity 1.5s ease-in-out;
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            top: 0;
            left: 0;
            background-size: cover;
            background-position: center;
        }
        .slide.active {
            opacity: 1;
        }
        .animate-fade-in-up {
            animation: fadeInUpDown 1s ease-out forwards;
        }
        .animate-scale-in {
            animation: scaleIn 0.8s ease-out forwards;
        }

        @keyframes fadeInUpDown {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-white flex flex-col min-h-screen">

    <nav class="bg-gray-800 p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center relative">
            <h1 class="text-xl md:text-2xl font-bold text-blue-500">RAVE RP</h1> <button class="md:hidden text-white text-2xl focus:outline-none" id="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>

            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="text-white hover:text-blue-400 px-3 py-2 rounded-md font-medium">Anasayfa</a>
                <a href="whitelistbasvuru.php" class="text-white hover:text-blue-400 px-3 py-2 rounded-md font-medium">Whitelist Başvurusu</a> <?php if(isset($_SESSION['loggedin'])): ?>
                    <a href="panel.php" class="text-white hover:text-blue-400 px-3 py-2 rounded-md font-medium">Panel</a>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md font-medium">Çıkış Yap</a>
                <?php else: ?>
                    <a href="login.php" class="text-white hover:text-blue-400 px-3 py-2 rounded-md font-medium">Giriş Yap</a>
                    <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md font-medium">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="md:hidden hidden bg-gray-700 w-full absolute left-0 top-full py-2 shadow-lg" id="mobile-menu">
            <a href="index.php" class="block text-white hover:bg-gray-600 px-4 py-2 font-medium">Anasayfa</a>
            <a href="whitelistbasvuru.php" class="block text-white hover:bg-gray-600 px-4 py-2 font-medium">Whitelist Başvurusu</a> <?php if(isset($_SESSION['loggedin'])): ?>
                <a href="panel.php" class="block text-white hover:bg-gray-600 px-4 py-2 font-medium">Panel</a>
                <a href="logout.php" class="block bg-red-600 hover:bg-red-700 text-white px-4 py-2 font-medium mt-2 mx-4 rounded-md">Çıkış Yap</a>
            <?php else: ?>
                <a href="login.php" class="block text-white hover:bg-gray-600 px-4 py-2 font-medium">Giriş Yap</a>
                <a href="register.php" class="block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 font-medium mt-2 mx-4 rounded-md">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="relative w-full h-96 md:h-screen-75 overflow-hidden"> <div class="slide active" style="background-image: url('https://via.placeholder.com/1920x1080/282c34/f8f8f2?text=FiveM+Slide+1');"></div>
        <div class="slide" style="background-image: url('https://via.placeholder.com/1920x1080/3a404a/f8f8f2?text=FiveM+Slide+2');"></div>
        <div class="slide" style="background-image: url('https://via.placeholder.com/1920x1080/4f5660/f8f8f2?text=FiveM+Slide+3');"></div>
        
        <div class="absolute inset-0 bg-black bg-opacity-70 flex flex-col items-center justify-center text-center p-4">
            <h2 class="text-3xl md:text-5xl lg:text-6xl font-bold text-white mb-4 animate-fade-in-up">
                RAVE ROLEPLAY SUNUCUSUNA HOŞ GELDİN!
            </h2>
            <p class="text-lg md:text-xl lg:text-2xl text-gray-300 mb-8 animate-fade-in-up" style="animation-delay: 0.2s;">
                Gerçekçi bir Los Santos deneyimi için seni bekliyoruz.
            </p>
            <a href="fivem://connect/oyuncu.sunucunuz.com:30120" class="bg-blue-600 hover:bg-blue-700 text-white text-lg md:text-xl font-bold py-3 px-8 rounded-full shadow-lg transition duration-300 transform hover:scale-105 animate-scale-in" style="animation-delay: 0.4s;">
                <i class="fas fa-play-circle mr-3"></i> SUNUCUYA GİR
            </a>
        </div>
    </section>

    <section class="py-16 bg-gray-900">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
                    <h3 class="text-2xl md:text-3xl font-bold text-blue-400 mb-6 text-center">SUNUCUMUZ HAKKINDA</h3>
                    <p class="text-gray-300 leading-relaxed text-justify">
                        Rave RolePlay sunucusuna hoş geldiniz! Biz, oyuncularına benzersiz ve sürükleyici bir roleplay deneyimi sunmayı amaçlayan dinamik bir FiveM topluluğuyuz. Sunucumuz, gerçekçilik ve eğlenceyi bir araya getirerek, her türlü roleplay senaryosuna olanak tanıyan geniş bir harita ve zengin içerik sunar. İster bir suç örgütünün lideri olun, ister kanun uygulayıcı bir polis memuru, ister şehrin sakin bir vatandaşı... Herkes için bir yer var! Topluluğumuzun temelinde saygı, yaratıcılık ve karşılıklı anlayış yatar. Sunucumuzda geçirdiğiniz her anın unutulmaz olması için sürekli çalışıyor, yeni özellikler ve etkinliklerle deneyiminizi zenginleştiriyoruz.
                    </p>
                    <p class="text-gray-300 leading-relaxed text-justify mt-4">
                        Amacımız, her oyuncunun keyif alacağı, kurallara saygılı ve adil bir ortam sağlamaktır. Yönetim ekibimiz her zaman sizinle iletişimde kalmaya ve sorunlarınıza çözüm bulmaya hazırdır. Dinamik ekonomi sistemi, gelişmiş meslekler, özelleştirilebilir araçlar ve evler gibi birçok özellikle dolu sunucumuzda kendinizi gerçek bir şehrin parçası gibi hissedeceksiniz. Yeni başlayanlar için rehberler ve deneyimli oyuncular için derinlemesine RP fırsatları sunuyoruz. Discord sunucumuza katılarak topluluğumuzla tanışabilir, güncel duyurularımızdan haberdar olabilir ve anlık destek alabilirsiniz.
                    </p>
                </div>

                <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
                    <h3 class="text-2xl md:text-3xl font-bold text-blue-400 mb-6 text-center">VİZYONUMUZ & MİSYONUMUZ</h3>
                    <div class="mb-8">
                        <h4 class="text-xl font-bold text-white mb-3 flex items-center"><i class="fas fa-eye mr-3 text-blue-400"></i> Vizyonumuz</h4>
                        <p class="text-gray-300 leading-relaxed text-justify">
                            FiveM camiasında sadece bir oyun sunucusu olmanın ötesine geçerek, oyuncuların kendilerini tam anlamıyla ifade edebildikleri, unutulmaz hikayeler yazabildikleri ve kalıcı dostluklar kurabildikleri bir yuva olmaktır. Her geçen gün yenilikçi yaklaşımlarla roleplay standartlarını yükselterek, tüm oyuncularımız için sürdürülebilir ve geliştirilebilir bir ekosistem yaratmayı hedefliyoruz.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-white mb-3 flex items-center"><i class="fas fa-lightbulb mr-3 text-blue-400"></i> Misyonumuz</h4>
                        <p class="text-gray-300 leading-relaxed text-justify">
                            Oyuncularımızın yaratıcılıklarını destekleyen, adil ve şeffaf bir yönetim anlayışıyla en iyi roleplay deneyimini sunmaktır. Topluluk geri bildirimlerini daima ön planda tutarak, sunucumuzu sürekli geliştirmek ve her bir oyuncunun sesini duyurabileceği bir platform sağlamak temel görevimizdir. Kaliteli, eğlenceli ve gerçekçi roleplay ortamı yaratmak için durmaksızın çalışıyoruz.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gray-800">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-bold text-blue-400 mb-12 text-center">GALERİ</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="overflow-hidden rounded-lg shadow-lg transform transition duration-300 hover:scale-105 hover:shadow-xl">
                    <img src="https://via.placeholder.com/600x400/3a404a/f8f8f2?text=Ekran+G%C3%B6r%C3%BCnt%C3%BCs%C3%BC+1" alt="Galeri Resmi 1" class="w-full h-auto object-cover">
                </div>
                <div class="overflow-hidden rounded-lg shadow-lg transform transition duration-300 hover:scale-105 hover:shadow-xl">
                    <img src="https://via.placeholder.com/600x400/4f5660/f8f8f2?text=Ekran+G%C3%B6r%C3%BCnt%C3%BCs%C3%BC+2" alt="Galeri Resmi 2" class="w-full h-auto object-cover">
                </div>
                <div class="overflow-hidden rounded-lg shadow-lg transform transition duration-300 hover:scale-105 hover:shadow-xl">
                    <img src="https://via.placeholder.com/600x400/282c34/f8f8f2?text=Ekran+G%C3%B6r%C3%BCnt%C3%BCs%C3%BC+3" alt="Galeri Resmi 3" class="w-full h-auto object-cover">
                </div>
                <div class="overflow-hidden rounded-lg shadow-lg transform transition duration-300 hover:scale-105 hover:shadow-xl">
                    <img src="https://via.placeholder.com/600x400/5a606a/f8f8f2?text=Ekran+G%C3%B6r%C3%BCnt%C3%BCs%C3%BC+4" alt="Galeri Resmi 4" class="w-full h-auto object-cover">
                </div>
                <div class="overflow-hidden rounded-lg shadow-lg transform transition duration-300 hover:scale-105 hover:shadow-xl">
                    <img src="https://via.placeholder.com/600x400/6f7680/f8f8f2?text=Ekran+G%C3%B6r%C3%BCnt%C3%BCs%C3%BC+5" alt="Galeri Resmi 5" class="w-full h-auto object-cover">
                </div>
                <div class="overflow-hidden rounded-lg shadow-lg transform transition duration-300 hover:scale-105 hover:shadow-xl">
                    <img src="https://via.placeholder.com/600x400/7a808a/f8f8f2?text=Ekran+G%C3%B6r%C3%BCnt%C3%BCs%C3%BC+6" alt="Galeri Resmi 6" class="w-full h-auto object-cover">
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gray-900">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-bold text-blue-400 mb-12 text-center">BİZE KATILIN!</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="bg-gray-800 p-8 rounded-lg shadow-lg flex flex-col items-center">
                    <h3 class="text-2xl font-bold text-white mb-4">DİSCORD SUNUCUMUZ</h3>
                    <p class="text-gray-400 mb-6 text-center">Topluluğumuza katılarak sohbet et, etkinliklere katıl ve güncel duyurulardan haberdar ol!</p>
                    <iframe src="https://discord.com/widget?id=758367214734016533&theme=dark" width="100%" height="300" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
                    <a href="https://discord.gg/senindiscordlinkin" target="_blank" class="mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-full transition duration-300">
                        <i class="fab fa-discord mr-3"></i> Discord'a Katıl
                    </a>
                </div>

                <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">SON DUYURULAR</h3>
                    <div class="space-y-6">
                        <div class="p-4 bg-gray-700 rounded-md shadow-md">
                            <h4 class="text-xl font-bold text-blue-300 mb-2">Sezon 2 Başlangıcı!</h4>
                            <p class="text-gray-300 text-sm">29 Haziran 2025</p>
                            <p class="text-gray-400 mt-2">Yeni harita güncellemeleri, meslekler ve etkinliklerle dolu Sezon 2 resmi olarak başladı! Detaylar Discord sunucumuzda.</p>
                        </div>
                        <div class="p-4 bg-gray-700 rounded-md shadow-md">
                            <h4 class="text-xl font-bold text-blue-300 mb-2">Whitelist Başvuruları Açıldı!</h4>
                            <p class="text-gray-300 text-sm">25 Haziran 2025</p>
                            <p class="text-gray-400 mt-2">Sunucumuza katılmak isteyen tüm yeni oyuncularımız için whitelist başvuruları panelimiz üzerinden açılmıştır.</p>
                        </div>
                        <div class="p-4 bg-gray-700 rounded-md shadow-md">
                            <h4 class="text-xl font-bold text-blue-300 mb-2">Büyük Yaz Etkinliği Duyurusu!</h4>
                            <p class="text-gray-300 text-sm">20 Haziran 2025</p>
                            <p class="text-gray-400 mt-2">Yaklaşan Yaz Etkinliğimiz ile ilgili tüm detaylar ve ödüller çok yakında duyurulacak! Takipte kalın.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 py-8 mt-auto shadow-inner">
        <div class="container mx-auto px-6 text-center text-gray-400">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4">
                <div class="mb-4 md:mb-0">
                    <h4 class="text-xl font-bold text-white mb-2">RAVE ROLEPLAY</h4>
                    <p>&copy; 2025 Tüm Hakları Saklıdır.</p>
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200"><i class="fab fa-discord fa-2x"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200"><i class="fab fa-youtube fa-2x"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200"><i class="fab fa-instagram fa-2x"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200"><i class="fab fa-steam fa-2x"></i></a>
                </div>
            </div>
            <p class="text-sm">Tasarım ve Geliştirme: Senin Adın</p>
        </div>
    </footer>

    <script>
        // Slider Logic
        let slideIndex = 0;
        const slides = document.querySelectorAll('.slide');

        function showSlides() {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === slideIndex) {
                    slide.classList.add('active');
                }
            });
            slideIndex++;
            if (slideIndex >= slides.length) {
                slideIndex = 0;
            }
            setTimeout(showSlides, 5000); // Change image every 5 seconds
        }

        // Mobile Menu Logic
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden'); // hidden sınıfını toggle ediyoruz
        });

        // Close mobile menu if resized to desktop or clicked outside
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                mobileMenu.classList.add('hidden'); // Masaüstüne geçince gizle
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (slides.length > 0) {
                showSlides();
            }
        });
    </script>
</body>
</html>