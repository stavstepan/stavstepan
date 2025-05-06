<?

/*если тебе надо закрыть общий доступ для сайта, и оставить для определенных пользователей:
1. открой общий доступ в главном модуле
2. создай /auth/index.php
3. добавь код (ниже)
4. добавь проверку на то что это не админ, что бы не было видно панели

простой шаблон
*/

//оберни вызов панели

global $USER;
if ($USER->IsAuthorized() && $USER->IsAdmin()) {
    $APPLICATION->ShowPanel();
}

?>

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
?>

<style>
.bx-authform {
    max-width: 400px;
    margin: 60px auto;
    padding: 30px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    font-family: Arial, sans-serif;
}
.bx-authform h3 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 22px;
}
.bx-authform .bx-authform-formgroup-container {
    margin-bottom: 15px;
}
.bx-authform .bx-authform-input-container input {
    width: 100%;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #ccc;
}
.bx-authform input.btn-primary {
    width: 100%;
    padding: 10px;
    font-weight: bold;
}
.auth-admin-link {
    text-align: center;
    margin-top: 20px;
}
.auth-admin-link a {
    display: inline-block;
    padding: 10px 20px;
    background: #f3f3f3;
    border: 1px solid #ccc;
    border-radius: 5px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s ease;
}
.auth-admin-link a:hover {
    background: #e2e2e2;
}
@media (max-width: 480px) {
    .bx-authform {
        margin: 20px;
        padding: 20px;
    }
}
</style>

<div class="bx-authform">

    <?php if ($arResult['ERRORS']): ?>
        <div class="alert alert-danger">
            <?php foreach ($arResult['ERRORS'] as $error) echo $error; ?>
        </div>
    <?php endif; ?>

    <h3><?= Loc::getMessage('MAIN_AUTH_FORM_HEADER'); ?></h3>

    <form name="<?= $arResult['FORM_ID'];?>" method="post" target="_top" action="<?= POST_FORM_ACTION_URI;?>">

        <input type="hidden" name="AUTH_FORM" value="Y">
        <input type="hidden" name="TYPE" value="AUTH">

        <div class="bx-authform-formgroup-container">
            <div class="bx-authform-label-container"><?= Loc::getMessage('MAIN_AUTH_FORM_FIELD_LOGIN');?></div>
            <div class="bx-authform-input-container">
                <input type="text" name="<?= $arResult['FIELDS']['login'];?>" maxlength="255" value="<?= htmlspecialcharsbx($arResult['LAST_LOGIN']); ?>" />
            </div>
        </div>

        <div class="bx-authform-formgroup-container">
            <div class="bx-authform-label-container"><?= Loc::getMessage('MAIN_AUTH_FORM_FIELD_PASS');?></div>
            <div class="bx-authform-input-container">
                <input type="password" name="<?= $arResult['FIELDS']['password'];?>" maxlength="255" autocomplete="off" />
            </div>
        </div>

        <?php if ($arResult['STORE_PASSWORD'] === 'Y'): ?>
            <div class="bx-authform-formgroup-container">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="<?= $arResult['FIELDS']['remember'];?>" value="Y" />
                        <?= Loc::getMessage('MAIN_AUTH_FORM_FIELD_REMEMBER'); ?>
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <div class="bx-authform-formgroup-container">
            <input type="submit" class="btn btn-primary" name="<?= $arResult['FIELDS']['action'];?>" value="<?= Loc::getMessage('MAIN_AUTH_FORM_FIELD_SUBMIT');?>" />
        </div>

    </form>

    <div class="auth-admin-link">
        <a href="/bitrix/admin.php?lang=ru">Вход для администратора</a>
    </div>
</div>

<script>
    <?php if ($arResult['LAST_LOGIN'] != ''): ?>
        try { document.forms["<?= $arResult['FORM_ID'];?>"].USER_PASSWORD.focus(); } catch(e) {}
    <?php else: ?>
        try { document.forms["<?= $arResult['FORM_ID'];?>"].USER_LOGIN.focus(); } catch(e) {}
    <?php endif; ?>
</script>



/* вызов компонента */
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Авторизация");



global $USER;
if ($USER->IsAuthorized()) {
    LocalRedirect("/"); // или на другую страницу: /personal/, /cabinet/ и т.д.
    exit();
} else {




$APPLICATION->IncludeComponent(
	"bitrix:main.auth.form",
	"user_auth",
	Array(
		"AUTH_FORGOT_PASSWORD_URL" => "",
		"AUTH_REGISTER_URL" => "",
		"AUTH_SUCCESS_URL" => "/"
	)
);

}




require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>





