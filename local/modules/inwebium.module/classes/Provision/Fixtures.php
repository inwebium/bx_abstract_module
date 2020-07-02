<?php
namespace Inwebium\Module\Provision;

class Fixtures
{
    /**
     * @var string Путь до папки install модуля
     */
    private $installerPath;
    private $sectionIdsMap;
    
    public function __construct($installerPath)
    {
        $this->setInstallerPath($installerPath);
        $this->sectionIdsMap = [];
    }

    public function getInstallerPath()
    {
        return $this->installerPath;
    }

    public function setInstallerPath($installerPath)
    {
        $this->installerPath = $installerPath;
        return $this;
    }

    public function load()
    {
        $this->loadHlblockElements()
            ->loadIblockSections()
            ->loadIblockElements();
    }
    
    private function loadIblockSections()
    {
        $iblocks = json_decode(file_get_contents($this->getInstallerPath() . "/fixtures/iblock.json"), true);
        
        foreach ($iblocks as $iblockCode => $iblockItems) {
            $iblockId = $this->getIblockId($iblockCode);
            $this->addIblockSections($iblockItems['SECTIONS'], 0, $iblockId);
        }
        
        return $this;
    }
    
    private function addIblockSections($sections, $parentId, $iblockId)
    {
        $newIblockSection = new \CIBlockSection;
        
        foreach ($sections as $key => $sectionFixture) {
            $children = [];
            
            if (!empty($sectionFixture['SECTIONS'])) {
                $children = $sectionFixture['SECTIONS'];
                unset($sectionFixture['SECTIONS']);
            }
            
            if ($parentId > 0) {
                $sectionFixture['IBLOCK_SECTION_ID'] = $parentId;
            }
            
            $sectionFixture['ACTIVE'] = 'Y';
            $sectionFixture['IBLOCK_ID'] = $iblockId;
            
            $newSectionId = $newIblockSection->Add($sectionFixture);
            $this->sectionIdsMap[$sectionFixture['XML_ID']] = $newSectionId;
            
            if ($newSectionId > 0 && count($children) > 0) {
                $this->addIblockSections($children, $newSectionId, $iblockId);
            }
        }
    }

    private function loadIblockElements()
    {
        $iblocks = json_decode(file_get_contents($this->getInstallerPath() . "/fixtures/iblock.json"), true);
        
        $newIblockElement = new \CIBlockElement;
        
        foreach ($iblocks as $iblockCode => $iblockItems) {
            $iblockId = $this->getIblockId($iblockCode);
            
            foreach ($iblockItems['ELEMENTS'] as $iblockItem) {
                if (!empty($iblockItem['SECTIONS'])) {
                    foreach ($iblockItem['SECTIONS'] as $key => $sectionXmlId) {
                        $iblockItem['IBLOCK_SECTION'][] = $this->sectionIdsMap[$sectionXmlId];
                    }
                    
                    unset($iblockItem['SECTIONS']);
                } else {
                    $iblockItem['IBLOCK_SECTION_ID'] = false;
                }
                
                $iblockItem['ACTIVE'] = 'Y';
                $iblockItem['IBLOCK_ID'] = $iblockId;
                
                $newElementId = $newIblockElement->Add($iblockItem);
                
                \CCatalogProduct::Add([
                    'ID' => $newElementId,
                    'QUANTITY' => $iblockItem['STORE_AMOUNT'],
                    'AVAILABLE' => $iblockItem['AVAILABLE'],
                    'CAN_BUY_ZERO' => 'N'
                ]);
                
                \CPrice::Add([
                    'PRODUCT_ID' => $newElementId,
                    'CATALOG_GROUP_ID' => 1,
                    'PRICE' => $iblockItem['PRICE'],
                    'CURRENCY' => 'RUB',
                ]);
            }
        }
        
        return $this;
    }

    private function loadHlblockElements()
    {
        $hlblocksItems = json_decode(file_get_contents($this->getInstallerPath() . "/fixtures/hlblock.json"), true);
        
        foreach ($hlblocksItems as $hlblockName => $hlblockItems) {
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
                    'filter' => ['=NAME' => $hlblockName]
                ])->fetch();
            $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock['ID']);
            $entityDataClass = $entity->getDataClass();
            
            if ($hlblock) {
                foreach ($hlblockItems as $hlblockItem) {
                    $entityDataClass::add($hlblockItem);
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Метод вернет id инфоблока по его символьному коду
     * 
     * @param string $iblockCode Символьный код инфоблока
     * @return int ID инфоблока
     */
    private function getIblockId($iblockCode)
    {
        $order = ['order' => 'asc', 'id' => 'desc'];
        $filter = [
            'CODE' => $iblockCode
        ];
        
        $dbResult = \CIBlock::GetList(
            $order,
            $filter,
            false
        );
        
        if ($arIblock = $dbResult->GetNext())
        {
            return intval($arIblock['ID']);
        }
        else
        {
            return 0;
        }
    }
}