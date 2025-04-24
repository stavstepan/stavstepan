<?php

//функция для добавления редактирования раздела напрямую с морды, кнопка добавляется только когда включен режим правки


    if ($USER->IsAdmin() && $APPLICATION->GetShowIncludeAreas()) {
        echo '<div class="section-edit-link" style="margin-bottom: 20px;">
            <a href="/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=12&ID=' . $section['ID'] . '&lang=ru" 
               target="_blank"
               style="display: inline-block; background: #ffc; padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; text-decoration: none; color: #000;">
                ✏️ Редактировать раздел
            </a>
        </div>';
    }

