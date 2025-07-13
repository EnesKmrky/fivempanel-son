// Dosya Adı: server.js

import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import cors from 'cors';
import mysql from 'mysql';
import { Client, GatewayIntentBits, Partials } from 'discord.js';
import bcrypt from 'bcrypt'; 

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3000;

// Discord Bot Ayarları (SENİN BİLGİLERİNLE GÜNCEL)
const client = new Client({ 
    intents: [
        GatewayIntentBits.Guilds, 
        GatewayIntentBits.GuildMembers, 
        GatewayIntentBits.GuildPresences,
        GatewayIntentBits.MessageContent 
    ],
    partials: [Partials.User, Partials.GuildMember, Partials.Presence] 
}); 

const DISCORD_TOKEN = 'MTM4OTkyOTcyMzQ2MjY4NDcwMg.GNrOby.FYtUIhgHFLX7v7Qe6fsw2JEm4yQRcCmKBAyozQ'; // Senin Discord bot token'ın
const GUILD_ID = '758367214734016533'; 
const DISCORD_NOTIFICATION_CHANNEL_ID = '1389028403654557776'; 
const DISCORD_WHITELIST_NOTIFICATION_CHANNEL_ID = '1389028403654557776'; 
const DISCORD_ROLE_WHITELISTED = '1387906780406743090'; 
const DISCORD_ROLE_REJECTED = '1387907347745083502'; 

const DISCORD_LSPD_APPLICATION_CHANNEL_ID = '1389028403654557776'; 
const DISCORD_LSPD_APPROVED_ROLE_ID = '1330879297287880755'; 

const DISCORD_LSSS_APPLICATION_CHANNEL_ID = '1389028403654557776';
const DISCORD_LSSS_APPROVED_ROLE_ID = '1387565794053066883'; 

const DISCORD_LSBB_APPLICATION_CHANNEL_ID = '1389028403654557776';
const DISCORD_LSBB_APPROVED_ROLE_ID = '1330879349934784524'; 

const DISCORD_LSH_APPLICATION_CHANNEL_ID = '1389028403654557776';
const DISCORD_LSH_APPROVED_ROLE_ID = '1330879344616407040'; 

const DISCORD_BCSO_APPLICATION_CHANNEL_ID = '1389028403654557776';
const DISCORD_BCSO_APPROVED_ROLE_ID = '1330879360885981246'; 

let discordStats = {
    online: 'N/A',
    total: 'N/A'
};

client.once('ready', async () => {
    console.log(`Discord botu ${client.user.tag} olarak giriş yaptı!`);
    
    const updateDiscordStats = async () => {
        try {
            const guild = await client.guilds.fetch(GUILD_ID);
            await guild.members.fetch({ withPresences: true }); 
            
            discordStats.total = guild.memberCount;
            discordStats.online = guild.presences.cache.filter(p => p.status !== 'offline').size;
            
            console.log(`Discord sunucu verileri çekildi: Toplam ${discordStats.total}, Aktif ${discordStats.online}`);
        } catch (error) {
            console.error("Discord sunucu verileri çekilirken hata:", error);
            discordStats.online = 'Hata';
            discordStats.total = 'Hata';
        }
    };

    await updateDiscordStats();
    setInterval(updateDiscordStats, 30000); 
});

client.login(DISCORD_TOKEN).catch(err => {
    console.error("Discord bota giriş yapılamadı. Token'ı kontrol et.", err);
});

// Veritabanı Bağlantısı 
const db = mysql.createConnection({
    host: '188.191.107.176', // Senin MySQL hostun
    user: 'paneluser', // Senin MySQL kullanıcın
    password: 'enesenes6636!#!', // Senin MySQL şifren
    database: 'effronte13_pck' // Senin veritabanı adın
});

let isDbConnected = false;

db.connect((err) => {
    if (err) {
        console.error('MySQL bağlantı hatası: ' + err.stack);
        isDbConnected = false; 
        return;
    }
    console.log('MySQL veritabanına başarıyla bağlandı.');
    isDbConnected = true; 
});

app.use(express.json());
app.use(cors());
app.use(express.static(path.join(__dirname, 'public')));

let pendingCommands = []; 

let currentPlayerList = [];

// Her 5 saniyede bir current PlayerList'i logla (debug amaçlı)
setInterval(() => {
    console.log(`[Server Debug] Güncel Oyuncu Listesi Sayısı: ${currentPlayerList.length}`);
}, 5000);


app.post('/submit-whitelist-application', async (req, res) => {
    const { discord_id, character_name, application_text } = req.body;

    if (!discord_id || !character_name || !application_text) {
        return res.status(400).json({ success: false, message: 'Eksik bilgi: Discord ID, Karakter Adı veya Başvuru Metni.' });
    }

    try {
        console.log(`[Discord Debug] Whitelist bildirimi için kanal ID: ${DISCORD_WHITELIST_NOTIFICATION_CHANNEL_ID}`);
        const channel = client.channels.cache.get(DISCORD_WHITELIST_NOTIFICATION_CHANNEL_ID);

        if (channel) {
            console.log(`[Discord Debug] Whitelist başvuru kanalı bulundu: ${channel.name} (${channel.id})`);
            await channel.send(`:page_with_curl: **YENİ WHITELIST BAŞVURUSU!**\n\n**Discord ID:** <@${discord_id}>\n**Karakter Adı:** ${character_name}\n**Başvuru Metni:** ${application_text.substring(0, 500)}${application_text.length > 500 ? '...' : ''}\n\nAdmin panelinden kontrol et: http://38.3.137.2/public/admin.php`);
            console.log(`[Discord Debug] Whitelist başvuru bildirimi başarıyla gönderildi.`);
        } else {
            console.warn(`[Discord Debug] Whitelist bildirim kanalı bulunamadı veya botun erişimi yok: ${DISCORD_WHITELIST_NOTIFICATION_CHANNEL_ID}`);
        }

        res.status(200).json({ success: true, message: 'Başvuru başarıyla alındı ve bildirim gönderildi.' });

    } catch (error) {
        console.error("Whitelist başvuru bildirimi gönderilirken hata:", error);
        res.status(500).json({ success: false, message: 'Başvuru alındı ancak Discord bildirimi gönderilirken hata oluştu.' });
    }
});

// Admin panelinden whitelist başvurularını çekme (Yeni endpoint)
app.get('/get-whitelist-applications', (req, res) => {
    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }
    db.query('SELECT * FROM whitelist_applications ORDER BY created_at DESC', (error, results) => {
        if (error) {
            console.error("Whitelist başvuruları çekilirken hata:", error);
            return res.status(500).json({ success: false, message: 'Başvurular çekilirken bir hata oluştu.' });
        }
        res.status(200).json({ success: true, applications: results });
    });
});

// Helper fonksiyon: Başvuru durumuna göre rol verme/çıkarma ve DM gönderme
async function handleApplicationStatusUpdate(discord_id, status, application_type, rejection_reason = null) {
    try {
        const user = await client.users.fetch(discord_id);
        if (!user) {
            console.warn(`[Discord Debug] Discord ID ${discord_id} bulunamadı veya botun erişimi yok. DM gönderilemedi.`);
            return;
        }

        let messageToUser = '';
        let approvedRoleId = '';
        let guildMember = null;
        
        try {
            const guild = await client.guilds.fetch(GUILD_ID);
            guildMember = await guild.members.fetch(discord_id);
        } catch (fetchError) {
            console.warn(`[Discord Debug] Discord ID ${discord_id} için sunucu üyesi bulunamadı:`, fetchError);
        }

        switch(application_type) {
            case 'whitelist':
                approvedRoleId = DISCORD_ROLE_WHITELISTED;
                if (status === 'Onaylandı') messageToUser = `:white_check_mark: **Whitelist Başvurunuz Onaylandı!**\n\nSunucumuza hoş geldiniz! Whitelist adımlarınızı tamamlamak için "Whitelist Sesli Mülakat" kanalına geçiş sağlamalısınız.`;
                else if (status === 'Reddedildi') messageToUser = `:x: **Whitelist Başvurunuz Reddedildi!**\n\nSebep: ${rejection_reason || 'Belirtilmedi.'}\n\nTekrar başvurmadan önce belirtilen sorunları gidermeniz önerilir.`;
                else messageToUser = `:hourglass: **Whitelist Başvurunuzun Durumu Güncellendi: Beklemede**\n\nBaşvurunuz incelenmeye devam ediyor. Lütfen sabırla bekleyin.`;
                break;
            case 'lspd':
                approvedRoleId = DISCORD_LSPD_APPROVED_ROLE_ID;
                if (status === 'Onaylandı') messageToUser = `:white_check_mark: **LSPD Başvurunuz Onaylandı!**\n\nTebrikler! Artık LSPD ekibimizin bir parçasısınız. Discord rolleriniz güncellendi.`;
                else if (status === 'Reddedildi') messageToUser = `:x: **LSPD Başvurunuz Reddedildi!**\n\nSebep: ${rejection_reason || 'Belirtilmedi.'}\n\nTekrar başvurmadan önce belirtilen sorunları gidermeniz önerilir.`;
                else messageToUser = `:hourglass: **LSPD Başvurunuzun Durumu Güncellendi: Beklemede**`;
                break;
            case 'lsss':
                approvedRoleId = DISCORD_LSSS_APPROVED_ROLE_ID;
                if (status === 'Onaylandı') messageToUser = `:white_check_mark: **LSSS Başvurunuz Onaylandı!**\n\nTebrikler! Artık LSSS ekibimizin bir parçasısınız. Discord rolleriniz güncellendi.`;
                else if (status === 'Reddedildi') messageToUser = `:x: **LSSS Başvurunuz Reddedildi!**\n\nSebep: ${rejection_reason || 'Belirtilmedi.'}\n\nTekrar başvurmadan önce belirtilen sorunları gidermeniz önerilir.`;
                else messageToUser = `:hourglass: **LSSS Başvurunuzun Durumu Güncellendi: Beklemede**`;
                break;
            case 'lsbb':
                approvedRoleId = DISCORD_LSBB_APPROVED_ROLE_ID;
                if (status === 'Onaylandı') messageToUser = `:white_check_mark: **LSBB Başvurunuz Onaylandı!**\n\nTebrikler! Artık LSBB ekibimizin bir parçasısınız. Discord rolleriniz güncellendi.`;
                else if (status === 'Reddedildi') messageToUser = `:x: **LSBB Başvurunuz Reddedildi!**\n\nSebep: ${rejection_reason || 'Belirtilmedi.'}\n\nTekrar başvurmadan önce belirtilen sorunları gidermeniz önerilir.`;
                else messageToUser = `:hourglass: **LSBB Başvurunuzun Durumu Güncellendi: Beklemede**`;
                break;
            case 'lsh':
                approvedRoleId = DISCORD_LSH_APPROVED_ROLE_ID;
                if (status === 'Onaylandı') messageToUser = `:white_check_mark: **Los Santos Hastanesi Başvurunuz Onaylandı!**\n\nTebrikler! Artık LSH ekibimizin bir parçasısınız. Discord rolleriniz güncellendi.`;
                else if (status === 'Reddedildi') messageToUser = `:x: **Los Santos Hastanesi Başvurunuz Reddedildi!**\n\nSebep: ${rejection_reason || 'Belirtilmedi.'}\n\nTekrar başvurmadan önce belirtilen sorunları gidermeniz önerilir.`;
                else messageToUser = `:hourglass: **Los Santos Hastanesi Başvurunuzun Durumu Güncellendi: Beklemede**`;
                break;
            case 'bcso':
                approvedRoleId = DISCORD_BCSO_APPROVED_ROLE_ID;
                if (status === 'Onaylandı') messageToUser = `:white_check_mark: **Blaine County Sheriffs Office Başvurunuz Onaylandı!**\n\nTebrikler! Artık BCSO ekibimizin bir parçasısınız. Discord rollersiniz güncellendi.`;
                else if (status === 'Reddedildi') messageToUser = `:x: **Blaine County Sheriffs Office Başvurunuz Reddedildi!**\n\nSebep: ${rejection_reason || 'Belirtilmedi.'}\n\nTekrar başvurmadan önce belirtilen sorunları gidermeniz önerilir.`;
                else messageToUser = `:hourglass: **Blaine County Sheriffs Office Başvurunuzun Durumu Güncellendi: Beklemede**`;
                break;
            default:
                console.warn(`[Discord Debug] Bilinmeyen başvuru türü: ${application_type}. DM gönderilmiyor.`);
                return;
        }

        // Rol verme/çıkarma
        if (guildMember) {
            if (status === 'Onaylandı' && approvedRoleId && approvedRoleId !== 'SENIN_ROL_ID_BURAYA' && approvedRoleId !== '') {
                try {
                    await guildMember.roles.add(approvedRoleId);
                    if (DISCORD_ROLE_REJECTED && DISCORD_ROLE_REJECTED !== 'SENIN_REDDEDILEN_ROL_ID' && guildMember.roles.cache.has(DISCORD_ROLE_REJECTED)) {
                        await guildMember.roles.remove(DISCORD_ROLE_REJECTED);
                    }
                    console.log(`[Discord Debug] Kullanıcı ${discord_id} için ${application_type} rolü başarıyla verildi: ${approvedRoleId}`);
                } catch (roleError) {
                    console.error(`[Discord Debug] Kullanıcıya ${application_type} rolü verilirken hata: ${discord_id}`, roleError);
                    messageToUser += `\n\n*Not: Rol verme işleminde bir sorun oluştu, lütfen bir yetkiliye danışın.*`;
                }
            } else if (status === 'Reddedildi' && DISCORD_ROLE_REJECTED && DISCORD_ROLE_REJECTED !== 'SENIN_REDDEDILEN_ROL_ID' && DISCORD_ROLE_REJECTED !== '') {
                     try {
                    if (approvedRoleId && guildMember.roles.cache.has(approvedRoleId)) {
                        await guildMember.roles.remove(approvedRoleId);
                    }
                    await guildMember.roles.add(DISCORD_ROLE_REJECTED);
                    console.log(`[Discord Debug] Kullanıcı ${discord_id} için reddedildi rolü başarıyla verildi: ${DISCORD_ROLE_REJECTED}`);
                   } catch (roleError) {
                    console.error(`[Discord Debug] Kullanıcıya reddedildi rolü verilirken hata: ${discord_id}`, roleError);
                    messageToUser += `\n\n*Not: Rol verme işleminde bir sorun oluştu, lütfen bir yetkiliye danışın.*`;
                   }
            }
        } else {
            console.warn(`[Discord Debug] Guild üyesi bulunamadığı için rol işlemi yapılamadı: ${discord_id}`);
            messageToUser += `\n\n*Not: Rol verilemedi, Discord sunucumuzda bulunmuyor olabilirsiniz.*`;
        }
        
        try {
            await user.send(messageToUser);
            console.log(`[Discord Debug] Kullanıcıya DM gönderildi: ${discord_id}`);
        } catch (dmError) {
            console.error(`[Discord Debug] Kullanıcıya DM gönderilirken hata:`, dmError);
        }

    } catch (error) {
        console.error(`[Discord Debug] Başvuru durumu güncelleme sonrası rol/DM işleme hatası: ${error}`);
    }
}


app.post('/update-whitelist-application-status', async (req, res) => {
    const { id, status, rejection_reason, discord_id } = req.body;
    if (!id || !status || !discord_id) return res.status(400).json({ success: false, message: 'Eksik bilgi: Başvuru ID, Durum veya Discord ID.' });
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });

    let query;
    let params;
    if (status === 'Reddedildi') {
        query = 'UPDATE whitelist_applications SET status = ?, rejection_reason = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?';
        params = [status, rejection_reason, id];
    } else {
        query = 'UPDATE whitelist_applications SET status = ?, rejection_reason = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?';
        params = [status, id];
    }

    try {
        await new Promise((resolve, reject) => { db.query(query, params, (error, results) => { if (error) reject(error); else resolve(results); }); });
        await handleApplicationStatusUpdate(discord_id, status, 'whitelist', rejection_reason);
        res.status(200).json({ success: true, message: 'Başvuru durumu başarıyla güncellendi ve bildirim gönderildi.' });
    }
    catch (error) {
        console.error("Whitelist başvuru durumu güncelleme hatası:", error);
        res.status(500).json({ success: false, message: 'Başvuru durumu güncellenirken bir hata oluştu.' });
    }
});


// Sunucu istatistiklerini çeken yol
app.get('/get-stats', (req, res) => {
    const stats = {
        activePlayers: currentPlayerList.length,
        totalPlayers: 0,
        discordOnline: discordStats.online,
        discordTotal: discordStats.total
    };

    if (isDbConnected) {
        db.query('SELECT COUNT(citizenid) as total FROM players', (error, results, fields) => {
            if (error) {
                console.error("Toplam oyuncu sayısı çekilemédi:", error);
                return res.status(500).json({ message: 'Veritabanı hatası' });
            }
            stats.totalPlayers = results[0].total;
            res.status(200).json(stats);
        });
    } else {
        stats.totalPlayers = 'Hata (DB Bağlı Değil)';
        res.status(500).json(stats); 
    }
});

// Oyuncu listesini sunar
app.get('/players', (req, res) => {
    res.status(200).json(currentPlayerList);
});

// FiveM'in komutları çektiği ve oyuncu listesini gönderdiği endpoint (ÖNEMLİ GÜNCELLEME!)
app.post('/get-command', (req, res) => { // Bu endpoint artık sadece komutlarla ilgilenecek
    console.log(`[Server Debug] /get-command endpoint'ine komut isteği geldi.`); 
    // FiveM'den gelen oyuncu verisi burada işlenmeyecek, /update-players'ta işlenecek.

    res.status(200).json({ commands: pendingCommands }); // Komutları gönder
    pendingCommands = []; // Gönderdikten sonra listeyi temizle
});

// YENİ ENDPOINT: FiveM'den oyuncu listesini alıp güncelleyecek
app.post('/update-players', (req, res) => {
    if (req.body && req.body.players) {
        console.log(`[Server Debug] /update-players endpoint'ine oyuncu verisi alındı. Gelen oyuncu sayısı: ${req.body.players.length}`); 
        currentPlayerList = req.body.players; // Oyuncu listesini cache'le
        res.status(200).json({ success: true, message: 'Oyuncu listesi güncellendi.' });
    } else {
        console.warn(`[Server Debug] /update-players endpoint'ine oyuncu verisi gelmedi veya boş geldi.`); 
        res.status(400).json({ success: false, message: 'Eksik oyuncu verisi.' });
    }
});


// Genel komutları işleyen fonksiyon
// Bu fonksiyon, gelen komutları pendingCommands dizisine eklemelidir.
function handleCommand(req, res, type) {
    const { playerId, reason, message, amount, moneyType, vehicleModel } = req.body; 
    if (!playerId) {
        return res.status(400).json({ success: false, message: 'Oyuncu ID eksik.' });
    }
    
    pendingCommands.push({
        command: type, 
        playerId: playerId,
        reason: reason,
        message: message,
        amount: amount,
        moneyType: moneyType,
        vehicleModel: vehicleModel 
    });

    res.status(200).json({ success: true, message: `Komut (${type}) sıraya eklendi.` });
}

app.post('/send-info', (req, res) => handleCommand(req, res, 'send_info'));
app.post('/give-money', (req, res) => handleCommand(req, res, 'give_money'));
app.post('/remove-money', (req, res) => handleCommand(req, res, 'remove_money'));
app.post('/kick-player', (req, res) => handleCommand(req, res, 'kick_player'));
app.post('/ck-player', (req, res) => handleCommand(req, res, 'ck_player'));
app.post('/revive-player', (req, res) => handleCommand(req, res, 'revive_player'));
app.post('/skin-menu', (req, res) => handleCommand(req, res, 'skin_menu'));
app.post('/teleport-motel', (req, res) => handleCommand(req, res, 'teleport_motel'));

// Frontend'den gelen /execute-command isteğini yakalar ve handleCommand'a yönlendirir.
app.post('/execute-command', (req, res) => { // BU SATIR YENİ EKLENDİ VEYA AKTİF EDİLDİ
    const { command, playerId, ...rest } = req.body;
    if (!command || !playerId) {
        return res.status(400).json({ success: false, message: 'Komut adı veya Oyuncu ID eksik.' });
    }
    // Gelen command adını handleCommand'a iletmek için type olarak kullanıyoruz
    // Örneğin, command: 'send_info', playerId: 'citizenid123', message: 'Hello'
    handleCommand(req, res, command);
});


// --- DESTEK TALEBİ ENDPOINTLERİ ---

// Destek talebi oluşturma
app.post('/create-ticket', async (req, res) => {
    const { citizenid, name, title, message } = req.body; 
    if (!citizenid || !name || !title || !message) {
        return res.status(400).json({ success: false, message: 'Eksik bilgi: citizenid, name, başlık veya mesaj.' });
    }

    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }

    try {
        const ticketInsertResult = await new Promise((resolve, reject) => {
            db.query('INSERT INTO support_tickets (citizenid, title, message, status) VALUES (?, ?, ?, ?)', 
                [citizenid, title, message, 'Açık'], 
                (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        const ticketId = ticketInsertResult.insertId;

        await new Promise((resolve, reject) => {
            db.query('INSERT INTO support_ticket_messages (ticket_id, citizenid, message) VALUES (?, ?, ?)', 
                [ticketId, citizenid, message], 
                (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        console.log(`[Discord Debug] Destek mesajı bildirimi için kanal ID: ${DISCORD_NOTIFICATION_CHANNEL_ID}`);
        const channel = client.channels.cache.get(DISCORD_NOTIFICATION_CHANNEL_ID);
        if (channel) {
            console.log(`[Discord Debug] Destek talebi için kanal bulundu: ${channel.name} (${channel.id})`);
            try {
                await channel.send(`:ticket: **YENİ DESTEK TALEBİ!**\n\n**Oyuncu:** ${name} (${citizenid})\n**Talep ID:** ${ticketId}\n**Başlık:** ${title}\n**Mesaj:** ${message.substring(0, 200)}${message.length > 200 ? '...' : ''}\n\nPaneli kontrol et!`);
                console.log(`[Discord Debug] Destek talebi mesaj bildirimi başarıyla gönderildi.`);
            } catch (discordError) {
                console.error(`[Discord Debug] Destek talebi mesaj bildirimi gönderilirken hata:`, discordError);
            }
        } else {
            console.warn(`[Discord Debug] Destek bildirim kanalı bulunamadı: ${DISCORD_NOTIFICATION_CHANNEL_ID}`);
            console.warn(`[Discord Debug] Botun bu kanala erişimi veya kanal ID'si hatalı olabilir.`);
        }

        res.status(200).json({ success: true, message: 'Mesajınız başarıyla gönderildi.' });

    } catch (error) {
        console.error("Destek talebi oluşturma hatası:", error);
        res.status(500).json({ success: false, message: 'Destek talebi oluşturulurken bir hata oluştu.' });
    }
});

// Oyuncunun destek taleplerini listeleme
app.get('/get-tickets', (req, res) => {
    const { citizenid } = req.query;
    if (!citizenid) {
        return res.status(400).json({ success: false, message: 'Eksik bilgi: citizenid.' });
    }

    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }

    db.query('SELECT * FROM support_tickets WHERE citizenid = ? ORDER BY created_at DESC', [citizenid], (error, results) => {
        if (error) {
            console.error("Destek talepleri çekme hatası:", error);
            return res.status(500).json({ success: false, message: 'Destek talepleri çekilirken bir hata oluştu.' });
        }
        res.status(200).json({ success: true, tickets: results });
    });
});

// Bir destek talebine ait mesajları listeleme
app.get('/get-ticket-messages', (req, res) => {
    const { ticketId } = req.query;
    if (!ticketId) {
        return res.status(400).json({ success: false, message: 'Eksik bilgi: ticketId.' });
    }

    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }

    db.query('SELECT stm.*, p.name as sender_name FROM support_ticket_messages stm LEFT JOIN players p ON stm.citizenid = p.citizenid WHERE stm.ticket_id = ? ORDER BY stm.created_at ASC', [ticketId], (error, results) => {
        if (error) {
            console.error("Destek talebi mesajları çekme hatası:", error);
            return res.status(500).json({ success: false, message: 'Destek talebi mesajları çekilirken bir hata oluştu.' });
        }
        res.status(200).json({ success: true, messages: results });
    });
});

// Destek talebine mesaj gönderme
app.post('/send-ticket-message', async (req, res) => {
    const { ticketId, citizenid, name, message } = req.body; 
    if (!ticketId || !citizenid || !name || !message) {
        return res.status(400).json({ success: false, message: 'Eksik bilgi: ticketId, citizenid, name veya mesaj.' });
    }

    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }

    try {
        await new Promise((resolve, reject) => {
            db.query('INSERT INTO support_ticket_messages (ticket_id, citizenid, message) VALUES (?, ?, ?)', 
                [ticketId, citizenid, message], 
                (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        await new Promise((resolve, reject) => {
            db.query('UPDATE support_tickets SET updated_at = CURRENT_TIMESTAMP(), status = ? WHERE id = ? AND status != "Kapalı"', 
                ['Cevap Bekliyor', ticketId], 
                (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        console.log(`[Discord Debug] Destek mesajı bildirimi için kanal ID: ${DISCORD_NOTIFICATION_CHANNEL_ID}`);
        const channel = client.channels.cache.get(DISCORD_NOTIFICATION_CHANNEL_ID);
        if (channel) {
            console.log(`[Discord Debug] Destek talebi için kanal bulundu: ${channel.name} (${channel.id})`);
            try {
                await channel.send(`:speech_balloon: **YENİ DESTEK TALEBİNE YENİ MESAJ!**\n\n**Talep ID:** ${ticketId}\n**Gönderen:** ${name} (${citizenid})\n**Mesaj:** ${message.substring(0, 200)}${message.length > 200 ? '...' : ''}\n\nPaneli kontrol et!`);
                console.log(`[Discord Debug] Destek talebi mesaj bildirimi başarıyla gönderildi.`);
            } catch (discordError) {
                console.error(`[Discord Debug] Destek talebi mesaj bildirimi gönderilirken hata:`, discordError);
            }
        } else {
            console.warn(`[Discord Debug] Destek bildirim kanalı bulunamadı: ${DISCORD_NOTIFICATION_CHANNEL_ID}`);
            console.warn(`[Discord Debug] Botun bu kanala erişimi veya kanal ID'si hatalı olabilir.`);
        }

        res.status(200).json({ success: true, message: 'Mesajınız başarıyla gönderildi.' });

    } catch (error) {
        console.error("Destek talebine mesaj gönderme hatası:", error);
        res.status(500).json({ success: false, message: 'Mesaj gönderilirken bir hata oluştu.' });
    }
});

app.post('/change-password', async (req, res) => {
    const { citizenid, currentPassword, newPassword } = req.body;
    if (!citizenid || !currentPassword || !newPassword) {
        return res.status(400).json({ success: false, message: 'Eksik bilgi: citizenid, mevcut şifre veya yeni şifre.' });
    }

    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }

    try {
        const userResult = await new Promise((resolve, reject) => {
            db.query('SELECT webpassword FROM players WHERE citizenid = ?', [citizenid], (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        if (userResult.length === 0 || !userResult[0].webpassword) {
            return res.status(404).json({ success: false, message: 'Kullanıcı bulunamadı veya şifre atanmamış.' });
        }

        const storedHashedPassword = userResult[0].webpassword;
        const isPasswordValid = await bcrypt.compare(currentPassword, storedHashedPassword);

        if (!isPasswordValid) {
            return res.status(401).json({ success: false, message: 'Mevcut şifreniz yanlış.' });
        }

        const hashedNewPassword = await bcrypt.hash(newPassword, 10); 
        await new Promise((resolve, reject) => {
            db.query('UPDATE players SET webpassword = ? WHERE citizenid = ?', [hashedNewPassword, citizenid], (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        res.status(200).json({ success: true, message: 'Şifreniz başarıyla değiştirildi.' });

    } catch (error) {
        console.error("Şifre değiştirme hatası:", error);
        res.status(500).json({ success: false, message: 'Şifre değiştirilirken bir hata oluştu.' });
    }
});

app.post('/submit-lspd-application', async (req, res) => {
    const { discord_id, character_name, application_text, citizenid } = req.body;
    if (!discord_id || !character_name || !application_text) return res.status(400).json({ success: false, message: 'Eksik bilgi: Discord ID, Karakter Adı veya Başvuru Metni.' });
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });

    try {
        const insertResult = await new Promise((resolve, reject) => {
            db.query('INSERT INTO lspd_applications (discord_id, character_name, application_text, status, citizenid) VALUES (?, ?, ?, ?, ?)',
                [discord_id, character_name, application_text, 'Beklemede', citizenid], 
                (error, results) => { if (error) reject(error); else resolve(results); });
        });
        const application_id = insertResult.insertId;

        const channel = client.channels.cache.get(DISCORD_LSPD_APPLICATION_CHANNEL_ID); 
        if (channel) await channel.send(`:police_car: **YENİ LSPD BAĞVURUSU!**\n\n**Başvuru ID:** ${application_id}\n**Discord ID:** <@${discord_id}>\n**Karakter Adı:** ${character_name}\n**Başvuru Metni:** ${application_text.substring(0, 500)}${application_text.length > 500 ? '...' : ''}\n\nAdmin panelinden kontrol et!`);
        else console.warn(`[Discord Debug] LSPD Başvurusu bildirim kanalı bulunamadı: ${DISCORD_LSPD_APPLICATION_CHANNEL_ID}`);

        res.status(200).json({ success: true, message: 'LSPD başvurunuz başarıyla alındı ve bildirim gönderildi.', application_id });
    } catch (error) {
        console.error("LSPD başvurusu gönderilirken hata:", error);
        res.status(500).json({ success: false, message: 'LSPD başvurusu gönderilirken bir hata oluştu.' });
    }
});

app.post('/submit-lsss-application', async (req, res) => {
    const { discord_id, character_name, application_text, citizenid } = req.body;
    if (!discord_id || !character_name || !application_text) return res.status(400).json({ success: false, message: 'Eksik bilgi: Discord ID, Karakter Adı veya Başvuru Metni.' });
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });

    try {
        const insertResult = await new Promise((resolve, reject) => {
            db.query('INSERT INTO lsss_applications (discord_id, character_name, application_text, status, citizenid) VALUES (?, ?, ?, ?, ?)',
                [discord_id, character_name, application_text, 'Beklemede', citizenid], 
                (error, results) => { if (error) reject(error); else resolve(results); });
        });
        const application_id = insertResult.insertId;

        const channel = client.channels.cache.get(DISCORD_LSSS_APPLICATION_CHANNEL_ID); 
        if (channel) await channel.send(`:oncoming_police_car: **YENİ LSSS BAĞVURUSU!**\n\n**Başvuru ID:** ${application_id}\n**Discord ID:** <@${discord_id}>\n**Karakter Adı:** ${character_name}\n**Başvuru Metni:** ${application_text.substring(0, 500)}${application_text.length > 500 ? '...' : ''}\n\nAdmin panelinden kontrol et!`);
        else console.warn(`[Discord Debug] LSSS Başvurusu bildirim kanalı bulunamadı: ${DISCORD_LSSS_APPLICATION_CHANNEL_ID}`);

        res.status(200).json({ success: true, message: 'LSSS başvurunuz başarıyla alındı ve bildirim gönderildi.', application_id });
    } catch (error) {
        console.error("LSSS başvurusu gönderilirken hata:", error);
        res.status(500).json({ success: false, message: 'LSSS başvurusu gönderilirken bir hata oluştu.' });
    }
});

app.post('/submit-lsbb-application', async (req, res) => {
    const { discord_id, character_name, application_text, citizenid } = req.body;
    if (!discord_id || !character_name || !application_text) return res.status(400).json({ success: false, message: 'Eksik bilgi: Discord ID, Karakter Adı veya Başvuru Metni.' });
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });

    try {
        const insertResult = await new Promise((resolve, reject) => {
            db.query('INSERT INTO lsbb_applications (discord_id, character_name, application_text, status, citizenid) VALUES (?, ?, ?, ?, ?)',
                [discord_id, character_name, application_text, 'Beklemede', citizenid], 
                (error, results) => { if (error) reject(error); else resolve(results); });
        });
        const application_id = insertResult.insertId;

        const channel = client.channels.cache.get(DISCORD_LSBB_APPLICATION_CHANNEL_ID); 
        if (channel) await channel.send(`:city_sunset: **YENİ LSBB BAĞVURUSU!**\n\n**Başvuru ID:** ${application_id}\n**Discord ID:** <@${discord_id}>\n**Karakter Adı:** ${character_name}\n**Başvuru Metni:** ${application_text.substring(0, 500)}${application_text.length > 500 ? '...' : ''}\n\nAdmin panelinden kontrol et!`);
        else console.warn(`[Discord Debug] LSBB Başvurusu bildirim kanalı bulunamadı: ${DISCORD_LSBB_APPLICATION_CHANNEL_ID}`);

        res.status(200).json({ success: true, message: 'LSBB başvurunuz başarıyla alındı ve bildirim gönderildi.', application_id });
    } catch (error) {
        console.error("LSBB başvurusu gönderilirken hata:", error);
        res.status(500).json({ success: false, message: 'LSBB başvurusu gönderilirken bir hata oluştu.' });
    }
});

app.post('/submit-lsh-application', async (req, res) => {
    const { discord_id, character_name, application_text, citizenid } = req.body;
    if (!discord_id || !character_name || !application_text) return res.status(400).json({ success: false, message: 'Eksik bilgi: Discord ID, Karakter Adı veya Başvuru Metni.' });
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });

    try {
        const insertResult = await new Promise((resolve, reject) => {
            db.query('INSERT INTO lsh_applications (discord_id, character_name, application_text, status, citizenid) VALUES (?, ?, ?, ?, ?)',
                [discord_id, character_name, application_text, 'Beklemede', citizenid], 
                (error, results) => { if (error) reject(error); else resolve(results); });
        });
        const application_id = insertResult.insertId;

        const channel = client.channels.cache.get(DISCORD_LSH_APPLICATION_CHANNEL_ID); 
        if (channel) await channel.send(`:hospital: **YENİ LOS SANTOS HASTANESİ BAĞVURUSU!**\n\n**Başvuru ID:** ${application_id}\n**Discord ID:** <@${discord_id}>\n**Karakter Adı:** ${character_name}\n**Başvuru Metni:** ${application_text.substring(0, 500)}${application_text.length > 500 ? '...' : ''}\n\nAdmin panelinden kontrol et!`);
        else console.warn(`[Discord Debug] Los Santos Hastanesi Başvurusu bildirim kanalı bulunamadı: ${DISCORD_LSH_APPLICATION_CHANNEL_ID}`);

        res.status(200).json({ success: true, message: 'Los Santos Hastanesi başvurunuz başarıyla alındı ve bildirim gönderildi.', application_id });
    } catch (error) {
        console.error("Los Santos Hastanesi başvurusu gönderilirken hata:", error);
        res.status(500).json({ success: false, message: 'Los Santos Hastanesi başvurusu gönderilirken bir hata oluştu.' });
    }
});

app.post('/submit-bcso-application', async (req, res) => {
    const { discord_id, character_name, application_text, citizenid } = req.body;
    if (!discord_id || !character_name || !application_text) return res.status(400).json({ success: false, message: 'Eksik bilgi: Discord ID, Karakter Adı veya Başvuru Metni.' });
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });

    try {
        const insertResult = await new Promise((resolve, reject) => {
            db.query('INSERT INTO bcso_applications (discord_id, character_name, application_text, status, citizenid) VALUES (?, ?, ?, ?, ?)',
                [discord_id, character_name, application_text, 'Beklemede', citizenid], 
                (error, results) => { if (error) reject(error); else resolve(results); });
        });
        const application_id = insertResult.insertId;

        const channel = client.channels.cache.get(DISCORD_BCSO_APPLICATION_CHANNEL_ID); 
        if (channel) await channel.send(`:sheriff_badge: **YENİ BLAINE COUNTY SHERIFFS OFFICE BAŞVURUSU!**\n\n**Başvuru ID:** ${application_id}\n**Discord ID:** <@${discord_id}>\n**Karakter Adı:** ${character_name}\n**Başvuru Metni:** ${application_text.substring(0, 500)}${application_text.length > 500 ? '...' : ''}\n\nAdmin panelinden kontrol et!`);
        else console.warn(`[Discord Debug] Blaine County Sheriffs Office Başvurusu bildirim kanalı bulunamadı: ${DISCORD_BCSO_APPLICATION_CHANNEL_ID}`);

        res.status(200).json({ success: true, message: 'Blaine County Sheriffs Office başvurunuz başarıyla alındı ve bildirim gönderildi.', application_id });
    } catch (error) {
        console.error("Blaine County Sheriffs Office başvurusu gönderilirken hata:", error);
        res.status(500).json({ success: false, message: 'Blaine County Sheriffs Office başvurusu gönderilirken bir hata oluştu.' });
    }
});


app.get('/get-whitelist-applications', (req, res) => {
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    db.query('SELECT * FROM whitelist_applications ORDER BY created_at DESC', (error, results) => {
        if (error) return res.status(500).json({ success: false, message: 'Başvurular çekilirken hata oluştu.', error: error.message });
        res.status(200).json({ success: true, applications: results });
    });
});

app.get('/get-lspd-applications', (req, res) => {
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    db.query('SELECT * FROM lspd_applications ORDER BY created_at DESC', (error, results) => {
        if (error) return res.status(500).json({ success: false, message: 'Başvurular çekilirken hata oluştu.', error: error.message });
        res.status(200).json({ success: true, applications: results });
    });
});

app.get('/get-lsss-applications', (req, res) => {
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    db.query('SELECT * FROM lsss_applications ORDER BY created_at DESC', (error, results) => {
        if (error) return res.status(500).json({ success: false, message: 'Başvurular çekilirken hata oluştu.', error: error.message });
        res.status(200).json({ success: true, applications: results });
    });
});

app.get('/get-lsbb-applications', (req, res) => {
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    db.query('SELECT * FROM lsbb_applications ORDER BY created_at DESC', (error, results) => {
        if (error) return res.status(500).json({ success: false, message: 'Başvurular çekilirken hata oluştu.', error: error.message });
        res.status(200).json({ success: true, applications: results });
    });
});

app.get('/get-lsh-applications', (req, res) => {
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    db.query('SELECT * FROM lsh_applications ORDER BY created_at DESC', (error, results) => {
        if (error) return res.status(500).json({ success: false, message: 'Başvurular çekilirken hata oluştu.', error: error.message });
        res.status(200).json({ success: true, applications: results });
    });
});

app.get('/get-bcso-applications', (req, res) => {
    if (!isDbConnected) return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    db.query('SELECT * FROM bcso_applications ORDER BY created_at DESC', (error, results) => {
        if (error) return res.status(500).json({ success: false, message: 'Başvurular çekilirken hata oluştu.', error: error.message });
        res.status(200).json({ success: true, applications: results });
    });
});

app.get('/get-player-discord-avatar/:citizenid', async (req, res) => {
    const { citizenid } = req.params;

    if (!isDbConnected) {
        return res.status(500).json({ success: false, message: 'Veritabanı bağlı değil.' });
    }

    try {
        const [playerData] = await new Promise((resolve, reject) => {
            db.query('SELECT discord_id FROM players WHERE citizenid = ?', [citizenid], (error, results) => {
                if (error) reject(error);
                else resolve(results);
            });
        });

        if (playerData && playerData.discord_id) {
            const discordId = playerData.discord_id;
            try {
                const discordUser = await client.users.fetch(discordId);
                
                if (discordUser && discordUser.avatar) {
                    const avatarUrl = discordUser.displayAvatarURL({ dynamic: true, size: 128 });
                    return res.status(200).json({ success: true, discordId: discordId, avatarUrl: avatarUrl });
                } else if (discordUser) {
                    const defaultAvatarUrl = discordUser.defaultAvatarURL;
                    return res.status(200).json({ success: true, discordId: discordId, avatarUrl: defaultAvatarUrl });
                } else {
                    return res.status(404).json({ success: false, message: 'Discord kullanıcısı bot tarafından bulunamadı.' });
                }
            } catch (discordFetchError) {
                console.error(`[Discord Debug] Discord kullanıcısı (${discordId}) fetch edilirken hata:`, discordFetchError);
                return res.status(404).json({ success: false, message: 'Discord kullanıcısı bulunamadı (API hatası).' });
            }
        } else {
            return res.status(404).json({ success: false, message: 'Oyuncuya ait Discord ID veritabanında bulunamadı.' });
        }

    } catch (error) {
        console.error("Discord avatarı çekilirken backend hatası:", error);
        res.status(500).json({ success: false, message: 'Sunucu hatası: Discord avatarı çekilemedi.' });
    }
});


app.listen(PORT, () => {
    console.log(`Dayıoğlu, sunucu http://38.3.137.2:${PORT} adresinde çalışıyor.`);
});