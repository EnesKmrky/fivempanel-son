server_script '@ElectronAC/src/include/server.lua'
client_script '@ElectronAC/src/include/client.lua'

-- fxmanifest.lua
fx_version 'cerulean'
game 'gta5'

server_scripts {
    'server.lua', -- Bu dosya script klasörünün kökünde mi?
}

client_scripts {
    'client.lua', -- Bu dosya da script klasörünün kökünde mi?
}

dependencies {
    'ox-mysql', -- Eğer kullanıyorsan ve adı buysa doğru.
}
-- BUNU EKLE!
dependencies {
    'qb-core'
}