<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("COMPONENT_DESCRIPTION"),
	"SORT" => 10,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "inwebium",
		"CHILD" => array(
			"ID" => "endpoint",
			"NAME" => GetMessage("MODULE_NAME"),
			"SORT" => 10,
		),
	),
);