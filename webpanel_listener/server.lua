-- webpanel_listener/server.lua

local QBCore = exports['qb-core']:GetCoreObject()
local panelUrl = "http://38.3.137.2:3000" -- Node.js API'sinin adresi ve portu (eski VDS IP'si)

local motelCoords = vector3(-3016.2568, 91.4313, 11.6125) -- Genel bir motel odası koordinatı

-- Plaka oluşturma için gerekli yardımcı fonksiyonlar
local StringCharset = {}
local NumberCharset = {}
for i = 48, 57 do NumberCharset[#NumberCharset + 1] = string.char(i) end -- 0-9
for i = 65, 90 do StringCharset[#StringCharset + 1] = string.char(i) end -- A-Z (büyük harf)

function RandomStr(length)
    if length <= 0 then return '' end
    return RandomStr(length - 1) .. StringCharset[math.random(1, #StringCharset)]
end

function RandomInt(length)
    if length <= 0 then return '' end
    return RandomInt(length - 1) .. NumberCharset[math.random(1, #NumberCharset)]
end

function GeneratePlate()
    local plate = RandomStr(3) .. RandomInt(3) .. RandomStr(1) -- Örneğin: AAA123B
    local result = exports.oxmysql:scalar('SELECT plate FROM player_vehicles WHERE plate = ?', {plate})
    if result then
        return GeneratePlate()
    else
        return plate:upper()
    end
end

-- Birinci thread: Node.js'den gelen komutları işler (SADECE KOMUT ÇEKECEK)
Citizen.CreateThread(function()
    print('[webpanel_listener] Panel dinleme servisi (Tüm Komutlar) başladı.')
    
    while true do
        Citizen.Wait(5000)

        PerformHttpRequest(panelUrl .. "/get-command", function(err, responseText, headers) -- Sadece /get-command endpoint'ine istek at
            print(string.format('[webpanel_listener Debug] API yanıtı alındı. HTTP Durum: %s', tostring(err)))
            
            if err == 200 then
                print(string.format('[webpanel_listener Debug] Ham API Yanıtı (responseText): %s', tostring(responseText)))
                local data = json.decode(responseText)
                print(string.format('[webpanel_listener Debug] JSON Çözümlenmiş Data Objesi (data): %s', json.encode(data)))
                
                if data then
                    if data.commands and type(data.commands) == 'table' then
                        print(string.format('[webpanel_listener Debug] API\'den %s adet komut alındı. (Komut İşleme Başlıyor)', #data.commands))
                        if #data.commands == 0 then
                            print('[webpanel_listener Debug] API\'den gelen komut listesi boş. (Normal olabilir)')
                        end

                        for _, cmd in ipairs(data.commands) do 
                            print(string.format('[webpanel_listener Debug] İşlenen komut: %s, Hedef PlayerID: %s (Player ID tipi: %s)', tostring(cmd.command), tostring(cmd.playerId), type(cmd.playerId)))
                            local commandType = cmd.command
                            local targetPlayerId = cmd.playerId 

                            local targetPlayer = QBCore.Functions.GetPlayerByCitizenId(targetPlayerId) 

                            if targetPlayer then 
                                print(string.format('[webpanel_listener Debug] Oyuncu %s (%s) bulundu. Komut uygulanıyor.', targetPlayer.PlayerData.name, targetPlayer.PlayerData.citizenid))
                                local sourceId = targetPlayer.PlayerData.source
                                local reason = cmd.reason or "Sebep belirtilmedi."

                                if commandType == 'send_info' then
                                    targetPlayer.Functions.Notify("[WEB PANEL]: " .. tostring(cmd.message), 'primary', 5000)
                                    print(string.format('[webpanel_listener Debug] send_info komutu uygulandı: %s', tostring(cmd.message)))
                                elseif commandType == 'give_money' then
                                    targetPlayer.Functions.AddMoney(tostring(cmd.moneyType), tonumber(cmd.amount))
                                    targetPlayer.Functions.Notify(("[WEB PANEL]: %s $ %s para aldın."):format(tostring(cmd.amount), tostring(cmd.moneyType)), 'success')
                                    print(string.format('[webpanel_listener Debug] give_money komutu uygulandı: %s %s %s', tostring(cmd.amount), tostring(cmd.moneyType), targetPlayer.PlayerData.name))
                                elseif commandType == 'remove_money' then
                                    targetPlayer.Functions.RemoveMoney(tostring(cmd.moneyType), tonumber(cmd.amount))
                                    targetPlayer.Functions.Notify(("[WEB PANEL]: %s $ %s para silindi."):format(tostring(cmd.amount), tostring(cmd.moneyType)), 'error')
                                    print(string.format('[webpanel_listener Debug] remove_money komutu uygulandı: %s %s %s', tostring(cmd.amount), tostring(cmd.moneyType), targetPlayer.PlayerData.name))
                                elseif commandType == 'kick_player' then
                                    QBCore.Functions.Kick(sourceId, "[WEB PANEL]: " .. tostring(reason), false)
                                    print(string.format('[webpanel_listener Debug] kick_player komutu uygulandı: %s %s', targetPlayer.PlayerData.name, tostring(reason)))
                                elseif commandType == 'revive_player' then
                                    TriggerClientEvent('hospital:client:Revive', sourceId)
                                    targetPlayer.Functions.Notify("Yeniden canlandırıldın.", "success")
                                    print(string.format('[webpanel_listener Debug] revive_player komutu uygulandı: %s', targetPlayer.PlayerData.name))
                                elseif commandType == 'ck_player' then
                                    targetPlayer.Functions.Notify("Karakterinize CK atıldı. Geçmiş olsun.", "error", 10000)
                                    Citizen.Wait(2000)
                                    QBCore.Functions.DeletePlayer(sourceId)
                                    print(string.format('[webpanel_listener Debug] ck_player komutu uygulandı: %s', targetPlayer.PlayerData.name))
                                elseif commandType == 'skin_menu' then
                                    TriggerClientEvent('qb-clothing:client:openMenu', sourceId)
                                    print(string.format('[webpanel_listener Debug] skin_menu komutu uygulandı: %s', targetPlayer.PlayerData.name))
                                elseif commandType == 'teleport_motel' then
                                    TriggerClientEvent('QBCore:Command:TeleportToCoords', sourceId, motelCoords.x, motelCoords.y, motelCoords.z)
                                    targetPlayer.Functions.Notify("Motele ışınlandın.", "primary")
                                    print(string.format('[webpanel_listener Debug] teleport_motel komutu uygulandı: %s', targetPlayer.PlayerData.name))
                                elseif commandType == 'admin_give_vehicle' then
                                    local vehicleModel = cmd.vehicleModel
                                    if vehicleModel then
                                        local playerCoords = GetEntityCoords(GetPlayerPed(sourceId))
                                        local playerHeading = GetEntityHeading(GetPlayerPed(sourceId))

                                        local plate = GeneratePlate()
                                        local vehicleHash = GetHashKey(vehicleModel)

                                        local defaultModsJson = json.encode({
                                            primaryColour = 0,
                                            secondaryColour = 0,
                                        })
                                        
                                        exports.oxmysql:insert('INSERT INTO player_vehicles (license, citizenid, vehicle, hash, mods, plate, garage, state) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', {
                                            targetPlayer.PlayerData.license, 
                                            targetPlayer.PlayerData.citizenid, 
                                            tostring(vehicleModel),
                                            tonumber(vehicleHash),
                                            defaultModsJson, 
                                            tostring(plate),
                                            'pillboxgarage', -- SENİN GARAJ İSMİNİ BURAYA YAZ!
                                            0 
                                        }, function(rowsAffected)
                                            if rowsAffected and rowsAffected > 0 then
                                                TriggerClientEvent('webpanel_listener:client:spawnAdminVehicleConfirmed', sourceId, tostring(vehicleModel), tostring(plate), playerCoords.x, playerCoords.y, playerCoords.z, playerHeading)
                                                TriggerClientEvent('vehiclekeys:client:SetOwner', sourceId, plate)

                                                targetPlayer.Functions.Notify(("Yönetici tarafından %s aracı verildi. Plaka: %s"):format(vehicleModel, plate), "success", 7500)
                                                print(string.format("[FiveM Admin][Server] Oyuncu %s (%s) adlı aracı başarıyla sahiplendirildi ve spawn bilgisi gönderildi. Plaka: %s", targetPlayer.PlayerData.name, tostring(vehicleModel), tostring(plate)))
                                            else
                                                targetPlayer.Functions.Notify('Araç sahiplendirme başarısız oldu!', 'error', 7500)
                                                print(string.format("[FiveM Admin][Server] HATA: Oyuncu %s (%s) için araç sahiplendirme oxmysql hatası veya etkilenen satır yok.", targetPlayer.PlayerData.name, tostring(vehicleModel)))
                                            end
                                        end)
                                    else
                                        targetPlayer.Functions.Notify('Araç modeli belirtilmedi.', 'error', 5000)
                                        print("[FiveM Admin][Server] Araç verme komutu için araç modeli eksik.")
                                    end
                                elseif commandType == 'delete_vehicle_ingame' then -- YENİ: Araç Silme Komutu
                                    local plateToDelete = cmd.plate
                                    if plateToDelete then
                                        TriggerClientEvent('webpanel_listener:client:deleteAdminVehicleConfirmed', sourceId, plateToDelete)
                                        targetPlayer.Functions.Notify(("Yönetici tarafından aracınız (%s) silindi."):format(plateToDelete), "info", 7500)
                                        print(string.format("[FiveM Admin][Server] Oyuncu %s (%s) adlı aracı silme komutu gönderildi.", targetPlayer.PlayerData.name, plateToDelete))
                                    else
                                        print("[FiveM Admin][Server] Araç silme komutu için plaka eksik.")
                                    end
                                else
                                    print(string.format("[webpanel_listener Debug] Bilinmeyen komut tipi veya oyuncu bulunamadı. Komut: %s, Hedef ID: %s", tostring(commandType), tostring(targetPlayerId), tostring(targetPlayerId)))
                                end
                            else -- Oyuncu bulunamadıysa
                                print(string.format("[webpanel_listener Debug] Komut (%s): Oyuncu %s bulunamadı veya çevrimdışı. (TargetPlayerId: %s)", tostring(commandType), tostring(targetPlayerId), tostring(targetPlayerId)))
                            end
                        end -- for loop for commands
                    else -- 'data.commands' tablosu yoksa veya boşsa
                        print('[webpanel_listener Debug] Data objesi mevcut, ancak "commands" özelliği yok veya nil.')
                    end
                else -- 'data' objesi nil veya tablo değilse
                    print('[webpanel_listener Debug] JSON çözümlenmiş data objesi nil veya geçersiz.')
                end
            else -- HTTP status not 200
                print(string.format("[NodeJS Command] Node.js sunucusundan komut çekilemedi. HTTP Durum Kodu: %s", tostring(err)))
            end
        end, "POST", json.encode({}), { ['Content-Type'] = "application/json" })
    end
end)

-- İkinci thread: FiveM'den Node.js'e oyuncu listesi gönderme (Cache için)
-- Bu thread artık /update-players endpoint'ine gönderecek.
local playersToSend = {}
Citizen.CreateThread(function()
    while true do
        Citizen.Wait(10000) -- Her 10 saniyede bir oyuncu listesini gönder
        if QBCore then
            playersToSend = {}
            for k, v in pairs(GetPlayers()) do 
                local player = QBCore.Functions.GetPlayer(tonumber(v))
                if player and player.PlayerData and player.PlayerData.citizenid then 
                    table.insert(playersToSend, { 
                        id = player.PlayerData.source, 
                        name = player.PlayerData.name, 
                        ping = GetPlayerPing(player.PlayerData.source),
                        citizenid = player.PlayerData.citizenid
                    })
                end
            end
            PerformHttpRequest(panelUrl .. "/update-players", function(statusCode, text, headers)
                if statusCode ~= 200 then
                    print(string.format("[FiveM -> NodeJS] Oyuncu listesi gönderilirken hata. HTTP Durum Kodu: %s", tostring(statusCode)))
                end
            end, "POST", json.encode({ players = playersToSend }), { ['Content-Type'] = "application/json" })
        end
    end
end)

-- FiveM Client tarafında admin tarafından spawn edilen aracı doğrulama ve spawn etme
RegisterNetEvent('webpanel_listener:client:spawnAdminVehicleConfirmed')
AddEventHandler('webpanel_listener:client:spawnAdminVehicleConfirmed', function(vehicleModel, plate, x, y, z, heading)
    Citizen.CreateThread(function()
        local modelHash = GetHashKey(vehicleModel)
        RequestModel(modelHash)
        while not HasModelLoaded(modelHash) do
            Citizen.Wait(10)
        end
        
        local spawnCoords = vector3(x, y, z)
        local spawnedVehicle = CreateVehicle(modelHash, spawnCoords.x, spawnCoords.y, spawnCoords.z, heading, true, false)
        
        SetVehicleNumberPlateText(spawnedVehicle, plate)
        SetVehicleEngineOn(spawnedVehicle, true, true, true)
        SetVehicleDirtLevel(spawnedVehicle, 0.0)
        SetModelAsNoLongerNeeded(modelHash)
        
        TaskWarpIntoVehicle(PlayerPedId(), spawnedVehicle, -1)
    end)
end)

-- YENİ: FiveM Client tarafında admin tarafından araç silme komutunu işleme
RegisterNetEvent('webpanel_listener:client:deleteAdminVehicleConfirmed')
AddEventHandler('webpanel_listener:client:deleteAdminVehicleConfirmed', function(plateToDelete)
    Citizen.CreateThread(function()
        local playerPed = PlayerPedId()
        local vehicles = GetGamePool('CVehicle') 

        for _, vehicle in ipairs(vehicles) do
            local vehiclePlate = GetVehicleNumberPlateText(vehicle)
            if vehiclePlate == plateToDelete then
                if IsPedInVehicle(playerPed, vehicle, false) then
                    TaskLeaveVehicle(playerPed, vehicle, 256) 
                    Citizen.Wait(1000) 
                    DeleteVehicle(vehicle)
                    print(string.format("[webpanel_listener Client] Oyuncunun aracı (%s) araçtan çıkarıldı ve silindi.", plateToDelete))
                else
                    DeleteVehicle(vehicle)
                    print(string.format("[webpanel_listener Client] Oyuncunun aracı (%s) doğrudan silindi.", plateToDelete))
                end
                break 
            end
        end
        print(string.format("[webpanel_listener Client] Araç silme komutu işlendi: %s", plateToDelete))
    end)
end)