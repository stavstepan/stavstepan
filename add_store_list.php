<?php
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Types\BaseType;
use Bitrix\Main\UserField\Types\EnumType;

$eventManager = EventManager::getInstance();
$eventManager->addEventHandler(
    "main",
    "OnUserTypeBuildList",
    ['UserFieldStoreMultiselect', 'getUserTypeDescription']
);

class UserFieldStoreMultiselect extends EnumType
{
    public const USER_TYPE_ID = 'user_field_store_multiselect';

    // ОБЯЗАТЕЛЬНО: описание для регистрации пользовательского типа
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'CLASS_NAME'    => __CLASS__,
            'DESCRIPTION'   => 'Множественный выбор складов',
            'BASE_TYPE'     => \CUserTypeManager::BASE_TYPE_ENUM,
        ];
    }

    // Не обязательно, но пусть будет — используется в админке
    public static function getDescription(): array
    {
        return [
            'DESCRIPTION' => 'Множественный выбор складов',
            'BASE_TYPE'   => \CUserTypeManager::BASE_TYPE_ENUM,
        ];
    }

    /**
     * Источник значений для списка
     * Д7 сигнатура тут у EnumType совместима со старой
     */
    public static function getList(array $userField)
    {
        if (!Loader::includeModule('catalog')) {
            return false;
        }

        $rows = [];
        $dbr = \Bitrix\Catalog\StoreTable::getList([
            'order'  => ['SORT' => 'ASC', 'TITLE' => 'ASC'],
            'select' => ['ID','TITLE','ACTIVE'],
            'filter' => ['=ACTIVE' => 'Y'],
        ]);

        while ($ar = $dbr->fetch()) {
            $rows[] = [
                'ID'    => (string)$ar['ID'],
                'VALUE' => '['.$ar['ID'].'] '.$ar['TITLE'],
            ];
        }

        $res = new \CDBResult();
        $res->InitFromArray($rows);
        return $res;
    }

    /**
     * Настройки поля (СОВРЕМЕННАЯ сигнатура)
     */
    public static function getSettingsHtml($userField, ?array $additionalParameters, $varsFromForm): string
    {
        $html = parent::getSettingsHtml($userField, $additionalParameters, $varsFromForm);

        // Принудительно включим множественность.
        // Имя корня настроек лежит в additionalParameters['NAME'].
        $name = $additionalParameters['NAME'] ?? '';
        if ($name) {
            $html .= '<input type="hidden" name="'.htmlspecialcharsbx($name).'[MULTIPLE]" value="Y">';
        }

        return $html;
    }

    /**
     * Подготовка настроек (надёжнее зафиксировать MULTIPLE здесь)
     */
    public static function prepareSettings($userField): array
    {
        $settings = parent::prepareSettings($userField);
        $userField['MULTIPLE'] = 'Y'; // фиксируем множественность
        return $settings;
    }

    /**
     * HTML редактирования значения (СОВРЕМЕННАЯ сигнатура)
     */
    public static function getEditFormHtml($userField, ?array $additionalParameters): string
    {
        $userField['MULTIPLE'] = 'Y';
        return parent::getEditFormHtml($userField, $additionalParameters);
    }

    /**
     * HTML фильтра в админке (СОВРЕМЕННАЯ сигнатура)
     */
    public static function getFilterHtml($userField, ?array $additionalParameters): string
    {
        $userField['MULTIPLE'] = 'Y';
        return parent::getFilterHtml($userField, $additionalParameters);
    }

    /**
     * Отображение в списках админки (СОВРЕМЕННАЯ сигнатура)
     */
    public static function getAdminListViewHtml($userField, ?array $additionalParameters): string
    {
        $value = $additionalParameters['VALUE'] ?? $userField['VALUE'] ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }

        if (!Loader::includeModule('catalog')) {
            return '';
        }

        $storeNames = [];
        foreach ($value as $storeId) {
            $sid = (int)$storeId;
            if ($sid > 0) {
                $store = \Bitrix\Catalog\StoreTable::getById($sid)->fetch();
                if ($store) {
                    $storeNames[] = '['.$store['ID'].'] '.$store['TITLE'];
                }
            }
        }

        return htmlspecialcharsbx(implode(', ', $storeNames));
    }

    /**
     * Публичное текстовое представление
     */
    public static function getPublicText(array $userField): string
    {
        $value = $userField['VALUE'] ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }

        if (!Loader::includeModule('catalog')) {
            return '';
        }

        $storeNames = [];
        foreach ($value as $storeId) {
            $sid = (int)$storeId;
            if ($sid > 0) {
                $store = \Bitrix\Catalog\StoreTable::getById($sid)->fetch();
                if ($store) {
                    $storeNames[] = $store['TITLE'];
                }
            }
        }

        return implode(', ', $storeNames);
    }

    /**
     * Валидация
     */
    public static function checkFields(array $userField, $value): array
    {
        $errors = [];

        if ($userField["MANDATORY"] === "Y") {
            if (is_array($value)) {
                $value = array_filter($value, static fn($v) => (string)$v !== '');
                if (empty($value)) {
                    $errors[] = ["id"=>$userField["FIELD_NAME"], "text"=>GetMessage("USER_TYPE_ENUM_REQUIRED")];
                }
            } elseif ((string)$value === '') {
                $errors[] = ["id"=>$userField["FIELD_NAME"], "text"=>GetMessage("USER_TYPE_ENUM_REQUIRED")];
            }
        }

        return $errors;
    }

    /**
     * Подготовка значения к сохранению
     */
    public static function onBeforeSave($userField, $value)
    {
        if (is_array($value)) {
            $value = array_values(array_filter($value, static fn($v) => (string)$v !== ''));
            if (empty($value)) {
                return false;
            }
        }
        return $value;
    }
}

// Языковые сообщения
if (defined('LANGUAGE_ID') && LANGUAGE_ID === 'ru') {
    $MESS['USER_TYPE_ENUM_REQUIRED'] = 'Поле обязательно для заполнения';
}


/* ===== рабочий вариант ===== https://dzen.ru/a/ZH4ZyTmO5hTctC9C */

<?php

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler(
"main",
"OnUserTypeBuildList",
['UserFieldStoreId','getUserTypeDescription']
);

class UserFieldStoreId extends \Bitrix\Main\UserField\Types\EnumType
{
public const USER_TYPE_ID = 'user_field_store_id';

/**
* @return array
*/
public static function getDescription(): array
{
return [
'DESCRIPTION' => 'Выбор склада',
'BASE_TYPE' => CUserTypeManager::BASE_TYPE_ENUM,
];
}

/**
* @param array $userField
* @return bool|\CDBResult
*/
public static function getList(array $userField)
{

if (!\Bitrix\Main\Loader::includeModule('catalog')) {
return false;
}

$arReturn = [];

$dbr = \Bitrix\Catalog\StoreTable::getList([
'order' => [
'ID' => 'ASC'
],
'select' => [
'ID', 'TITLE'
]
]);
while ($ar = $dbr->Fetch()) {
$arReturn[] = [
'ID' => $ar['ID'],
'VALUE' => '[' . $ar['ID'] . '] ' . $ar['TITLE']
];
}

$res = new \CDBResult;
$res->InitFromArray($arReturn);
return $res;
}
}
