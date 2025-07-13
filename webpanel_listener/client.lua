-- webpanel_listener/client.lua

-- Bu dosya, web panelden gelen ve client tarafında işlenmesi gereken komutları içerecek.

-- Admin tarafından araç doğrulama ve spawn etme (server.lua'da tetikleniyor)
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
        
        -- Oyuncuya bildirim
        TriggerEvent('QBCore:Notify', ("Yönetici tarafından size yeni bir araç verildi: %s (Plaka: %s)"):format(vehicleModel, plate), "success", 7500)
    end)
end)

-- YENİ EKLENECEK KISIM: Admin tarafından araç silme komutunu client tarafında işleme
RegisterNetEvent('webpanel_listener:client:deleteAdminVehicleConfirmed')
AddEventHandler('webpanel_listener:client:deleteAdminVehicleConfirmed', function(plateToDelete)
    Citizen.CreateThread(function()
        local playerPed = PlayerPedId()
        local vehicles = GetGamePool('CVehicle') -- Çevredeki tüm araçları al

        local vehicleFound = false
        for _, vehicle in ipairs(vehicles) do
            local vehiclePlate = GetVehicleNumberPlateText(vehicle)
            if vehiclePlate == plateToDelete then
                -- Eğer araç oyuncunun içinde değilse veya oyuncu yakınında değilse sil
                if IsPedInVehicle(playerPed, vehicle, false) then
                    -- Oyuncu araçtaysa, aracı dışına ışınla ve sonra sil
                    TaskLeaveVehicle(playerPed, vehicle, 256) -- 256 = KeepDoorsOpen
                    Citizen.Wait(1000) -- Oyuncu araçtan çıksın
                    DeleteVehicle(vehicle)
                    print(string.format("[webpanel_listener Client] Oyuncunun aracı (%s) araçtan çıkarıldı ve silindi.", plateToDelete))
                else
                    DeleteVehicle(vehicle)
                    print(string.format("[webpanel_listener Client] Oyuncunun aracı (%s) doğrudan silindi.", plateToDelete))
                end
                vehicleFound = true
                break -- Aracı bulduk ve sildik, döngüyü sonlandır
            end
        end

        if vehicleFound then
            TriggerEvent('QBCore:Notify', ("Aracınız (%s) yönetici tarafından oyun içinden silindi."):format(plateToDelete), "info", 7500)
        else
            TriggerEvent('QBCore:Notify', ("Yönetici tarafından silinmesi istenen araç (%s) oyun içinde bulunamadı."):format(plateToDelete), "error", 7500)
        end
        print(string.format("[webpanel_listener Client] Araç silme komutu işlendi: %s", plateToDelete))
    end)
end)