/*
в php-командной строке запускаем
*/


<?php
// Подключаем модуль
CModule::IncludeModule("main");

global $DB;
$oUser = new CUser;

// === НАСТРОЙКИ ===
$batchSize   = 1000;   // сколько пользователей обрабатывать за один прогон
$startFromId = 0;      // ID, с которого начинаем (0 = с самого начала)
$dateFrom    = date('Y-m-d H:i:s', strtotime('-1 day')); // сутки назад
// =================

// SQL-запрос
$sql = "
    SELECT ID, LOGIN, EMAIL, NAME, LAST_NAME, BLOCKED, TIMESTAMP_X
    FROM b_user
    WHERE BLOCKED = 'Y'
      AND TIMESTAMP_X >= '".$DB->ForSql($dateFrom)."'
      AND ID > ".(int)$startFromId."
    ORDER BY ID ASC
    LIMIT ".(int)$batchSize."
";

$res = $DB->Query($sql);

echo "=== РАЗБЛОКИРОВКА ПОЛЬЗОВАТЕЛЕЙ (батч) ===\n";
echo "Размер батча: $batchSize\n";
echo "Начинаем с ID: ".($startFromId > 0 ? $startFromId : "любого")."\n";
echo "Период обновления: с $dateFrom\n";
echo "-----------------------------------------\n";

$found = false;
$count = 0;
$errors = 0;
$processedIds = [];

while ($arUser = $res->Fetch()) {
    $found = true;
    $processedIds[] = $arUser["ID"];

    echo "Разблокируем: ID {$arUser['ID']} ({$arUser['LOGIN']}, {$arUser['EMAIL']}) - ";

    if ($oUser->Update($arUser["ID"], ["BLOCKED" => "N"])) {
        $count++;
        echo "УСПЕШНО\n";
    } else {
        $errors++;
        echo "ОШИБКА: " . $oUser->LAST_ERROR . "\n";
    }
}

if (!$found) {
    echo "✓ Пользователей для разблокировки не найдено.\n";
} else {
    echo "-----------------------------------------\n";
    echo "ИТОГО ЭТОГО БАТЧА:\n";
    echo "Успешно разблокировано: $count\n";
    echo "Ошибок: $errors\n";
    echo "Всего обработано: " . ($count + $errors) . "\n";

    if (!empty($processedIds)) {
        echo "Первый обработанный ID: " . min($processedIds) . "\n";
        echo "Последний обработанный ID: " . max($processedIds) . "\n";
        echo "\n👉 Для следующего батча используйте:\n";
        echo '$startFromId = ' . max($processedIds) . ";\n";
    }
}
?>
