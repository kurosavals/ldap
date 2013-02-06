<?php

/*
 *  Настройки подключения к Active Directory
 */

//список доменных контроллеров
$config['ad']['domain_controllers'] = array(); //array("dc1.example.com","dc2.example.com")

// DN для поиска
$config['ad']['base_dn'] = ""; //OU=Users,DC=example,DC=com

// суффикс подключения
$config['ad']['account_suffix'] = ""; //@example.com

//Учетные данные для подключения
$config['ad']['admin_username'] = ""; //admin
$config['ad']['admin_password'] = ""; //password

// Используемая аутентификация. ssl и tls вместе не работают!
$config['ad']['use_ssl'] = false;
$config['ad']['use_tls'] = false;


//порт для соединения
$config['ad']['ad_port'] = "";

// Группы для администраторов
$config['security']['admin_groups'] = array("Администраторы домена");

/*
 * Настройки для профиля (пользовательские поля)
 * На текущий момент вместо имен используются id полей
 */

$config['profile'] = array(
    '1'=> 'mobile',
);


return $config;

?>