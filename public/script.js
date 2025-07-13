// Dosya Adı: public/script.js
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Admin Panel Debug] script.js yüklendi.');

    // Node.js API'sinin adresi.
    const nodeJsApiUrl = 'http://38.3.137.2:3000';

    //--- Değişkenler ---
    let selectedPlayerCitizenId = null;

    //--- Elementleri Topla ---
    const elements = {
        playerList: document.getElementById('player-list'),
        playerCount: document.getElementById('player-count'),
        actionPanel: document.getElementById('action-panel'),
        selectedPlayerName: document.getElementById('selected-player-name'),
        giveVehiclePlayerIdInput: document.getElementById('giveVehiclePlayerId'),
        giveVehicleModelInput: document.getElementById('giveVehicleModel'),
        infoMessageInput: document.getElementById('info-message'),
        moneyAmountInput: document.getElementById('money-amount'),
        kickReasonInput: document.getElementById('kick-reason'),
        refreshDataBtn: document.getElementById('refresh-data-btn'), // BU BUTONU HTML'E EKLEMEYİ UNUTMA!
        sendInfoBtn: document.getElementById('send-info-btn'),
        giveMoneyBtn: document.getElementById('give-money-btn'),
        removeMoneyBtn: document.getElementById('remove-money-btn'),
        kickPlayerBtn: document.getElementById('kick-player-btn'),
        revivePlayerBtn: document.getElementById('revive-player-btn'),
        ckPlayerBtn: document.getElementById('ck-player-btn'),
        skinMenuBtn: document.getElementById('skin-menu-btn'),
        teleportMotelBtn: document.getElementById('teleport-motel-btn'),
        btnGiveVehicle: document.getElementById('btnGiveVehicle'),
    };

    //--- Ana Fonksiyonlar ---

    // BÜTÜN KOMUTLARI TEK BİR ADRESE YOLLAYAN DELİKANLI FONKSİYON
    async function sendCommand(type, playerIdentifier, data = {}) {
        if (!playerIdentifier) {
            alert('Lütfen bir oyuncu seçin!');
            return;
        }
        console.log(`[Debug] Komut gönderiliyor: Tip: ${type}, Hedef: ${playerIdentifier}`);
        try {
            const response = await fetch(`${nodeJsApiUrl}/execute-command`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    command: type,
                    playerId: playerIdentifier,
                    ...data
                }),
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || `Sunucu Hatası: ${response.status}`);
            
            alert(`Komut sıraya eklendi: ${type}`);
        } catch (error) {
            console.error(`[HATA] Komut gönderilemedi (${type}):`, error);
            alert(`Hata: ${error.message}`);
        }
    }

    async function fetchPlayers() {
        console.log('[Debug] Oyuncular çekiliyor...');
        try {
            const response = await fetch(`${nodeJsApiUrl}/players`);
            const players = await response.json();
            elements.playerList.innerHTML = '';
            elements.playerCount.textContent = players.length;

            if (players.length === 0) {
                elements.playerList.innerHTML = '<p class="text-gray-400">Sunucuda aktif oyuncu yok.</p>';
                return;
            }

            players.forEach(player => {
                const playerItem = document.createElement('div');
                playerItem.className = 'player-list-item p-3 rounded-lg cursor-pointer transition-all duration-200';
                playerItem.innerHTML = `<p class="font-bold">${player.name}</p><p class="text-sm text-gray-400">ID: ${player.id} | Ping: ${player.ping}</p>`;
                playerItem.addEventListener('click', () => {
                    selectedPlayerCitizenId = player.citizenid;
                    elements.selectedPlayerName.textContent = player.name;
                    if(elements.giveVehiclePlayerIdInput) elements.giveVehiclePlayerIdInput.value = player.citizenid;
                    elements.actionPanel.style.display = 'block';
                    
                    document.querySelectorAll('.player-list-item').forEach(item => item.classList.remove('selected'));
                    playerItem.classList.add('selected');
                });
                elements.playerList.appendChild(playerItem);
            });
        } catch (error) {
            console.error('[HATA] Oyuncular çekilemedi:', error);
            elements.playerList.innerHTML = '<p class="text-red-500">Oyuncu listesi yüklenemedi.</p>';
        }
    }

    //--- Olay Dinleyicileri (Event Listeners) ---
    elements.refreshDataBtn?.addEventListener('click', fetchPlayers);

    elements.sendInfoBtn?.addEventListener('click', () => {
        const message = elements.infoMessageInput.value;
        if (message.trim()) sendCommand('send_info', selectedPlayerCitizenId, { message: message.trim() });
    });

    elements.giveMoneyBtn?.addEventListener('click', () => {
        const amount = parseInt(elements.moneyAmountInput.value);
        const moneyType = document.querySelector('input[name="money-type"]:checked').value;
        if (amount > 0) sendCommand('give_money', selectedPlayerCitizenId, { amount, moneyType });
    });
    
    elements.btnGiveVehicle?.addEventListener('click', () => {
        const playerIdentifier = elements.giveVehiclePlayerIdInput.value.trim();
        const vehicleModel = elements.giveVehicleModelInput.value.trim();
        if (playerIdentifier && vehicleModel) sendCommand('admin_give_vehicle', playerIdentifier, { vehicleModel });
    });

    elements.removeMoneyBtn?.addEventListener('click', () => {
        const amount = parseInt(elements.moneyAmountInput.value);
        const moneyType = document.querySelector('input[name="money-type"]:checked').value;
        if (amount > 0) sendCommand('remove_money', selectedPlayerCitizenId, { amount, moneyType });
    });
    elements.kickPlayerBtn?.addEventListener('click', () => {
        const reason = elements.kickReasonInput.value.trim();
        if(reason) sendCommand('kick_player', selectedPlayerCitizenId, { reason });
    });
    elements.revivePlayerBtn?.addEventListener('click', () => sendCommand('revive_player', selectedPlayerCitizenId));
    elements.skinMenuBtn?.addEventListener('click', () => sendCommand('skin_menu', selectedPlayerCitizenId));
    elements.teleportMotelBtn?.addEventListener('click', () => sendCommand('teleport_motel', selectedPlayerCitizenId));
    elements.ckPlayerBtn?.addEventListener('click', () => {
        if (confirm('Bu oyuncuya CK atmak istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
            sendCommand('ck_player', selectedPlayerCitizenId);
        }
    });


    //--- Sayfa İlk Yüklendiğinde ---
    fetchPlayers(); // Sayfa açılırken oyuncuları bir kere çek, yeter.
});