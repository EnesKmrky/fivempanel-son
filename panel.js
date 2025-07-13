// Dosya Adı: public/script.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('[Admin Panel Debug] script.js yüklendi ve DOM hazır!'); 

    // Node.js API'sinin gerçek adresi ve portu (38.3.137.2 IP'li VDS'te çalışıyor)
    const nodeJsApiUrl = 'http://38.3.137.2:3000'; 

    // Bu kısımlar admin.php'de HTML elementi olarak yok, bu yüzden kaldırıldı.
    // Eğer adminin Discord avatarını göstermek için bir PHP değişkeni kullanıyorsan ve HTML'de elementi varsa, bu kısmı tekrar ekleyip düzenleyebilirsin.
    /*
    const loggedInDiscordId = '<?php echo $loggedInDiscordId; ?>'; 
    const discordProfileAvatar = document.getElementById('discord-profile-avatar');
    async function fetchDiscordAvatar(discordId) {
        if (!discordId) {
            console.warn("Discord ID mevcut değil, varsayılan avatar gösteriliyor.");
            return;
        }
        try {
            const response = await fetch(`${nodeJsApiUrl}/get-discord-avatar/${discordId}`); 
            const result = await response.json();
            if (result.success && result.avatarUrl) {
                discordProfileAvatar.src = result.avatarUrl;
                console.log("Discord avatarı başarıyla yüklendi:", result.avatarUrl);
            } else {
                console.error("Discord avatarı çekilemedi:", result.message);
            }
        } catch (error) {
            console.error("Discord avatarı çekilirken bir hata oluştu:", error);
        }
    }
    // fetchDiscordAvatar(loggedInDiscordId); 
    */

    let selectedPlayerId = null; 
    let selectedPlayerCitizenId = null; 

    const playerListDiv = document.getElementById('player-list');
    const playerCountSpan = document.getElementById('player-count');
    const welcomeMessageDiv = document.getElementById('welcome-message');
    const actionPanelDiv = document.getElementById('action-panel');
    const selectedPlayerNameSpan = document.getElementById('selected-player-name');
    const selectedPlayerIdSpan = document.getElementById('selected-player-id'); 

    const sendInfoBtn = document.getElementById('send-info-btn');
    const giveMoneyBtn = document.getElementById('give-money-btn');
    const removeMoneyBtn = document.getElementById('remove-money-btn'); 
    const kickPlayerBtn = document.getElementById('kick-player-btn'); 
    const revivePlayerBtn = document.getElementById('revive-player-btn'); 
    const ckPlayerBtn = document.getElementById('ck-player-btn'); 
    const skinMenuBtn = document.getElementById('skin-menu-btn'); 
    const teleportMotelBtn = document.getElementById('teleport-motel-btn'); 
    const btnGiveVehicle = document.getElementById('btnGiveVehicle'); 


    const infoMessageInput = document.getElementById('info-message');
    const moneyAmountInput = document.getElementById('money-amount');
    const kickReasonInput = document.getElementById('kick-reason'); 
    const giveVehiclePlayerIdInput = document.getElementById('giveVehiclePlayerId'); 
    const giveVehicleModelInput = document.getElementById('giveVehicleModel'); 

    const whitelistApplicationsList = document.getElementById('whitelist-applications-list');
    const lspdApplicationsList = document.getElementById('lspd-applications-list');
    const lssApplicationsList = document.getElementById('lss-applications-list');
    const lsbbApplicationsList = document.getElementById('lsbb-applications-list'); 
    const lshApplicationsList = document.getElementById('lsh-applications-list'); 
    const bcsoApplicationsList = document.getElementById('bcso-applications-list'); 


    const newApplicationsBadgeWhitelist = document.getElementById('new-applications-badge-whitelist');
    const newApplicationsBadgeLspd = document.getElementById('new-applications-badge-lspd');
    const newApplicationsBadgeLss = document.getElementById('new-applications-badge-lss');
    const newApplicationsBadgeLsbb = document.getElementById('new-applications-badge-lsbb');
    const newApplicationsBadgeLsh = document.getElementById('new-applications-badge-lsh');
    const newApplicationsBadgeBcso = document.getElementById('new-applications-badge-bcso');


    const rejectionModal = document.getElementById('rejection-modal');
    const rejectionReasonText = document.getElementById('rejection-reason-text');
    const confirmRejectionBtn = document.getElementById('confirm-rejection');
    const cancelRejectionBtn = document.getElementById('cancel-rejection');
    
    let currentApplicationId = null;
    let currentDiscordId = null;
    let currentApplicationType = null; 


    const sidebarNavItems = document.querySelectorAll('.sidebar-nav-item');
    const contentSections = document.querySelectorAll('.content-section');

    const mobileMenuButton = document.getElementById('admin-mobile-menu-button');
    const mainSidebar = document.getElementById('admin-main-sidebar');
    const sidebarOverlay = document.getElementById('admin-sidebar-overlay');


    function activateSection(targetId) {
        console.log(`[Admin Panel Debug] activateSection çağrıldı, hedef ID: ${targetId}`); 
        
        contentSections.forEach(section => {
            section.classList.remove('active');
            section.style.display = 'none'; 
        });
        sidebarNavItems.forEach(item => {
            item.classList.remove('active');
        });

        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            console.log(`[Admin Panel Debug] Hedef bölüm bulundu: ${targetId}. classList: ${targetSection.classList}`); 
            targetSection.classList.add('active');
            targetSection.style.display = 'block'; 

            const activeSidebarItem = document.querySelector(`.sidebar-nav-item[data-target="${targetId}"]`);
            if (activeSidebarItem) {
                activeSidebarItem.classList.add('active');
            }

            if (targetId.includes('-applications-section')) { 
                const appType = targetId.replace('-applications-section', '');
                if (appType !== 'gang' && appType !== 'ems') { 
                    fetchApplications(appType);
                }
            }

        } else {
            console.error(`[Admin Panel Debug] Hata: Hedef bölüm bulunamadı: ${targetId}`); 
        }
        if (mainSidebar.classList.contains('open')) {
            mainSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('visible');
        }
    }

    sidebarNavItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            activateSection(this.dataset.target); 
        });
    });

    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            console.log('[Admin Panel Debug] Mobil menü butonu tıklandı.');
            mainSidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('visible');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            console.log('[Admin Panel Debug] Sidebar overlay tıklandı, sidebar kapatılıyor.');
            mainSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('visible');
        });
    }

    activateSection('player-management-section');


    async function fetchPlayers() {
        try {
            const response = await fetch(`${nodeJsApiUrl}/players`); 
            const players = await response.json(); 
            
            playerListDiv.innerHTML = '';
            playerCountSpan.textContent = players.length;

            if (players.length === 0) {
                playerListDiv.innerHTML = '<p class="text-gray-400">Sunucuda aktif oyuncu yok.</p>';
                return;
            }

            players.forEach(player => {
                const playerItem = document.createElement('div');
                playerItem.className = 'player-list-item p-3 rounded-lg cursor-pointer transition-all duration-200';
                playerItem.innerHTML = `
                    <p class="font-bold">${player.name}</p>
                    <p class="text-sm text-gray-400">
                        ID: ${player.id} | CitizenID: ${player.citizenid || 'N/A'} | Ping: ${player.ping}
                    </p>
                `;
                playerItem.dataset.playerId = player.id; 
                playerItem.dataset.playerName = player.name;
                playerItem.dataset.playerCitizenId = player.citizenid; 

                playerItem.addEventListener('click', () => selectPlayer(player));
                playerListDiv.appendChild(playerItem);
            });
        } catch (error) {
            console.error('[Admin Panel Debug] Oyuncular çekilemedi:', error);
            playerListDiv.innerHTML = '<p class="text-red-500">Oyuncular çekilemedi.</p>';
        }
    }

    function selectPlayer(player) {
        selectedPlayerId = player.id; 
        selectedPlayerCitizenId = player.citizenid; 
        
        if (giveVehiclePlayerIdInput) {
            giveVehiclePlayerIdInput.value = player.citizenid; 
        } else {
            console.error("Hata: 'giveVehiclePlayerIdInput' bulunamadı. admin.php dosyasını kontrol edin.");
        }


        document.querySelectorAll('.player-list-item').forEach(item => {
            item.classList.remove('selected');
            if(item.dataset.playerId == player.id) {
                item.classList.add('selected');
            }
        });

        welcomeMessageDiv.style.display = 'none';
        actionPanelDiv.style.display = 'block';

        selectedPlayerNameSpan.textContent = player.name;
        selectedPlayerIdSpan.textContent = player.id;
    }

    sendInfoBtn.addEventListener('click', () => {
        const message = infoMessageInput.value;
        if (selectedPlayerCitizenId && message.trim() !== '') {
            sendCommand('send_info', selectedPlayerCitizenId, { message: message.trim() });
            infoMessageInput.value = '';
        } else {
            alert('Lütfen bir mesaj yazın ve bir oyuncu seçin.');
        }
    });

    giveMoneyBtn.addEventListener('click', () => {
        const amount = moneyAmountInput.value;
        const moneyType = document.querySelector('input[name="money-type"]:checked').value;
        if (selectedPlayerCitizenId && amount && parseInt(amount) > 0) {
            sendCommand('give_money', selectedPlayerCitizenId, { amount: parseInt(amount), moneyType });
            moneyAmountInput.value = '';
        } else {
            alert('Lütfen geçerli bir miktar girin ve bir oyuncu seçin.');
        }
    });

    removeMoneyBtn.addEventListener('click', () => {
        const amount = moneyAmountInput.value;
        const moneyType = document.querySelector('input[name="money-type"]:checked').value;
        if (selectedPlayerCitizenId && amount && parseInt(amount) > 0) {
            sendCommand('remove_money', selectedPlayerCitizenId, { amount: parseInt(amount), moneyType });
            moneyAmountInput.value = '';
        } else {
            alert('Lütfen geçerli bir miktar girin ve bir oyuncu seçin.');
        }
    });

    kickPlayerBtn.addEventListener('click', () => {
        const reason = kickReasonInput.value.trim();
        if (selectedPlayerCitizenId && reason !== '') {
            sendCommand('kick_player', selectedPlayerCitizenId, { reason });
            kickReasonInput.value = '';
        } else {
            alert('Lütfen bir neden belirtin ve bir oyuncu seçin.');
        }
    });

    revivePlayerBtn.addEventListener('click', () => {
        if (selectedPlayerCitizenId) {
            sendCommand('revive_player', selectedPlayerCitizenId);
        } else {
            alert('Lütfen bir oyuncu seçin.');
        }
    });

    ckPlayerBtn.addEventListener('click', () => {
        if (selectedPlayerCitizenId) {
            if (confirm('Bu oyuncuya CK atmak istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
                sendCommand('ck_player', selectedPlayerCitizenId);
            }
        } else {
            alert('Lütfen bir oyuncu seçin.');
        }
    });

    skinMenuBtn.addEventListener('click', () => {
        if (selectedPlayerCitizenId) {
            sendCommand('skin_menu', selectedPlayerCitizenId);
        } else {
            alert('Lütfen bir oyuncu seçin.');
        }
    });

    teleportMotelBtn.addEventListener('click', () => {
        if (selectedPlayerCitizenId) {
            sendCommand('teleport_motel', selectedPlayerCitizenId);
        } else {
            alert('Lütfen bir oyuncu seçin.');
        }
    });

    if (btnGiveVehicle) {
        btnGiveVehicle.addEventListener('click', function() {
            const playerIdentifier = giveVehiclePlayerIdInput.value.trim(); 
            const vehicleModel = giveVehicleModelInput.value.trim();

            if (!playerIdentifier) {
                alert('Lütfen araç vermek istediğiniz oyuncuyu seçin.');
                return;
            }
            if (!vehicleModel) {
                alert('Lütfen araç modelini girin.');
                return;
            }

            sendCommand('admin_give_vehicle', playerIdentifier, { vehicleModel: vehicleModel });
            giveVehicleModelInput.value = ''; 
        });
    }

    async function sendCommand(type, playerIdentifier, data = {}) { 
        console.log(`[Admin Panel Debug] sendCommand içine girildi. Tip: ${type}, Oyuncu CitizenID: ${playerIdentifier}, Data:`, data); 
        try {
            const payload = { playerId: playerIdentifier, ...data }; 
            const response = await fetch(`${nodeJsApiUrl}/${type.replace('_', '-')}`, { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            
            console.log(`[Admin Panel Debug] Fetch isteği yanıtı alındı. Durum: ${response.status}`); 
            const result = await response.json();
            console.log(`[Admin Panel Debug] Fetch isteği JSON yanıtı:`, result); 

            if (!result.success) {
                throw new Error(result.message || 'Bilinmeyen hata.'); 
            }
            alert(`${type.replace('_', ' ')} komutu sıraya eklendi: ${result.message}`);
            console.log(`[Admin Panel Debug] Komut başarıyla gönderildi: ${type}`); 
        } catch (error) { 
            console.error(`[Admin Panel Debug] sendCommand hatası: ${type} komutu gönderilemedi.`, error); 
            alert(`Hata: ${error.message}`); 
        }
    }

    async function fetchApplications(type) {
        console.log(`[Admin Panel Debug] fetchApplications çağrıldı, tip: ${type}`); 
        const applicationListDiv = document.getElementById(`${type}-applications-list`);
        const newApplicationsBadge = document.getElementById(`new-applications-badge-${type}`);

        if (!applicationListDiv) {
            console.error(`[Admin Panel Debug] Hata: applicationListDiv bulunamadı for type: ${type}`); 
            return;
        }

        applicationListDiv.innerHTML = '<p class="text-gray-400">Başvurular yükleniyor...</p>';
        try {
            const response = await fetch(`${nodeJsApiUrl}/get-${type}-applications`); 
            if (!response.ok) {
                throw new Error(`HTTP hatası! Durum: ${response.status} - ${response.statusText}`); 
            }
            const data = await response.json();
            console.log(`[Admin Panel Debug] ${type} başvuruları çekildi (data):`, data); 

            if (data.success) {
                applicationListDiv.innerHTML = ''; 
                let pendingCount = 0;
                if (data.applications.length === 0) {
                    applicationListDiv.innerHTML = '<p class="text-gray-400">Henüz bekleyen başvuru bulunmamaktadır.</p>';
                } else {
                    data.applications.forEach(app => {
                        if (app.status === 'Beklemede') {
                            pendingCount++;
                        }
                        const appElement = document.createElement('div');
                        appElement.className = `card p-6 mb-4 application-card status-${app.status}`; 
                        appElement.innerHTML = `
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-semibold">${app.character_name} (<a href="https://discordapp.com/users/${app.discord_id}" target="_blank" class="text-blue-400 hover:underline">@${app.discord_id}</a>)</h3>
                                <span class="px-3 py-1 rounded-full text-sm font-medium status-badge-${app.status}">
                                    ${app.status === 'Beklemede' ? 'BEKLEMEDE' : app.status === 'Onaylandı' ? 'ONAYLANDI' : 'REDDEDİLDİ'}
                                </span>
                            </div>
                            <p class="text-gray-300 mb-4 whitespace-pre-wrap"><strong>Başvuru Metni:</strong> ${app.application_text}</p>
                            ${app.rejection_reason ? `<p class="text-red-300 text-sm mt-2"><strong>Reddetme Sebebi:</strong> ${app.rejection_reason}</p>` : ''}
                            <p class="text-gray-500 text-sm">Başvuru Tarihi: ${new Date(app.created_at).toLocaleString()}</p>
                            <div class="mt-4 flex space-x-2">
                                ${app.status === 'Beklemede' ? `
                                    <button class="approve-btn bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded" data-id="${app.id}" data-discord-id="${app.discord_id}" data-type="${type}">Onayla</button>
                                    <button class="reject-btn bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded" data-id="${app.id}" data-discord-id="${app.discord_id}" data-type="${type}">Reddet</button>
                                ` : `
                                    <button class="reset-btn bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded" data-id="${app.id}" data-discord-id="${app.discord_id}" data-type="${type}">Beklemeye Al</button>
                                `}
                            </div>
                        `;
                        applicationListDiv.appendChild(appElement);
                    });

                    document.querySelectorAll(`#${type}-applications-list .approve-btn`).forEach(button => {
                        button.addEventListener('click', function() {
                            updateApplicationStatus(this.dataset.id, 'Onaylandı', null, this.dataset.discordId, this.dataset.type);
                        });
                    });

                    document.querySelectorAll(`#${type}-applications-list .reject-btn`).forEach(button => {
                        button.addEventListener('click', function() {
                            currentApplicationId = this.dataset.id;
                            currentDiscordId = this.dataset.discordId;
                            currentApplicationType = this.dataset.type; 
                            rejectionReasonText.value = ''; 
                            rejectionModal.classList.remove('hidden');
                        });
                    });

                    document.querySelectorAll(`#${type}-applications-list .reset-btn`).forEach(button => {
                        button.addEventListener('click', function() {
                            updateApplicationStatus(this.dataset.id, 'Beklemede', null, this.dataset.discordId, this.dataset.type);
                        });
                    });
                }
                if (newApplicationsBadge) { 
                     newApplicationsBadge.textContent = pendingCount; 
                }
            } else {
                applicationListDiv.innerHTML = `<p class="text-red-400">Başvurular yüklenirken hata oluştu: ${data.message}</p>`;
            }
        } catch (error) {
            console.error(`[Admin Panel Debug] ${type} başvuruları çekilirken genel hata:`, error); 
            applicationListDiv.innerHTML = '<p class="text-red-400">Sunucuya bağlanılamadı veya bir hata oluştu.</p>';
        }
    }

    cancelRejectionBtn.addEventListener('click', function() {
        rejectionModal.classList.add('hidden');
        currentApplicationId = null;
        currentDiscordId = null;
        currentApplicationType = null;
    });

    confirmRejectionBtn.addEventListener('click', function() {
        const reason = rejectionReasonText.value.trim();
        if (currentApplicationId && currentDiscordId && currentApplicationType) {
            updateApplicationStatus(currentApplicationId, 'Reddedildi', reason, currentDiscordId, currentApplicationType);
            rejectionModal.classList.add('hidden');
            currentApplicationId = null;
            currentDiscordId = null;
            currentApplicationType = null;
        }
    });

    async function updateApplicationStatus(id, status, reason, discordId, type) {
        console.log(`[Admin Panel Debug] Başvuru durumu güncelleniyor, tip: ${type}, ID: ${id}, durum: ${status}`);
        try {
            const response = await fetch(`${nodeJsApiUrl}/update-${type}-application-status`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, status, rejection_reason: reason, discord_id: discordId }),
            });
            const data = await response.json();

            if (data.success) {
                alert('Başvuru durumu başarıyla güncellendi!');
                fetchApplications(type);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error(`[Admin Panel Debug] Başvuru durumu güncellenirken hata, tip: ${type}:`, error);
            alert('Sunucuya bağlanılamadı veya bir hata oluştu: ' + error.message);
        }
    }

    fetchPlayers();
    setInterval(fetchPlayers, 10000);

    fetchApplications('whitelist');
    fetchApplications('lspd');
    fetchApplications('lss');
    fetchApplications('lsbb');
    fetchApplications('lsh');
    fetchApplications('bcso');
    
    setInterval(() => {
        fetchApplications('whitelist');
        fetchApplications('lspd');
        fetchApplications('lss');
        fetchApplications('lsbb');
        fetchApplications('lsh');
        fetchApplications('bcso');
    }, 30000);
});