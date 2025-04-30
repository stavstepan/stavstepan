/*Еще раз по повод почты.
1. Проверяй smtp подключение при настройке Битрикс. Для яндекса используй 587 порт, предварительно проверь доступен он или нет: telnet smtp.yandex.ru 587 в консоли этого сервера.
2. Сделай отправку почты на свой ящик. Потому что если там будут ошибки подписи, то она сама себе не будет доставлена, а у тебя попадет в спам. 
3. В спаме проверь "свойства письма" - там будет реальный отправитель, возможно это будет почта сервера.
4. Проверь настройки DKIM и SPF также добавь DMARC1 : v=DMARC1; p=none; sp=none; adkim=s; aspf=s; rua=mailto:{почта куда будут приходить отчеты}; fo=1 - это не строгие настройки, но тебе будет приходить отчет, это полезно.
5. Если ты проверил порты, добавил все записи на хостинг, и все равно почта попадает в спам, проверь какой метод отправляет почту. Вероятно это "mail()", а это значит, что он не использует твои SMTP настройки на сайте.
6. У тебя нет Битрикс ВМ (потому как там можно прописать SMTP и оно работает).
7. Используй PHPMailer. Для этого скачай его с гита, разверни и просто положи в /local/php_interdace/

Вот файл send.php также добавь рядом файл .env.php - для хранения ключа доступа.
*/
<?php

defined("B_PROLOG_INCLUDED") || die();




require_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/PHPMailer-master/src/PHPMailer.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/PHPMailer-master/src/SMTP.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/PHPMailer-master/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendCustomEmail(array $arFields): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.yandex.ru';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'адрес отправителя';
        $config = include $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/.env.php";
        $mail->Password = $config['SMTP_PASSWORD'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587; // Обычно для SSL используется порт 465
        $mail->CharSet = 'UTF-8';


        $mail->setFrom('адрес отправителя', 'Правоведъ');
        $mail->addAddress($arFields['EMAIL_TO'] ?? 'адрес отправителя', $arFields['AUTHOR'] ?? '');

        $mail->isHTML(true);
        $mail->Subject = $arFields['FORM_PAGE'] ?? 'Сообщение с сайта';

        // Формируем тело письма с нужными полями
        $body = "Имя: " . ($arFields['AUTHOR'] ?? '-') . "<br>";
        $body .= "Телефон: " . ($arFields['AUTHOR_PHONE'] ?? '-') . "<br>";
        // Раскомментируй, если нужно
        // $body .= "E-mail: " . ($arFields['AUTHOR_EMAIL'] ?? '-') . "<br>";
        $body .= "Сообщение: " . nl2br($arFields['TEXT'] ?? '-') . "<br>";
        $body .= "Страница: " . ($arFields['FORM_PAGE'] ?? '-') . "<br>";
        $body .= "Раздел: " . ($arFields['FORM_SECTION'] ?? '-') . "<br>";
        $body .= "Тип формы: " . ($arFields['FORM_TYPE'] ?? '-');

        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $body)); // Текстовая версия

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $mail->ErrorInfo);
        return false;
    }
}


//содержание файла .env - вообще можешь туда положить все переменные

<?php
return [
    'SMTP_PASSWORD' => 'пароль от приложения если это Яндекс ',
];



//как подключить вызов функции?
//это весь файл component.php из шаблона
//как видишь, мы вызываем нашу функцию и передаем туда поля. Все работает, ура! )
/*---------------------------------------------*/

<?php
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
require_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/send.php";

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

$arResult["PARAMS_HASH"] = md5(serialize($arParams).$this->GetTemplateName());

$arParams["USE_CAPTCHA"] = (($arParams["USE_CAPTCHA"] != "N" && !$USER->IsAuthorized()) ? "Y" : "N");
$arParams["EVENT_NAME"] = trim($arParams["EVENT_NAME"]);
if($arParams["EVENT_NAME"] == '')
	$arParams["EVENT_NAME"] = "FEEDBACK_FORM";
$arParams["EMAIL_TO"] = trim($arParams["EMAIL_TO"]);
if($arParams["EMAIL_TO"] == '')
	$arParams["EMAIL_TO"] = COption::GetOptionString("main", "email_from");
$arParams["OK_TEXT"] = trim($arParams["OK_TEXT"]);
if($arParams["OK_TEXT"] == '')
	$arParams["OK_TEXT"] = GetMessage("MF_OK_MESSAGE");

if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["submit"] <> '' && (!isset($_POST["PARAMS_HASH"]) || $arResult["PARAMS_HASH"] === $_POST["PARAMS_HASH"]))
{
	$arResult["ERROR_MESSAGE"] = array();
	if(check_bitrix_sessid())
	{		
		if(empty($arResult["ERROR_MESSAGE"]))
		{
			$arFields = Array(
				"AUTHOR" => $_POST["user_name"],
				//"AUTHOR_EMAIL" => $_POST["user_email"],
				"EMAIL_TO" => $arParams["EMAIL_TO"],
				"TEXT" => $_POST["MESSAGE"],
				"AUTHOR_PHONE" => $_POST["user_phone"],
				"FORM_PAGE" => $_POST["FORM_PAGE"],
				"FORM_SECTION" => $_POST["FORM_SECTION"],
				"FORM_TYPE" => $_POST["FORM_TYPE"],
			);

			/***/
			$el = new CIBlockElement;
			$arLoadProductArray = Array(
				"IBLOCK_ID"      => $GLOBALS["codekeepers_block_id"]["requests_feedback_id"],
				"NAME"           => $_POST["user_name"],
				);
			$PRODUCT_ID = $el->Add($arLoadProductArray);
			CIBlockElement::SetPropertyValuesEx($PRODUCT_ID, $arLoadProductArray["IBLOCK_ID"], array("feedback_text" => $_POST["MESSAGE"]));
			CIBlockElement::SetPropertyValuesEx($PRODUCT_ID, $arLoadProductArray["IBLOCK_ID"], array("feedback_phone" => $_POST["user_phone"]));
			/***/

//			if(!empty($arParams["EVENT_MESSAGE_ID"]))
//			{
//				foreach($arParams["EVENT_MESSAGE_ID"] as $v)
//					if(intval($v) > 0)
//						CEvent::Send($arParams["EVENT_NAME"], SITE_ID, $arFields, "N", intval($v));
//			}
//			else
//				CEvent::Send($arParams["EVENT_NAME"], SITE_ID, $arFields);

//----------------------------------------------------------------
            if (sendCustomEmail($arFields)) {
                $arResult["OK_MESSAGE"] = $arParams["OK_TEXT"];
            } else {
                $arResult["ERROR_MESSAGE"][] = "Ошибка при отправке письма. Пожалуйста, попробуйте позже.";
            }

//----------------------------------------------------------------

			$_SESSION["MF_NAME"] = htmlspecialcharsbx($_POST["user_name"]);
			$_SESSION["MF_EMAIL"] = htmlspecialcharsbx($_POST["user_email"]);
		}
		
		$arResult["MESSAGE"] = htmlspecialcharsbx($_POST["MESSAGE"]);
		$arResult["AUTHOR_NAME"] = htmlspecialcharsbx($_POST["user_name"]);
	}
	else
		$arResult["ERROR_MESSAGE"][] = GetMessage("MF_SESS_EXP");
}
elseif($_REQUEST["success"] == $arResult["PARAMS_HASH"])
{
	$arResult["OK_MESSAGE"] = $arParams["OK_TEXT"];
}

$this->IncludeComponentTemplate();








