-- client.lua
RegisterNetEvent('webkey:display')
AddEventHandler('webkey:display', function(webkey)
    print("--------------------------------------------------")
    print("Değerli oyuncu, web anahtarınız:")
    print("--> " .. webkey .. " <--" )
    print("Bu anahtarı F8 konsolundan kopyalayabilirsiniz.")
    print("--------------------------------------------------")
end)

RegisterNetEvent('Test:Client:SelamAl', function()
    print('>>> CLIENT TESTI: Sunucudan selam basariyla alindi!')
    QBCore.Functions.Notify('Istemci tarafi eventi calisiyor!', 'success', 5000)
end)