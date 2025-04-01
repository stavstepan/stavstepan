<?php


/*  полезные команды в php командной строке битрикса*/




//найти все файлы за определенную дату
// Директория, в которой будет происходить поиск
$path = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intec.startshop/";

// Указываем дату: 13 марта 2025 года
$targetDate = '2025-03-13';
$targetTimestamp = strtotime($targetDate);

if ($targetTimestamp === false) {
    die("Неверный формат даты.\n");
}

$foundFiles = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

foreach ($files as $file) {
    if ($file->isFile() && preg_match('/\.(php)$/', $file->getFilename())) {
        $filePath = $file->getPathname();
        $filemtime = $file->getMTime();
        $filectime = $file->getCTime();

        $modifiedDate = date('Y-m-d', $filemtime);
        $createdDate = date('Y-m-d', $filectime);

        // Проверяем, был ли файл изменен или создан (по времени изменения inode) 13 марта
        if ($modifiedDate == $targetDate || $createdDate == $targetDate) {
            $foundFiles[] = $filePath;
        }
    }
}

echo "<pre>";
print_r($foundFiles);
echo "</pre>";

if (empty($foundFiles)) {
    echo "Не найдено PHP файлов в директории " . $path . ", которые были изменены или (по времени изменения inode) созданы 13 марта 2025 года.\n";
} else {
    echo "Найдены следующие PHP файлы в директории " . $path . ", которые были изменены или (по времени изменения inode) созданы 13 марта 2025 года:\n";
    foreach ($foundFiles as $filePath) {
        echo $filePath . "\n";
    }
}


//------------------------------------------------------------------------------

//найти все файлы измененные после определенной даты

// Директория, в которой будет происходить поиск
$path = $_SERVER["DOCUMENT_ROOT"] . "/";

// Указываем дату: 13 марта 2025 года
$targetDate = '2025-03-20';
$targetTimestamp = strtotime($targetDate);

if ($targetTimestamp === false) {
    die("Неверный формат даты.\n");
}

$foundFiles = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

foreach ($files as $file) {
    if ($file->isFile() && preg_match('/\.(php)$/', $file->getFilename())) {
        $filePath = $file->getPathname();
        $filemtime = $file->getMTime();
        $filectime = $file->getCTime();

        // Добавьте этот блок для отладки
        if (strpos($filePath, 'название_вашего_тестового_файла.php') !== false) {
            echo "Путь к файлу: " . $filePath . "\n";
            echo "Время изменения файла (timestamp): " . $filemtime . "\n";
            echo "Время создания файла (timestamp): " . $filectime . "\n";
            echo "Целевое время (timestamp): " . $targetTimestamp . "\n";
        }

        // Проверяем, был ли файл изменен или создан после целевой даты
        if ($filemtime > $targetTimestamp || $filectime > $targetTimestamp) {
            $foundFiles[] = $filePath;
        }
    }
}

echo "<pre>";
print_r($foundFiles);
echo "</pre>";

if (empty($foundFiles)) {
    echo "Не найдено PHP файлов в директории " . $path . ", которые были изменены или (по времени изменения inode) созданы после 13 марта 2025 года.\n";
} else {
    echo "Найдены следующие PHP файлы в директории " . $path . ", которые были изменены или (по времени изменения inode) созданы после 13 марта 2025 года:\n";
    foreach ($foundFiles as $filePath) {
        echo $filePath . "\n";
    }
}

//--------------------------------------------
//файлы и по дате и по паттерну

// Директория, в которой будет происходить поиск
$path = $_SERVER["DOCUMENT_ROOT"] . "/";

// Указываем дату: 13 марта 2025 года
$targetDate = '2025-03-20';
$targetTimestamp = strtotime($targetDate);

if ($targetTimestamp === false) {
    die("Неверный формат даты.\n");
}

$foundFilesByDate = [];
$foundFilesByBoth = [];
$patterns = [
    'eval(',
    'strrev(',
    '46esab', // Часть reversed 'base64'
    'edoced', // Часть reversed 'decode'
    'etalfnizg', // reversed 'gzinflate'
    'error_reporting(0);',
    'touch(__FILE__'
];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

// Сначала найдем файлы, измененные после указанной даты
foreach ($files as $file) {
    if ($file->isFile() && preg_match('/\.(php)$/', $file->getFilename())) {
        $filePath = $file->getPathname();
        $filemtime = $file->getMTime();
        $filectime = $file->getCTime();

        if ($filemtime > $targetTimestamp || $filectime > $targetTimestamp) {
            $foundFilesByDate[] = $filePath;
        }
    }
}

// Теперь проверим содержимое найденных по дате файлов на наличие паттернов
foreach ($foundFilesByDate as $filePath) {
    $fileContent = file_get_contents($filePath);
    if ($fileContent !== false) {
        foreach ($patterns as $pattern) {
            if (strpos($fileContent, $pattern) !== false) {
                $foundFilesByBoth[$filePath][] = $pattern;
                break; // Нашли хотя бы один паттерн, можно перейти к следующему файлу
            }
        }
    }
}

echo "<h2>PHP файлы, измененные или созданные после " . date('Y-m-d', $targetTimestamp) . " и содержащие подозрительные паттерны:</h2>";
echo "<pre>";
if (empty($foundFilesByBoth)) {
    echo "Не найдено файлов, удовлетворяющих обоим условиям.\n";
} else {
    print_r($foundFilesByBoth);
}
echo "</pre>";

//-------------
//файлф по дате и по паттерну отдельно

// Директория, в которой будет происходить поиск
$path = $_SERVER["DOCUMENT_ROOT"] . "/";

// Указываем дату: 13 марта 2025 года
$targetDate = '2025-03-20';
$targetTimestamp = strtotime($targetDate);

if ($targetTimestamp === false) {
    die("Неверный формат даты.\n");
}

$foundFilesByDate = [];
$foundFilesByPattern = [];
$patterns = [
    'eval(',
    'strrev(',
    '46esab', // Часть reversed 'base64'
    'edoced', // Часть reversed 'decode'
    'etalfnizg', // reversed 'gzinflate'
    'error_reporting(0);',
    'touch(__FILE__'
];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

foreach ($files as $file) {
    if ($file->isFile() && preg_match('/\.(php)$/', $file->getFilename())) {
        $filePath = $file->getPathname();
        $filemtime = $file->getMTime();
        $filectime = $file->getCTime();

        // Проверяем, был ли файл изменен или создан после целевой даты
        if ($filemtime > $targetTimestamp || $filectime > $targetTimestamp) {
            $foundFilesByDate[] = $filePath;
        }

        // Читаем содержимое файла для поиска паттернов
        $fileContent = file_get_contents($filePath);
        if ($fileContent !== false) {
            foreach ($patterns as $pattern) {
                if (strpos($fileContent, $pattern) !== false) {
                    $foundFilesByPattern[$filePath][] = $pattern;
                    break; // Нашли хотя бы один паттерн, можно перейти к следующему файлу
                }
            }
        }
    }
}

echo "<h2>PHP файлы, измененные или созданные после " . date('Y-m-d', $targetTimestamp) . ":</h2>";
echo "<pre>";
if (empty($foundFilesByDate)) {
    echo "Не найдено.\n";
} else {
    print_r($foundFilesByDate);
}
echo "</pre>";

echo "<h2>PHP файлы, содержащие подозрительные паттерны:</h2>";
echo "<pre>";
if (empty($foundFilesByPattern)) {
    echo "Не найдено.\n";
} else {
    print_r($foundFilesByPattern);
}
echo "</pre>";

