<?php
namespace Inwebium\Module\Provision;

class Model
{
    /**
     * @var string Путь до папки install модуля
     */
    private $installerPath;
    
    public function __construct($installerPath)
    {
        $this->setInstallerPath($installerPath);
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

    /**
     * Создаст тип инфоблоков
     * 
     * @global CDatabase $DB
     * @global CCacheManager $CACHE_MANAGER
     * @return $this
     */
    public function createIblockType()
    {
        global $DB, $CACHE_MANAGER;
        
        $CACHE_MANAGER->cleanDir("b_iblock_type");
        $resIblockType = \CIBlockType::GetByID('inwebium');
        
        if ($resIblockType->GetNext()) {
            echo "\nNOTICE (CreateIblockType): IBlockType inwebium already exists\n\n";
        } else {
            $newIblockType = new \CIBlockType;
            $arIblockTypeFields = [
                'ID' => 'inwebium',
                'SECTIONS' => 'Y',
                'IN_RSS' => 'N',
                'SORT' => 1,
                'LANG' => [
                    'ru' => [
                        'NAME' => 'Inwebium',
                        'SECTION_NAME' => 'Разделы',
                        'ELEMENT_NAME' => 'Товары',
                    ],
                    'en' => [
                        'NAME' => 'Inwebium',
                        'SECTION_NAME' => 'Sections',
                        'ELEMENT_NAME' => 'Products',
                    ],
                ],
            ];

            $DB->StartTransaction();

            $result = $newIblockType->Add($arIblockTypeFields);

            if (!$result) {
                $DB->Rollback();
                echo "\nERROR (CreateIblockType): " . $newIblockType->LAST_ERROR . "\n\n";
                die();
            } else {
                $DB->Commit();
                $CACHE_MANAGER->cleanDir("b_iblock_type");
            }
        }
        
        return $this;
    }
    
    /**
     * Создаст инфоблоки из конфига install/conf/iblock.json
     * 
     * @global CDatabase $DB
     */
    public function createIblocks()
    {
        global $DB;
        $arIblocksParams = json_decode(file_get_contents($this->getInstallerPath() . "/conf/iblock.json"), true);

        foreach ($arIblocksParams as $iblockCode => $iblockParams) {
            $newIblock = new \CIBlock;
            $iblockParams['CODE'] = $iblockCode;
            
            if (empty($iblockParams['IBLOCK_TYPE_ID'])) {
                $iblockParams['IBLOCK_TYPE_ID'] = 'inwebium';
            }

            $arPermissions = $iblockParams['PERMISSIONS'];
            unset($iblockParams['PERMISSIONS']);
            $isCatalog = $iblockParams['IS_CATALOG'];
            unset($iblockParams['IS_CATALOG']);

            $DB->StartTransaction();

            $newIblockId = $newIblock->Add($iblockParams);

            if (!$newIblockId) {
                $DB->Rollback();
                echo "\nERROR (CreateIblocks, IB code = " . $iblockParams['CODE'] . "): " . $newIblock->LAST_ERROR . "\n\n";
            } else {
                $DB->Commit();
                \CIBlock::SetPermission($newIblockId, $arPermissions);

                if ($isCatalog) {
					$catalogAddResult = \CCatalog::Add(['IBLOCK_ID' => $newIblockId]);

					if (!$catalogAddResult) {
					    if ($ex = $APPLICATION->GetException()) {
					        echo "\nERROR (CreateIblocks - AddCatalog, IB code = " . $iblockParams['CODE'] . "): " . $strError . "\n\n";
					    }
					}
                }
            }
        }
        
        return $this;
    }

    /**
     * Создаст HL-блоки из конфига install/conf/hlblock.json
     * 
     * @global CDatabase $DB
     */
    public function createHlBlocks()
    {
        global $DB;
        $hlBlocksParams = json_decode(file_get_contents($this->getInstallerPath() . "/conf/hlblock.json"), true);

        foreach ($hlBlocksParams as $hlblockTableName => $hlblockParams) {
            $hlblockFields = $hlblockParams['FIELDS'];
            unset($hlblockParams['FIELDS']);
            $hlblockParams['TABLE_NAME'] = $hlblockTableName;
            //создание hl-блока
            $result = \Bitrix\Highloadblock\HighloadBlockTable::add($hlblockParams);
            
            if (!$result->isSuccess()) {
                echo "\nERROR (CreateHlBlocks) " . implode('; ', $result->getErrorMessages());
            } else {
                $this->createHlblockFields($result->getId(), $hlblockFields);
            }
        }
        
        return $this;
    }

    /**
     * Создаст свойства инфоблоков из конфига conf/property.json
     * 
     * @global CDatabase $DB
     */
    public function createProperties()
    {
        global $DB;
        $arIblocksProperties = json_decode(file_get_contents($this->getInstallerPath() . "/conf/property.json"), true);

        foreach ($arIblocksProperties as $iblockCode => $arProperties) {
            $iblockId = $this->getIblockId($iblockCode);

            if (!$iblockId) {
                echo "\nERROR (CreateProperties): No Id for Iblock " . $iblockCode . "\n\n";
                continue;
            }
            
            foreach ($arProperties as $propertyCode => $arProperty) {
                
                if (!isset($arProperty['PROPERTY_TYPE']) && !isset($arProperty['NAME'])) {
                    continue;
                }
            
                $newProperty = new \CIBlockProperty;
                $arProperty['CODE'] = $propertyCode;
                $arProperty['IBLOCK_ID'] = $iblockId;
                
                $DB->StartTransaction();

                $result = $newProperty->Add($arProperty);

                if (!$result) {
                    $DB->Rollback();
                    echo "\nERROR (CreateProperties): " . $newProperty->LAST_ERROR . "\n\n";
                } else {
                    $DB->Commit();
                }
            }
        }
        
        return $this;
    }

    private function createHlblockFields($hlblockId, $fields)
    {
        global $DB, $APPLICATION;
        $newUserField = new \CUserTypeEntity();

        foreach ($fields as $fieldName => $fieldParams) {
            $fieldParams['ENTITY_ID'] = 'HLBLOCK_' . $hlblockId;
            $fieldParams['FIELD_NAME'] = $fieldName;

            $DB->StartTransaction();

            $result = $newUserField->Add($fieldParams);

            if (!$result)
            {
                $DB->Rollback();
                
                if( $ex = $APPLICATION->GetException()) {
                    $strError = $ex->GetString();
                    echo "\nERROR (CreateHlblockFields) " . $strError;
                }
            }
            else
            {
                $DB->Commit();
            }
        }
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