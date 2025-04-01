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
?>
