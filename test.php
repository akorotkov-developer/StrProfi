<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Компания СтройПрофи. О компании");
$APPLICATION->SetTitle("СтройПрофи");
?>
<?php
/**Сортировка категорий*/
$xml = simplexml_load_file("tmp/cats2.xml");

//Переведем XML в массив
function xmlToArray(SimpleXMLElement $xml): array
{
    $parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
        $nodes = $xml->children();
        $attributes = $xml->attributes();

        if (0 !== count($attributes)) {
            foreach ($attributes as $attrName => $attrValue) {
                $collection['attributes'][$attrName] = strval($attrValue);
            }
        }

        if (0 === $nodes->count()) {
            $collection['value'] = strval($xml);
            return $collection;
        }

        foreach ($nodes as $nodeName => $nodeValue) {
            if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
                $collection[$nodeName] = $parser($nodeValue);
                continue;
            }

            $collection[$nodeName][] = $parser($nodeValue);
        }

        return $collection;
    };

    return [
        $xml->getName() => $parser($xml)
    ];
}

$arrXml = xmlToArray($xml);

//Сортировка массива категорий согласно SORT_IN_PRICE
//Получим все категории
$arSectionRowIds = [];
foreach($arrXml['categories']['category'] as $category) {
    $arSectionRowIds[] = $category['ID']['value'];
}

CModule::IncludeModule("iblock");
$arFilter = [
    'ACTIVE' => 'Y',
    'IBLOCK_ID' => 1,
    'UF_ROWID' => $arSectionRowIds
];
$arSelect = ['IBLOCK_ID', 'ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'PICTURE', 'UF_ROWID'];
$arOrder = ['DEPTH_LEVEL' => 'ASC', 'SORT' => 'ASC'];
$rsSections = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect);

$arSectionIds = [];
while ($arSection = $rsSections->GetNext()) {
    $arSectionIds[$arSection['UF_ROWID']] = $arSection['ID'];
    $arSectionsKeys[$arSection['ID']] = $arSection['UF_ROWID'];
}

//Поочереди сделаем запросы в базу
foreach ($arSectionIds as $sectId) {
    $arFilter = [
        'IBLOCK_ID' => 1,
        'PROPERTY_SHOW_IN_PRICE' => [1],
        'SECTION_ID' => $sectId,
        'INCLUDE_SUBSECTIONS' => 'Y'
    ];
    $dbRes = CIBlockElement::GetList(
        ['SORT"=>"ASC'],
        $arFilter,
        false,
        false,
        [
            'ID',
            'NAME',
            'PROPERTY_SORT_IN_PRICE',
            'IBLOCK_SECTION_ID'
        ]
    );
    while ($arRez = $dbRes->Fetch())
    {
        if ($arRez['PROPERTY_SORT_IN_PRICE_VALUE']) {
            $arSectionsSortInPriceValues[$sectId] = $arRez['PROPERTY_SORT_IN_PRICE_VALUE'];
            break;
        }
    }
}

foreach ($arSectionsSortInPriceValues as $key => $sectItem) {
    $arrSectionsWithKeys[$arSectionsKeys[$key]]['SORT_IN_PRICE'] = $sectItem;
}

//Подставим значения SORT_IN_PRICE в массив для XML файла

foreach ($arrXml['categories']['category'] as $key => $xmlItem) {
    $arrXml['categories']['category'][$key]['SORT_IN_PRICE'] = $arrSectionsWithKeys[$xmlItem['ID']['value']]['SORT_IN_PRICE'];
}

usort($arrXml['categories']['category'], function ($a, $b) {
    return $a['SORT_IN_PRICE'] < $b['SORT_IN_PRICE'] ? -1 : 1;
});


//Переведем массив в XML и сохраним его в файл cats_sort.xml
use Spatie\ArrayToXml\ArrayToXml;
$result = ArrayToXml::convert($arrXml);
$result = ArrayToXml::convert($arrXml, ['categories'], true, 'UTF-8', '1.1', []);

//Удаляем лишнее из файла
$result = preg_replace('/<root>/', '',$result);
$result = preg_replace('/<\/root>/', '',$result);

$result = preg_replace('/<value>/', '',$result);
$result = preg_replace('/<\/value>/', '',$result);

file_put_contents('tmp/cats_sort.xml', $result);
?>







<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>