<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;
CModule::IncludeModule("iblock");
CModule::IncludeModule("form");


// Получаем все файлы из поля PREVIEW_PICTURE
$arrFiles = [];
if (!empty($_FILES["PREVIEW_PICTURE"]["name"][0])) {
    foreach ($_FILES["PREVIEW_PICTURE"]["name"] as $key => $name) {
        if ($_FILES["PREVIEW_PICTURE"]["error"][$key] == 0) {
            $arrFiles[] = CFile::MakeFileArray($_FILES["PREVIEW_PICTURE"]["tmp_name"][$key]);
        }
    }
}


// Подготовка данных для формы
$arValues = array(
    "form_text_74" => $_POST["form_text_74"],
    "form_text_75" => $_POST["form_text_75"],
    "form_textarea_76" => $_POST["form_textarea_76"],
);

// Раскладываем файлы по полям
if (count($arrFiles) > 0) {
    // Если есть файлы, то распределим их на поля form_file_78, form_file_86, form_file_87
    $arValues["form_file_78"] = isset($arrFiles[0]) ? $arrFiles[0] : null;  // Первый файл
    $arValues["form_file_86"] = isset($arrFiles[1]) ? $arrFiles[1] : null;  // Второй файл
    $arValues["form_file_87"] = isset($arrFiles[2]) ? $arrFiles[2] : null;  // Третий файл
}

// Создаём новый результат формы
if ($RESULT_ID = CFormResult::Add(13, $arValues)) {

    // Отправляем уведомление
    if (CFormResult::Mail($RESULT_ID)) {
        CFormCrm::AddLead(13, $RESULT_ID);
        echo "success";
    }
} else {
    writeToLog("Ошибка при добавлении формы: " . json_encode($arValues, JSON_UNESCAPED_UNICODE), "/logs/form_debug.txt");
}
?>
