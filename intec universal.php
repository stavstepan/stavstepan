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
        writeToLog("🛠 Находимся в startshop_orders.php");

        if ($_REQUEST["action"] == "status" && isset($_REQUEST["ID"]) && isset($_REQUEST["STATUS"])) {
            $orderId = (int)$_REQUEST["ID"];
            $newStatus = (int)$_REQUEST["STATUS"];

            writeToLog("⚙️ Изменение статуса заказа ID: $orderId → Новый статус: $newStatus");

            // Получаем старый статус перед изменением
            $oldStatus = GetOldOrderStatus($orderId);
            if ($oldStatus === false) {
                writeToLog("❌ Ошибка: Не удалось получить старый статус заказа ID: $orderId.");
                return;
            }

            writeToLog("🧐 Старый статус заказа ID: $orderId → $oldStatus");

            // Если статус не изменился, пропускаем обработку
            if ($oldStatus === $newStatus) {
                writeToLog("⚠️ Статус заказа ID: $orderId не изменился. Пропускаем обработку.");
                return;
            }

            $connection = \Bitrix\Main\Application::getConnection();

            // Обновляем статус заказа
            $query = "UPDATE startshop_order SET STATUS = $newStatus WHERE ID = $orderId";
            writeToLog("📤 SQL-запрос на изменение статуса: $query");
            $connection->queryExecute($query);

            // Проверяем, изменился ли статус
            $checkQuery = "SELECT STATUS FROM startshop_order WHERE ID = $orderId";
            $checkResult = $connection->query($checkQuery)->fetch();
            if ($checkResult && $checkResult["STATUS"] == $newStatus) {
                writeToLog("✅ Статус заказа ID: $orderId успешно изменён на $newStatus");

                // Проверяем и обновляем DELIVERY и PAYMENT
                CheckAndUpdateOrderFields($orderId);

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
    writeToLog("🔎 Получаем старый статус заказа ID: $orderId");

    $connection = \Bitrix\Main\Application::getConnection();
    $sql = "SELECT STATUS FROM startshop_order WHERE ID = " . (int)$orderId;
    writeToLog("📥 SQL-запрос: $sql");

    $result = $connection->query($sql)->fetch();
    if ($result) {
        writeToLog("✅ Найден старый статус заказа ID: $orderId → " . $result["STATUS"]);
        return (int)$result["STATUS"];
    } else {
        writeToLog("⚠️ Ошибка: не удалось получить старый статус заказа ID: $orderId");
        return false;
    }
}

/**
 * Проверяет и обновляет DELIVERY и PAYMENT в заказе, если они пустые.
 */
function CheckAndUpdateOrderFields($orderId)
{
    writeToLog("🔎 Проверяем поля DELIVERY и PAYMENT для заказа ID: $orderId");

    $connection = \Bitrix\Main\Application::getConnection();
    $sql = "SELECT DELIVERY, PAYMENT FROM startshop_order WHERE ID = " . (int)$orderId;
    $result = $connection->query($sql)->fetch();

    if ($result) {
        $updateFields = [];
        if (empty($result["DELIVERY"])) {
            $updateFields[] = "DELIVERY = 1";
        }
        if (empty($result["PAYMENT"])) {
            $updateFields[] = "PAYMENT = 1";
        }

        if (!empty($updateFields)) {
            $updateQuery = "UPDATE startshop_order SET " . implode(", ", $updateFields) . " WHERE ID = " . (int)$orderId;
            writeToLog("📤 SQL-запрос на обновление DELIVERY и PAYMENT: $updateQuery");
            $connection->queryExecute($updateQuery);
        }
    }
}

/**
 * Логика обработки смены статуса
 */
function HandleOrderStatusChange($orderId, $oldStatus, $newStatus)
{
    writeToLog("🔄 Запускаем HandleOrderStatusChange() для заказа ID: $orderId. Был статус: $oldStatus, стал: $newStatus");

    $connection = \Bitrix\Main\Application::getConnection();
    $query = "SELECT * FROM startshop_order_items WHERE `ORDER` = " . (int)$orderId;
    writeToLog("📥 SQL-запрос на получение товаров заказа: $query");

    $basket = $connection->query($query);
    while ($basketItem = $basket->fetch()) {
        $productId = (int)$basketItem["ITEM"];
        $quantity = (float)$basketItem["QUANTITY"];

        writeToLog("🎯 Обрабатываем товар: ID = $productId, Количество = $quantity");

        if ($newStatus === 5) {
            writeToLog("📉 Списываем товар ID: $productId, количество: $quantity");
            UpdateProductQuantity($productId, $quantity, 15, false);
        } elseif ($oldStatus === 5 && $newStatus !== 5) {
            writeToLog("📈 Возвращаем товар ID: $productId, количество: $quantity");
            UpdateProductQuantity($productId, $quantity, 15, true);
        }
    }
}

/**
 * Обновление количества товара в инфоблоке
 */
function UpdateProductQuantity($productId, $quantity, $catalogId, $restore = false)
{
    writeToLog("🔄 Запускаем UpdateProductQuantity() для товара ID: $productId, количество: $quantity, действие: " . ($restore ? "ВОЗВРАТ" : "СПИСАНИЕ"));

    $res = CIBlockElement::GetProperty($catalogId, $productId, [], ["CODE" => "STARTSHOP_QUANTITY"]);
    if ($prop = $res->Fetch()) {
        $currentQuantity = (int)$prop["VALUE"];
        $newQuantity = $restore ? $currentQuantity + $quantity : max(0, $currentQuantity - $quantity);

        writeToLog("🛠 Обновляем STARTSHOP_QUANTITY для ID: $productId → $newQuantity");

        CIBlockElement::SetPropertyValuesEx($productId, false, ["STARTSHOP_QUANTITY" => $newQuantity]);

        $checkRes = CIBlockElement::GetProperty($catalogId, $productId, [], ["CODE" => "STARTSHOP_QUANTITY"])->Fetch();
        if ($checkRes && (int)$checkRes["VALUE"] === $newQuantity) {
            writeToLog("✅ Успешно обновлено количество товара ID: $productId, новое значение: $newQuantity");
        } else {
            writeToLog("❌ Ошибка обновления количества товара ID: $productId! Текущее значение: " . ($checkRes ? (int)$checkRes["VALUE"] : "NULL"));
        }
    }
}
