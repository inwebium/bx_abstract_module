<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

CModule::IncludeModule("inwebium.module");

use Bitrix\Main\Context,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Loader,
	Bitrix\Iblock,
    Inwebium\Module\Service\ProductService;

if (isset($_GET['method'])) {
    header('Content-Type: application/json');
    $productService = new ProductService();
    $result = [];

    switch ($_GET['method']) {
        case 'getById':
            $result = $productService->getById($_GET['id']);
            break;
        case 'getByName':
            $result = $productService->getByName($_GET['name']);
            break;
        case 'getByManufacturer':
            $result = $productService->getByManufacturer($_GET['manufacturer']);
            break;
        case 'getBySection':
            if (isset($_GET['includeSubsections'])) {
                $result = $productService->getBySection($_GET['section'], 'Y');
            } else {
                $result = $productService->getBySection($_GET['section']);
            }
            break;
        default:
            break;
    }

    echo json_encode($result);
} else {
    $this->includeComponentTemplate();
}

