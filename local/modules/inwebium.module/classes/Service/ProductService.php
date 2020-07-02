<?php
namespace Inwebium\Module\Service;

class ProductService
{
	private $iblockId;

    /**
     * Устанавливает Id инфоблока с товарами
     */
	public function __construct()
	{
        \CModule::IncludeModule("iblock");
		$order = ['order' => 'asc', 'id' => 'desc'];
        $filter = [
            'CODE' => 'catalog_test'
        ];
        
        $dbResult = \CIBlock::GetList(
            $order,
            $filter,
            false
        );
        
        if ($arIblock = $dbResult->GetNext())
        {
            $this->iblockId = intval($arIblock['ID']);
        }
        else
        {
            $this->iblockId = 0;
        }
	}
    
    /**
     * Возвращает Id инфоблока с товарами
     * 
     * @return int
     */
	public function getIblockId()
	{
		return $this->iblockId;
	}

    /**
     * Получение товара по Id
     * 
     * @param int|int[] $id
     */
	public function getById($id)
	{
		return $this->getProducts([
			'ID' => intval($id)
		]);
	}
    
    /**
     * Получение товара по имени
     * 
     * @param string $name
     */
	public function getByName($name)
	{
		return $this->getProducts([
			'NAME' => '%' . strval($name) . '%'
		]);
	}

    /**
     * Получение товара по производителю
     * 
     * @param type $manufacturer
     */
	public function getByManufacturer($manufacturer)
	{
		return $this->getProducts([
			'PROPERTY_MANUFACTURER' => strval($manufacturer)
		]);
	}
    
    /**
     * Возвращает товары по привязке к разделу. 
     * 
     * @param int $sectionId
     * @param bool $doIncludeSubsections Включать товары из подразделов
     */
	public function getBySection($sectionId, $doIncludeSubsections = false)
	{
		$filter = [
			'SECTION_ID' => intval($sectionId)
		];

		if ($doIncludeSubsections && $sectionId > 0) {
			$filter['INCLUDE_SUBSECTIONS'] = 'Y';
		}

		return $this->getProducts($filter);
	}
    
    /**
     * Получение товаров из инфоблока
     * 
     * @param type $additionalFilter
     * @return \Inwebium\Module\Model\Product
     */
	private function getProducts($additionalFilter)
	{
		$result = [];

		$order = ['ORDER' => 'ASC', 'NAME' => 'ASC', 'ID' => 'DESC'];
		$filter = array_merge([
				'IBLOCK_ID' => $this->getIblockId()
			], 
			$additionalFilter);

		$elementsResult = \CIBlockElement::GetList(
			$order,
			$filter,
			false,
			false,
			['IBLOCK_ID', 'ID', 'NAME', 'AVAILABLE', 'PRICE_1', 'PROPERTY_MANUFACTURER']
		);

		while($element = $elementsResult->GetNext()) {
            $result[] = new \Inwebium\Module\Model\Product(
                $element['ID'],
                $element['NAME'],
                $element['AVAILABLE'] == 'Y' ? true : false,
                $element['PRICE_1'],
                $element['PROPERTY_MANUFACTURER_VALUE']
            );
		}

		return $result;
	}
}