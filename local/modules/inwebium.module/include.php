<?php
global $DB, $MESS, $APPLICATION;

function scanFolder($folder, $namespace, $classpath)
{
    $classesMap = [];
    $dir = scandir($folder);
    
    foreach ($dir as $key => $item)
    {
        $fullPath = $folder . '/' . $item;

        if (is_file($fullPath) && pathinfo($fullPath)['extension'] == 'php') {
            $className = $namespace . pathinfo($fullPath)['filename'];
            $classPath = $classpath . $item;
            
            $classesMap[$className] = $classPath;
        }
        
        if (is_dir($fullPath) && !in_array($item, ['.', '..'])) {
            $subMap = scanFolder(
                $fullPath, 
                $namespace . $item . "\\", 
                $classpath . $item . '/'
            );
            
            $classesMap = $classesMap + $subMap;
        }
    }
    
    return $classesMap;
}

$classesMap = [];
$classesMap = scanFolder(
    $_SERVER['DOCUMENT_ROOT'] . '/local/modules/inwebium.module/classes', 
    "Inwebium\\Module\\",
    'classes/'
);

Bitrix\Main\Loader::registerAutoLoadClasses(
    'inwebium.module', 
    $classesMap
    );