<?php
//запись в лог
function writeToLog($data, $logFile = "/logs/event_log.txt") {
$date = date("Y-m-d H:i:s"); // Получаем текущее время
    $logMessage = "[$date] "; // Начало строки лога

    if (is_array($data) || is_object($data)) {
        $logMessage .= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $logMessage .= $data;
    }

    $logMessage .= PHP_EOL; // Добавляем перенос строки

    $filePath = $_SERVER["DOCUMENT_ROOT"] . $logFile; // Полный путь к файлу

    // Создаём директорию, если её нет
    if (!file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    // Записываем сообщение в файл
    file_put_contents($filePath, $logMessage, FILE_APPEND | LOCK_EX);
}
//--------------------------------------------------------------------------------------------
//дебаг
function dp($array){

    global $USER;
    if($USER->IsAdmin()) {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }
}

//----------------------------------------------------------------------------------------------------------------------
function debugConsole($array){
    global $USER;
    if($USER->IsAdmin()) {
        echo "<script>console.log(" . json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ");</script>";
    }
}

//----------------------------------------------------------------------------------------------------------------------
//редирект для строчных букв

$pos      = strpos($_SERVER['REQUEST_URI'], '/bitrix/');
if ($pos === false) {
    $parts_url = explode("?", $_SERVER['REQUEST_URI']);
    $parts_url_0= $parts_url[0]; // кусок1
    $parts_url_1= $parts_url[1]; // кусок2

    if ( $parts_url_0 != strtolower( $parts_url_0) ) {
        if(empty($parts_url_1)){
            header('Location: https://'.$_SERVER['HTTP_HOST'] .
                strtolower($parts_url_0), true, 301);
        }else{
            header('Location: https://'.$_SERVER['HTTP_HOST'] .
                strtolower($parts_url_0).'?'.$parts_url_1, true, 301);
        }
        exit();
    }
}

//---------------------------------------------------------------------------------------------------------------------
//редирикт в нижний регистр, с учетом исключений

// Массив исключений, в котором можно добавлять пути, которые не должны подвергаться редиректу
$excludePaths = [
    '/bitrix/',      // Путь для Битрикса
    '/ajax/',        // Путь для файлов в /ajax/
    '/upload/',      // Путь для загрузок (можно добавить другие директории)
    // Добавляй сюда другие пути, если нужно
];

$shouldRedirect = true; // Флаг, который определяет, нужно ли делать редирект

// Проверяем, если в запросе есть один из исключённых путей
foreach ($excludePaths as $excludePath) {
    if (strpos($_SERVER['REQUEST_URI'], $excludePath) !== false) {
        $shouldRedirect = false; // Не делаем редирект для исключений
        break;
    }
}

// Если редирект не исключён, проверяем и выполняем редирект
if ($shouldRedirect) {
    $parts_url = explode("?", $_SERVER['REQUEST_URI']);
    $parts_url_0 = $parts_url[0]; // кусок1
    $parts_url_1 = isset($parts_url[1]) ? $parts_url[1] : ''; // кусок2

    if ($parts_url_0 != strtolower($parts_url_0)) {
        if (empty($parts_url_1)) {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . strtolower($parts_url_0), true, 301);
        } else {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . strtolower($parts_url_0) . '?' . $parts_url_1, true, 301);
        }
        exit();
    }
}

//---------------------------------------------------------------------------------------------------------------------

//версификация для загрузки css и js 

function assetWithVersion($path) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($fullPath)) {
        return $path . '?v=' . filemtime($fullPath);
    }
    return $path;
}


//кэширование контактов организации
use Bitrix\Main\Loader;
Loader::includeModule('iblock');

$cache = \Bitrix\Main\Data\Cache::createInstance();

if ($cache->initCache(43200, 'contact_info_element_67')) {
    $vars = $cache->getVars();
    $GLOBALS['CONTACT_DATA'] = $vars['DATA'];
} elseif ($cache->startDataCache()) {

    $contactData = [];
    $elementId = 67;
    $iblockId = 6;

    $res = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            'ID' => $elementId,
            'ACTIVE' => 'Y'
        ],
        false,
        false,
        [
            'ID',
            'NAME',
            'PROPERTY_ADDRESS',
            'PROPERTY_PHONE_MAIN',
            'PROPERTY_SCHEDULE',
            'PROPERTY_MAP_COORD',
            // без PROPERTY_EMPLOYEES_LIST_CONTACTS, потому что его отдельно
        ]
    );

    if ($item = $res->GetNext()) {
        $contactData = [
            'ADDRESS' => $item['PROPERTY_ADDRESS_VALUE'],
            'PHONE_MAIN' => $item['PROPERTY_PHONE_MAIN_VALUE'],
            'SCHEDULE' => $item['PROPERTY_SCHEDULE_VALUE'],
            'EMPLOYEES_LIST_CONTACTS' => [],
            'MAP_COORD' => $item['PROPERTY_MAP_COORD_VALUE']
        ];

        // Теперь тянем множественное поле отдельно
        $propertyRes = CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            [],
            ['CODE' => 'EMPLOYEES_LIST_CONTACTS']
        );

        while ($prop = $propertyRes->Fetch()) {
            if ($prop['VALUE']) {
                $contactData['EMPLOYEES_LIST_CONTACTS'][] = (int)$prop['VALUE'];
            }
        }

        $GLOBALS['CONTACT_DATA'] = $contactData;
        $cache->endDataCache(['DATA' => $contactData]);
    } else {
        $GLOBALS['CONTACT_DATA'] = [];
        $cache->abortDataCache();
    }
}

//----------------------------------------------------------------------------------------------------------------------------------------------------
function debugConsole($array){
    global $USER;
    if($USER->IsAdmin()) {
        echo "<script>console.log(" . json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ");</script>";
    }
}

//----------------------------------------------------------------------------------------------------------------------------------------------------

//првоерка координат
function getSafeCoordinates($targetCoords, $defaultCoords = [37.617644, 55.755819]) {
    if (!is_string($targetCoords)) {
        return $defaultCoords;
    }

    $parts = array_map('trim', explode(',', $targetCoords));

    if (count($parts) !== 2) {
        return $defaultCoords;
    }

    $lng = floatval($parts[0]);
    $lat = floatval($parts[1]);

    $isValidLat = is_finite($lat) && $lat >= -90 && $lat <= 90;
    $isValidLng = is_finite($lng) && $lng >= -180 && $lng <= 180;

    return ($isValidLat && $isValidLng) ? [$lng, $lat] : $defaultCoords;
}



