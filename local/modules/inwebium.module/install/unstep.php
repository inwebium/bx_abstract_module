<?php
if (!check_bitrix_sessid())
{
    return;
}
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
echo CAdminMessage::ShowNote(GetMessage("MODULE_UNINSTALL_OK"));