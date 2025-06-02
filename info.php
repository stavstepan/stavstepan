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
