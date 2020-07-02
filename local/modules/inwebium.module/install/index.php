<?php
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(class_exists("inwebium_module")) {
	return;
}

class inwebium_module extends \CModule
{
	var $MODULE_ID = "inwebium.module";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "N";
    var $PARTNER_NAME;
    var $PARTNER_URI;
    
   	public function __construct()
	{
		$arModuleVersion = array();

		include($this->GetPath() . "/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = "InwebiumModule";
		$this->MODULE_DESCRIPTION = "Test";
        
        $this->PARTNER_NAME = 'Inwebium';
        $this->PARTNER_URI = 'https://www.example.com/';
	}

	function DoInstall(bool $isShell = false)
	{
		global $DOCUMENT_ROOT, $APPLICATION;

		$this->InstallFiles();
		$this->InstallDB();
        //$this->fillFixtures();
        $this->InstallEvents();

        if (!$isShell)
        {
            $APPLICATION->IncludeAdminFile(GetMessage("INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/local/modules/" . $this->MODULE_ID . "/install/step.php");
        }
	}
    
    /**
     * Копирование нужных файлов (компоненты, другие нужные файлы, 
     * может .js или какие-нибудь php-библиотеки)
     * 
     * @return boolean
     */
	function InstallFiles()
	{
        // Копирование компонентов
        CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/install/components",
			$_SERVER["DOCUMENT_ROOT"] . "/local/components",
			true, true
		);
        
        // Копирование страниц
        CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/install/pages",
			$_SERVER["DOCUMENT_ROOT"] . "",
			true, true
		);
        
		return true;
	}
	
    /**
     * Регистрация модуля, создание инфоблоков, свойств, пользовательских полей и прочие действия с БД
     * 
     * @return boolean
     */
	function InstallDB()
	{
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("catalog");
        \CModule::IncludeModule('highloadblock');
		RegisterModule($this->MODULE_ID);

        Bitrix\Main\Loader::registerAutoLoadClasses(
            null, [
                'Inwebium\Module\Provision\Model' => '/local/modules/inwebium.module/classes/Provision/Model.php',
                'Inwebium\Module\Provision\Fixtures' => '/local/modules/inwebium.module/classes/Provision/Fixtures.php',
            ]
        );
        
        $model = new \Inwebium\Module\Provision\Model($this->GetPath());
        
        $model
            ->createHlBlocks()
            ->createIblockType()
            ->createIblocks()
            ->createProperties()
        ;
        
        $fixtures = new \Inwebium\Module\Provision\Fixtures($this->GetPath());
        
        $fixtures->load();
        
		return true;
	}
	
    /**
     * Регистрация обработчиков событий (например "перед выводом пролога", "после сохранения элемента")
     * 
     * @return boolean
     */
	function InstallEvents()
	{
		return true;
	}
	
	function DoUninstall(bool $isShell = false)
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		
		$this->UnInstallFiles();
		$this->UnInstallDB();
        $this->UnInstallEvents();
		
        if (!$isShell)
        {
            $APPLICATION->IncludeAdminFile(GetMessage("UNINSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/local/modules/" . $this->MODULE_ID . "/install/unstep.php");
        }
	}
	
    /**
     * Действия с БД при удалении модуля
     * 
     * @return boolean
     */
	function UnInstallDB()
	{
        global $DB;
        \CModule::IncludeModule("iblock");
        UnRegisterModule($this->MODULE_ID);

        $DB->StartTransaction();
        
        if(!\CIBlockType::Delete('inwebium'))
        {
            $DB->Rollback();
            echo "\nERROR: Failed to delete Iblock type = inwebium\n\n";
        }
        $DB->Commit();
        
		return true;
	}
	
    /**
     * Действия с файловой структурой при удалении модуля
     * 
     * @todo Добавить удаления сгенерированного репозитория
     * @return boolean
     */
	function UnInstallFiles()
	{
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/install/components",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components",
            []
        );
        
        // Копирование страниц
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/install/pages",
            $_SERVER["DOCUMENT_ROOT"] . "",
            []
        );
        
		return true;
	}
	
    /**
     * Удаление зарегистрированных обработчиков событий
     * 
     * @return boolean
     */
	function UnInstallEvents()
	{
		return true;
	}
    
    /**
     * Возвращает путь к текущему файлу
     * 
     * @return string Абсолютный путь к файлу
     */
    private function GetPath()
    {
        $path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
        return $path;
    }
}