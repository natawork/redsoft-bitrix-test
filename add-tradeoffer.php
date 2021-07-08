<?
/**
 * Скрипт добавления товара и его торговых предложений
 * Скрипт расположен в /local/scripts/
 */
set_time_limit(120);
ini_set('memory_limit', '1024M');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../'));
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

define("NO_KEEP_STATISTIC", true); // запрещаем сбор статистики на данном скрипте
define("NO_AGENT_CHECK", true); // выключим выполнение всех агентов

// Скидываем буферизацию
while (ob_get_level()) {
    ob_end_flush();
}

$iTime = time();

/**
 * Основные действия скрипта
 */

// Подключаем ядро 1С-Битрикс
require($DOCUMENT_ROOT . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');

$IBLOCK_ID = 2; // IBLOCK товаров
$IBLOCK_ID_OFFER = 3; // IBLOCK торговых предложений
$SECTION_ID = 7; // Раздел

// Передаем данные товара
$arrProduct = Array (
    'IBLOCK_ID' => $IBLOCK_ID,
    'SECTION_ID' => $SECTION_ID,
    'NAME' => "Товар 1",
    // Прочие параметры товара
);
// Создаем символьный код товара
$arParams = Array("replace_space"=>"-","replace_other"=>"-");
$trans = Cutil::translit($arrProduct["NAME"], "ru", $arParams);

// Добавляем товар-родитель, у которго будут торговые предложения
$ciBlockElement = new CIBlockElement;
$product_id = $ciBlockElement->Add(
    array(
        'IBLOCK_ID' => $arrProduct["IBLOCK_ID"],
        "IBLOCK_SECTION_ID" => $arrProduct["SECTION_ID"],
        'NAME' => $arrProduct["NAME"],
        "CODE" => $trans,
        "ACTIVE" => "Y",
        // Прочие параметры товара
    )
);
// Проверка на ошибки
if (!empty($ciBlockElement->LAST_ERROR)) {
    echo "Ошибка добавления товара: ". $ciBlockElement->LAST_ERROR;
    die();
}


// Передаем данные торгового предложения
$arrProductOffer = Array (
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => "Торговое предложение 1",
    'QUANTITY' => 9999,
    'PRICE' => 999,
    'CURRENCY' => "RUB",
    'PROPERTY_VALUES' => array(
        'ARTICLE' => '235-82-06',
        'COLOR' => '',
        'SIZE' => '',
    ),
    // Прочие параметры торгового предложения
);
// Создаем символьный код торгового предложения
$arParams = Array("replace_space"=>"-","replace_other"=>"-");
$transOffer = Cutil::translit($arrProductOffer["NAME"].'-'.$arrProductOffer['PROPERTY_VALUES']["ARTICLE"], "ru", $arParams);

// Добавляем нужное количество торговых предложений
$arLoadProductArray = array(
    "IBLOCK_ID" => $IBLOCK_ID_OFFER,
    "NAME" => $arrProductOffer["NAME"],
    "CODE" => $transOffer,
    "ACTIVE" => "Y",
    'PROPERTY_VALUES' => array(
        'CML2_LINK' => $product_id, // Свойство типа "Привязка к товарам (SKU)", связываем торговое предложение с товаром
        'ARTNUMBER' => $arrProductOffer['PROPERTY_VALUES']["ARTICLE"],
    )
    // Прочие параметры торгового предложения
);
$product_offer_id = $ciBlockElement->Add($arLoadProductArray);
// Проверка на ошибки
if (!empty($ciBlockElement->LAST_ERROR)) {
    echo "Ошибка добавления торгового предложения: ". $ciBlockElement->LAST_ERROR;
    die();
}
// Добавляем параметры к торговому предложению
CCatalogProduct::Add(
    array(
        "ID" => $product_offer_id,
        "QUANTITY" => $arrProductOffer["QUANTITY"]
    )
);
// Добавляем цену к торговому предложению
CPrice::Add(
    array(
        "CURRENCY" => $arrProductOffer["CURRENCY"],
        "PRICE" => $arrProductOffer["PRICE"],
        "CATALOG_GROUP_ID" => 1,
        "PRODUCT_ID" => $product_offer_id,
    )
);


/**
 * Отладочная информация
 */
$sMemory = (!function_exists('memory_get_usage')) ? '-' : round(memory_get_usage() / 1024 / 1024, 2).' Mb';
$iTime = time() - $iTime;

echo PHP_EOL.'Использовано ресурсов: '.PHP_EOL.'Memory: '.$sMemory.PHP_EOL.'Time: '. $iTime.' s'.PHP_EOL;

require($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_after.php");
?>
