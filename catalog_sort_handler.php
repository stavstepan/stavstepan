<?php

/*
в общем, если необходимо настроить сортировку в каталоге по вложенности в элементы с учетом сортировки разделов 
и у элементов тажке может быть разная глубика, то это решение работает с любой глубиной вложенности.
Битрикс использует лексикографическую сортировку.
создай доп поле - GLOBAL_SORT вида строка.
добавь этот файл
подключи этот файл
выбери его сортировку в вызове каталога
	"ELEMENT_SORT_FIELD" => "PROPERTY_GLOBAL_SORT", // Поле сортировки элементов
	"ELEMENT_SORT_ORDER" => "asc", // Порядок сортировки элементов


подключаем в /local/php_interface/init.php
require_once($_SERVER["DOCUMENT_ROOT"] . "/include/catalog_sort_handler.php");
*/


use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;

EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementAdd', ['CatalogSortHandler', 'onAfterElementModify']);
EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', ['CatalogSortHandler', 'onAfterElementModify']);
EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockSectionAdd', ['CatalogSortHandler', 'onAfterSectionModify']);
EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockSectionUpdate', ['CatalogSortHandler', 'onAfterSectionModify']);

class CatalogSortHandler
{
    const IBLOCK_ID = 30;
    const SORT_PROPERTY_CODE = 'GLOBAL_SORT';

    public static function onAfterElementModify(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] == self::IBLOCK_ID && $arFields['RESULT']) {
            self::recalculateGlobalSortForAll();
        }
    }

    public static function onAfterSectionModify(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] == self::IBLOCK_ID && $arFields['RESULT']) {
            self::recalculateGlobalSortForAll();
        }
    }

    public static function recalculateGlobalSortForAll()
    {
        if (!Loader::includeModule('iblock')) {
            return;
        }

        $maxDepth = self::getMaxSectionDepth();

        $elements = \CIBlockElement::GetList([], ['IBLOCK_ID' => self::IBLOCK_ID], false, false, ['ID', 'IBLOCK_SECTION_ID', 'SORT']);
        while ($element = $elements->GetNext()) {
            $navChain = \CIBlockSection::GetNavChain(self::IBLOCK_ID, $element['IBLOCK_SECTION_ID'], ['ID', 'SORT']);
            $sortParts = [];

            while ($nav = $navChain->GetNext()) {
                $sortParts[] = str_pad($nav['SORT'], 5, '0', STR_PAD_LEFT);
            }

            // Дополняем до максимальной глубины
            while (count($sortParts) < $maxDepth) {
                $sortParts[] = str_pad(0, 5, '0', STR_PAD_LEFT);
            }

            // Добавляем сортировку самого элемента
            $sortParts[] = str_pad($element['SORT'], 5, '0', STR_PAD_LEFT);

            $globalSortValue = implode('.', $sortParts);
            \CIBlockElement::SetPropertyValuesEx($element['ID'], self::IBLOCK_ID, [self::SORT_PROPERTY_CODE => $globalSortValue]);
        }
    }

    protected static function getMaxSectionDepth()
    {
        $maxDepth = 0;

        $sections = \CIBlockSection::GetList([], ['IBLOCK_ID' => self::IBLOCK_ID], false, ['DEPTH_LEVEL']);
        while ($section = $sections->GetNext()) {
            if ((int)$section['DEPTH_LEVEL'] > $maxDepth) {
                $maxDepth = (int)$section['DEPTH_LEVEL'];
            }
        }

        return $maxDepth;
    }
}
?>
