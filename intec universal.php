<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

Loader::includeModule("intec.startshop");
Loader::includeModule("iblock");

/**
 * Функция логирования событий
 */
function writeToLog($data, $logFile = "/logs/event_log.txt") {
    $date = date("Y-m-d H:i:s");
    $logMessage = "[$date] ";

    if (is_array($data) || is_object($data)) {
        $logMessage .= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $logMessage .= $data;
    }

    $logMessage .= PHP_EOL;

    $filePath = $_SERVER["DOCUMENT_ROOT"] . $logFile;

    if (!file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    file_put_contents($filePath, $logMessage, FILE_APPEND | LOCK_EX);
}

EventManager::getInstance()->addEventHandler("main", "OnBeforeProlog", "CheckOrderStatusChange");

function CheckOrderStatusChange()
{
    if ($_SERVER["SCRIPT_NAME"] == "/bitrix/admin/startshop_orders.php") {
        writeToLog("Находимся в startshop_orders.php");

        if ($_REQUEST["action"] == "status" && isset($_REQUEST["ID"]) && isset($_REQUEST["STATUS"])) {
            $orderId = (int)$_REQUEST["ID"];
            $newStatus = (int)$_REQUEST["STATUS"];

            writeToLog("Изменение статуса заказа ID: $orderId → Новый статус: $newStatus");

            // Получаем старый статус перед изменением
            $oldStatus = GetOldOrderStatus($orderId);
            writeToLog("Старый статус заказа ID: $orderId → $oldStatus");

            $connection = \Bitrix\Main\Application::getConnection();
            $query = "UPDATE startshop_order SET STATUS = $newStatus WHERE ID = $orderId";
            writeToLog("SQL-запрос на изменение статуса: $query");

            $connection->queryExecute($query);

            // Проверяем, изменился ли статус
            $checkQuery = "SELECT STATUS FROM startshop_order WHERE ID = $orderId";
            $checkResult = $connection->query($checkQuery)->fetch();
            if ($checkResult && $checkResult["STATUS"] == $newStatus) {
                writeToLog("Статус заказа ID: $orderId успешно изменён на $newStatus");

                // Вызываем обработчик смены статуса
                HandleOrderStatusChange($orderId, $oldStatus, $newStatus);
            } else {
                writeToLog("❌ Ошибка: статус заказа ID: $orderId не изменился!");
            }
        }
    }
}

/**
 * Получаем старый статус заказа
 */
function GetOldOrderStatus($orderId)
{
    writeToLog("Получаем старый статус заказа ID: $orderId");

    $connection = \Bitrix\Main\Application::getConnection();
    $sql = "SELECT STATUS FROM startshop_order WHERE ID = " . (int)$orderId;
    writeToLog("SQL-запрос: $sql");

    $result = $connection->query($sql)->fetch();
    if ($result) {
        writeToLog("Найден старый статус заказа ID: $orderId → " . $result["STATUS"]);
        return (int)$result["STATUS"];
    } else {
        writeToLog("❌ Ошибка: не удалось получить старый статус заказа ID: $orderId");
        return false;
    }
}

/**
 * Логика обработки смены статуса
 */
/**
 * Логика обработки смены статуса
 */
function HandleOrderStatusChange($orderId, $oldStatus, $newStatus)
{
    writeToLog("Запускаем HandleOrderStatusChange() для заказа ID: $orderId. Был статус: $oldStatus, стал: $newStatus");

    $connection = \Bitrix\Main\Application::getConnection();

    // Запрашиваем товары из таблицы startshop_order_items
    $query = "SELECT * FROM startshop_order_items WHERE `ORDER` = " . (int)$orderId;
    writeToLog("SQL-запрос на получение товаров заказа: $query");

    $basket = $connection->query($query);
    $foundItems = false;

    while ($basketItem = $basket->fetch()) {
        if (!$foundItems) {
            writeToLog("Найдены товары в заказе ID: $orderId");
            $foundItems = true;
        }

        $productId = (int)$basketItem["ITEM"];
        $quantity = (float)$basketItem["QUANTITY"];

        writeToLog("Обрабатываем товар: ID = $productId, Количество = $quantity");

        // Если заказ получил статус "Отгружен" (ID 5) — списываем товар
        if ($newStatus === 5) {
            writeToLog("Списываем товар ID: $productId, количество: $quantity");
            UpdateProductQuantity($productId, $quantity, 15, false);
        }
        // Если заказ был "Отгружен" (ID 5), а теперь стал любым другим — возвращаем товар
        elseif ($oldStatus === 5 && $newStatus !== 5) {
            writeToLog("Возвращаем товар ID: $productId, количество: $quantity");
            UpdateProductQuantity($productId, $quantity, 15, true);
        }
    }

    if (!$foundItems) {
        writeToLog("❌️ Ошибка: В заказе ID: $orderId не найдено товаров!");
    }
}


/**
 * Обновление количества товара в инфоблоке 15
 */
/**
 * Обновление количества товара в инфоблоке 15
 */
function UpdateProductQuantity($productId, $quantity, $catalogId, $restore = false)
{
    writeToLog("Запускаем UpdateProductQuantity() для товара ID: $productId, количество: $quantity, действие: " . ($restore ? "ВОЗВРАТ" : "СПИСАНИЕ"));

    // Получаем текущее количество через GetProperty
    $res = CIBlockElement::GetProperty($catalogId, $productId, [], ["CODE" => "STARTSHOP_QUANTITY"]);
    if ($prop = $res->Fetch()) {
        $currentQuantity = (int)$prop["VALUE"];
        $newQuantity = $restore ? $currentQuantity + $quantity : max(0, $currentQuantity - $quantity);

        writeToLog("Обновляем STARTSHOP_QUANTITY для ID: $productId → $newQuantity");

        // Очистка кэша инфоблока перед обновлением
        CIBlock::clearIblockTagCache($catalogId);

        // Устанавливаем новое значение свойства
        CIBlockElement::SetPropertyValuesEx($productId, false, ["STARTSHOP_QUANTITY" => $newQuantity]);

        // Ждем немного, чтобы изменения отразились
        usleep(50000); // 50 мс (можно увеличить до 100 мс, если потребуется)

        // Перепроверяем, обновилось ли значение
        $checkRes = CIBlockElement::GetProperty($catalogId, $productId, [], ["CODE" => "STARTSHOP_QUANTITY"])->Fetch();
        if ($checkRes && (int)$checkRes["VALUE"] === $newQuantity) {
            writeToLog("Успешно обновлено количество товара ID: $productId, новое значение: $newQuantity");
        } else {
            writeToLog("Ошибка: количество товара ID: $productId не обновилось! Текущее значение в БД: " . ($checkRes ? (int)$checkRes["VALUE"] : "NULL"));
        }
    } else {
        writeToLog("Ошибка: не удалось получить свойство STARTSHOP_QUANTITY для ID: $productId. Проверь код свойства и активность!");
    }
}
