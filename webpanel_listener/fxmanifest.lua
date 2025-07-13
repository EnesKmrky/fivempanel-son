server_script '@ElectronAC/src/include/server.lua'
client_script '@ElectronAC/src/include/client.lua'






fx_version 'cerulean'
game 'gta5'

-- Bu satır QB-Core'u kullanacağımızı belirtir.
shared_script '@qb-core/shared/main.lua'

author 'Enes - Cooldeatcom'
description 'Web Panelden gelen komutları dinler ve QB-Core bildirimi gösterir.'
version '2.1.0' -- Sürüm güncellendi

-- Artık sadece server scriptimiz var.
server_script 'server.lua'
client_script 'client.lua'