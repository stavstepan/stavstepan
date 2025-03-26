<?php
/*
если тебе надо генерировать специальные артикулы для товаров.
можешь создать этот файл и запускать его через wget через крон.
что он делает?
создает уникальныый артикул товара используя первые три согласные буквы.
пример:
"прч-сст_хрн-1098" к примеру = прочее-системы_хранения-1098
количество вложенностей значчения не имеет

*/
$_SERVER['DOCUMENT_ROOT'] = '/home/d/dapsite6/grata.production/public_html';
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;

function getFirstThreeConsonants(string $text): string
{
    $consonants = '';
    $vowels = ['а', 'е', 'ё', 'и', 'о', 'у', 'ы', 'э', 'ю', 'я'];
    $text = mb_strtolower($text);
    for ($i = 0; $i < mb_strlen($text); $i++) {
        $char = mb_substr($text, $i, 1);
        if (!in_array($char, $vowels)) {
            $consonants .= $char;
            if (mb_strlen($consonants) === 3) {
                break;
            }
        }
    }
    return $consonants;
}

function generateCategoryCode(string $categoryName): string
{
    $words = explode(' ', $categoryName);
    $codeParts = [];
    $vowels = ['а', 'е', 'ё', 'и', 'о', 'у', 'ы', 'э', 'ю', 'я'];
    $excludedWords = ['и', 'в', 'во', 'на', 'с', 'со', 'по', 'к', 'у', 'о', 'об', 'от', 'за', 'для', 'из', 'при', 'без', 'над', 'под', 'про', 'через', 'между'];

    foreach ($words as $word) {
        $lowerWord = mb_strtolower($word);
        if (in_array($lowerWord, $excludedWords)) {
            continue;
        }

        $firstChar = mb_substr($word, 0, 1);
        if (in_array(mb_strtolower($firstChar), $vowels)) {
            // Если начинается с гласной — берём гласную + до 3 согласных
            $code = mb_strtolower($firstChar);
            $consonantCount = 0;
            for ($i = 1; $i < mb_strlen($word); $i++) {
                $char = mb_substr($word, $i, 1);
                if (!in_array(mb_strtolower($char), $vowels)) {
                    $code .= mb_strtolower($char);
                    $consonantCount++;
                    if ($consonantCount === 3) {
                        break;
                    }
                }
            }
            $codeParts[] = $code;
        } else {
            // Иначе берём первые 3 согласные
            $codeParts[] = getFirstThreeConsonants($word);
        }
    }

    return implode('_', array_filter($codeParts));
}



function updateArticlesForIblock($iblockId = 15) {
    if (!Loader::includeModule('iblock')) {
        file_put_contents(
            __DIR__ . '/update_articles_log.txt',
            "Модуль 'iblock' не подключен\n",
            FILE_APPEND
        );
        return;
    }

    $elements = CIBlockElement::GetList(
        [], // сортировка
        ['IBLOCK_ID' => $iblockId],
        false,
        false,
        ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'PROPERTY_ARTICLE']
    );

    while ($element = $elements->Fetch()) {
        $elementId = $element['ID'];
        $sectionId = $element['IBLOCK_SECTION_ID'];
        $productName = $element['NAME'];
        $currentArticle = $element['PROPERTY_ARTICLE_VALUE'];

        $nav = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME']);
        $categoryCodes = [];
        while ($section = $nav->Fetch()) {
            $categoryCodes[] = generateCategoryCode($section['NAME']);
        }

        $expectedArticle = implode('-', $categoryCodes) . '-' . $elementId;

        if ($currentArticle === $expectedArticle) {
            file_put_contents(
                __DIR__ . '/update_articles_log.txt',
                "Элемент '{$productName}' уже имеет правильный артикул: {$currentArticle}. Пропускаем.\n",
                FILE_APPEND
            );
            continue;
        }

        CIBlockElement::SetPropertyValuesEx(
            $elementId,
            $iblockId,
            ['ARTICLE' => $expectedArticle]
        );

        file_put_contents(
            __DIR__ . '/update_articles_log.txt',
            "Артикул для элемента '{$productName}' обновлен: {$expectedArticle}\n",
            FILE_APPEND
        );
    }

    file_put_contents(
        __DIR__ . '/update_articles_log.txt',
        "Обновление завершено.\n",
        FILE_APPEND
    );
}

updateArticlesForIblock(15);
