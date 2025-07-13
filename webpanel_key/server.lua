-- server.lua
math.randomseed(os.time()) -- random düzgün çalışsın

local webkeyLength = 16 -- Web anahtarı uzunluğu

RegisterCommand("webkey", function(source, args, rawCommand)
    local player = source
    local playerName = GetPlayerName(player)

    if playerName and type(playerName) == 'string' then
        exports.oxmysql:query("SELECT webkey FROM players WHERE name = ?", { playerName }, function(result)
            if result and #result > 0 and result[1].webkey and result[1].webkey ~= '' then
                TriggerClientEvent('webkey:display', player, result[1].webkey)
                print(("[Webkey Script] Oyuncu %s zaten bir web anahtarına sahip: %s"):format(playerName, result[1].webkey))
            else
                local webkey = GenerateRandomString(webkeyLength)
                exports.oxmysql:query("UPDATE players SET webkey = ? WHERE name = ?", { webkey, playerName }, function(updateResult)
                    if updateResult and updateResult.affectedRows and updateResult.affectedRows > 0 then
                        TriggerClientEvent('webkey:display', player, webkey)
                        print(("[Webkey Script] Oyuncu %s için yeni web anahtarı oluşturuldu: %s"):format(playerName, webkey))
                    else
                        print(("[Webkey Script HATA] Web anahtarı oluşturulamadı: %s"):format(playerName))
                    end
                end)
            end
        end)
    else
        print(("[Webkey Script HATA] Oyuncu adı alınamadı. Alınan: %s"):format(tostring(playerName)))
    end
end)

function GenerateRandomString(length)
    local chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    local randomString = ""
    for i = 1, length do
        local rand = math.random(1, #chars)
        randomString = randomString .. chars:sub(rand, rand)
    end
    return randomString
end
