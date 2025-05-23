<?php
/*
Итак, у тебя задача, надо положить в письмо дополнительные данные о товарах. У тебя шаблон Intec и в заказе не так много данных. Что мы делаем? Перехватываем письмо по событию и добавляем туда нужные данные.
вот шаблон:
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=windows-1251"/>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <table cellpadding="0" cellspacing="0" width="850" style="background-color: #d1d1d1; border-radius: 2px; border:1px solid #d1d1d1; margin: 0 auto;" border="1" bordercolor="#d1d1d1">
        <tr>
            <td height="83" width="850" bgcolor="#eaf3f5" style="border: none; padding-top: 23px; padding-right: 17px; padding-bottom: 24px; padding-left: 17px;">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td bgcolor="#ffffff" height="75" style="font-weight: bold; text-align: center; font-size: 26px; color: #0b3961;">Оформлен заказ в магазине #SITE_NAME#</td>
                    </tr>
                    <tr>
                        <td bgcolor="#bad3df" height="11"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td width="850" bgcolor="#f7f7f7" valign="top" style="border: none; padding-top: 0; padding-right: 44px; padding-bottom: 16px; padding-left: 44px;">
                <p style="margin-top: 0; margin-bottom: 20px; line-height: 20px;">Номер заказа #ORDER_ID#.<br />
                Стоимость заказа: #ORDER_AMOUNT#.<br />
                <br />
                Состав заказа:<br />
                <table>
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Количество</th>
                            <th>Артикул Excel</th> <!-- Столбец с артикулом -->
                            <th>Фото</th>
                        </tr>
                    </thead>
                    <tbody>
                        #ORDER_TABLE_ITEMS# <!-- Здесь будет информация о заказе с артикулом -->
                    </tbody>
                </table>
                <br />
                Стоимость доставки: #ORDER_DELIVERY#.<br />
                <br />
                Способ оплаты: #ORDER_PAYMENT#.<br />
                <br />
                Свойства заказа:<br />
                #STARTSHOP_ORDER_PROPERTY#<br />
                <br />
            </td>
        </tr>
    </table>
</body>
</html>

вот твои поля для события
#ORDER_ID# - Номер заказа
#ORDER_AMOUNT# - Сумма заказа
#STARTSHOP_SHOP_EMAIL# - Электронная почта магазина из настроек сайта
#STARTSHOP_ORDER_LIST# - Состав заказа
#STARTSHOP_ORDER_PROPERTY# - Свойства заказа
#ORDER_DELIVERY# - стоимость доставки
#ORDER_PAYMENT# - способ оплаты
#ORDER_TABLE_ITEMS# - пользовательские поля


вот перехватчик:
просто положи его в init.php поставь свой инфоблок и все готово )


Loader::includeModule('iblock');

class CEventLogger
{
    protected static function BuildCustomOrderTable($arFields)
    {
        $orderId = (int)$arFields["ORDER_ID"];

        // Запросим товары из заказа по ID заказа
        $connection = \Bitrix\Main\Application::getConnection();
        $query = "SELECT * FROM startshop_order_items WHERE `ORDER` = " . $orderId;
        $result = $connection->query($query);

        $products = [];
        $productIds = [];

        while ($item = $result->fetch()) {
            $productId = (int)$item["ITEM"];
            $quantity = (int)$item["QUANTITY"];

            $products[$productId] = [
                'QUANTITY' => $quantity
            ];

            $productIds[] = $productId;
        }

        // Получаем названия и артикулы товаров из инфоблока 15
        $res = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 15,
                'ID' => $productIds
            ],
            false,
            false,
            ['ID', 'NAME', 'PROPERTY_ARTICLE_EXCEL', 'PREVIEW_PICTURE']
        );

        while ($item = $res->GetNext()) {
            $productId = (int)$item['ID'];
            $imageSrc = '';
            if (!empty($item['PREVIEW_PICTURE'])) {
                $image = CFile::ResizeImageGet($item['PREVIEW_PICTURE'], ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_PROPORTIONAL, true);
                $imageSrc = $image['src'];
            }
            $products[$productId]['NAME'] = $item['NAME'];
            $products[$productId]['ARTICLE'] = $item['PROPERTY_ARTICLE_EXCEL_VALUE'];
            $products[$productId]['IMAGE'] = $imageSrc;
        }

        // Формируем таблицу
        $html = '';
        foreach ($products as $productId => $data) {
            $name = htmlspecialchars($data['NAME'] ?? 'Неизвестно');
            $quantity = $data['QUANTITY'] ?? 0;
            $article = htmlspecialchars($data['ARTICLE'] ?? 'Не указан');

            $imageTag = !empty($data['IMAGE'])
                ? '<img src="https://' . $_SERVER['HTTP_HOST'] . $data['IMAGE'] . '" width="80" style="display:block;">'
                : '—';



            $html .= "<tr>
                <td>{$name}</td>
                <td>{$quantity}</td>
                <td>{$article}</td>
                <td>{$imageTag}</td>
            </tr>";
        }
        //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/email_debug.txt', $html);
        return $html;
    }

    public static function OnBeforeEventAdd(&$event, &$lid, &$arFields)
    {
        if ($event === 'STARTSHOP_NEW_ORDER_ADMIN') {
            $arFields['ORDER_TABLE_ITEMS'] = self::BuildCustomOrderTable($arFields);
        }
    }
}

AddEventHandler("main", "OnBeforeEventAdd", array("CEventLogger", "OnBeforeEventAdd"));

