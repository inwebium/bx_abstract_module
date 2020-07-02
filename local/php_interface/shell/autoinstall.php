<?php
/**
 * Скрипт позволяет устанавливать/удалять/переустанавливать/стирать
 * нужный модуль. 
 * На одном уровне находится файл autoinstall.json с конфигурацией:
 *  "userId": IdПользователя, Id пользователя который будет выполнять скрипт (1 - стандартный админ)
 *  "moduleId": "IdМодуля", Код модуля в папке local/modules (e.g. inwebium.module)
 *  "action": "Действие", Что делать (install/uninstall/clear/reinstall)
 * install - модуль не установлен в битриксе, установить
 * uninstall - модуль установлен в битриксе, удалить
 * clear - модуль удален в битриксе, удалить файлы в local/modules/IdМодуля
 * reinstall - модуль установлен в битриксе, сначала удалит, потом заново установит
 * deploy - если модуль не установлен, то установит, а если установлен, то reinstall
 * 
 * Так же скрипту можно передать аргументы из командной строки. E.g.:
 * Находясь в папке = DocumentRoot
 * php -f local/php_interface/shell/autoinstall.php -m="inwebium.module" -a="uninstall" -u=1
 * php -d short_open_tag=On local/php_interface/shell/autoinstall.php -m="inwebium.module" -a="uninstall" -u=1
 * 
 * Где аргументами служат ключи
 * -m(--module) - IdМодуля,
 * -a(--action) - Действие,
 * -u(--user) - IdПользователя
 * 
 * Переданные аргументы имеют больший приоритет над параметрами из .json файла
 */

ini_set("max_execution_time", 0);
ini_set("display_errors", 1);

$scriptDir = '/local/php_interface/shell';
$arPath = pathinfo(__FILE__);
$absolutepath = str_replace("\\", "/", $arPath['dirname']);
$stdout = fopen('php://stdout', 'w');

if (strpos($absolutepath, $scriptDir) === false)
{
    fwrite($stdout, "\n\e[1;31mERROR: autoinstall script must be in " . $scriptDir . "\e[0m\n\n");
    die();
}

$docRoot = str_replace($scriptDir, '', $absolutepath);
$_SERVER["DOCUMENT_ROOT"] = $docRoot;

define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", 'Y');
define("NO_AGENT_STATISTIC",'Y');
define("NO_AGENT_CHECK", true);
//define("DisableEventsCheck", true);
define("NOT_CHECK_PERMISSIONS", true);
//define("BX_BUFFER_USED", true);

fwrite($stdout, "HELLO\n");

require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_admin_before.php');
require_once($arPath['dirname'] . '/classes/AutoInstall.php');
global $DB, $USER, $APPLICATION;

$conf = json_decode(file_get_contents($arPath['dirname'] . '/' . $arPath['filename'] . '.json'), true);

$argumentsAssoc = [
    'action' => [
        "a", "action"
    ],
    'moduleId' => [
        "m", "module"
    ],
    'userId' => [
        "u", "user"
    ]
];

$shortOpts = '';
$longOpts = [];
foreach ($argumentsAssoc as $confParam => $arKeys)
{
    $shortOpts .= $arKeys[0] . '::';
    $longOpts[] = $arKeys[1] . '::';
}

// Перезапишем
$arguments = getopt($shortOpts, $longOpts);

if (count($arguments) > 0)
{
    foreach ($arguments as $key => $value)
    {
        if (in_array($key, $argumentsAssoc['action']))
        {
            $conf['action'] = $value;
        }
        if (in_array($key, $argumentsAssoc['moduleId']))
        {
            $conf['moduleId'] = $value;
        }
        if (in_array($key, $argumentsAssoc['userId']))
        {
            $conf['userId'] = $value;
        }
    }
}

$autoInstaller = new AutoInstall($conf, $stdout);

$autoInstaller->Authorize();

switch ($autoInstaller['action'])
{
    case 'install':
        $autoInstaller->Install();
        break;
    case 'uninstall':
        $autoInstaller->UnInstall();
        break;
    case 'clear':
        $autoInstaller->Clear();
        break;
    case 'reinstall':
        $autoInstaller->ReInstall();
        break;
    case 'deploy':
        $autoInstaller->Deploy();
        break;
    default:
        fwrite($stdout, "\n\e[1;31mERROR: unsupported action type.\e[0m\n\n");
        die();
        break;
}

fwrite($stdout, "\e[1;32mScript finished.\e[0m\n\n");
fclose($stdout);