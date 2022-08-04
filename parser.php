<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}

// Сделал интерфейс для выбора инфоблока, куда будем парсить
if(CModule::IncludeModule("iblock"))
{
   $iblocks = GetIBlockList(""); 
   echo '<form method=POST>';
   echo 'Выберите инфоблок <select name="infoblock"> '; 
   while($arIBlock = $iblocks->GetNext()) 
   {
      echo '<option>'.$arIBlock["NAME"].'<br>'; echo '</option>';    
   }
   echo '</select>';
   echo '<br><br><input type="submit" value="Выбрать инфоблок">';
   echo '</form>';
   echo '<pre>';  
   print_r($arIBlock);
   echo '</pre>';  
}

$infoblock_name = $_POST['infoblock'];
echo 'Название:'.$infoblock_name;

if(CModule::IncludeModule("iblock"))
{ 
 $iblocks = GetIBlockList(""); 
 while($arIBlock = $iblocks->GetNext()) 
 {
    if ($arIBlock["NAME"]==$infoblock_name) {
      $infoblock_ID = $arIBlock["ID"];
      echo '<br>ID: '.$infoblock_ID.'<br>';
    }  
 }
}

\Bitrix\Main\Loader::includeModule('iblock');
$row = 1;
$IBLOCK_ID = $infoblock_ID; // Изменил со статического значения на динамическое

$el = new CIBlockElement;
$arProps = [];

$rsElement = CIBlockElement::getList([], ['IBLOCK_ID' => 37],
    false, false, ['ID', 'NAME']);
while ($ob = $rsElement->GetNextElement()) {
    $arFields = $ob->GetFields();
    $key = str_replace(['»', '«', '(', ')'], '', $arFields['NAME']);
    $key = strtolower($key);
    $arKey = explode(' ', $key);
    $key = '';
    foreach ($arKey as $part) {
        if (strlen($part) > 2) {
            $key .= trim($part) . ' ';
        }
    }
    $key = trim($key);
    $arProps['OFFICE'][$key] = $arFields['ID'];
    
}

$rsProp = CIBlockPropertyEnum::GetList(
    ["SORT" => "ASC", "VALUE" => "ASC"],
    ['IBLOCK_ID' => $IBLOCK_ID]
);
while ($arProp = $rsProp->Fetch()) {
    $key = trim($arProp['VALUE']);
    $arProps[$arProp['PROPERTY_CODE']][$key] = $arProp['ID'];
}

$rsElements = CIBlockElement::GetList([], ['IBLOCK_ID' => $IBLOCK_ID], false, false, ['ID']);
while ($element = $rsElements->GetNext()) {
    CIBlockElement::Delete($element['ID']);
    
}

if (($handle = fopen("parser_test.csv", "r")) !== false) {
    while (($data = fgetcsv($handle, 1000,";")) !== false) {
        if ($row == 1) {
            $row++;
            continue;
        }
        $row++;
        // Изменил поиск свойств в системе со статического способа на динамический
        $properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$IBLOCK_ID));
        
        $i=-1; 
        while ($prop_fields = $properties->GetNext())
        {
        $i++;
        $PROP[$prop_fields["CODE"]] = $data[$i];      
        }

        $arLoadProductArray = [
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $data[3],
            "ACTIVE" => end($data) ? 'Y' : 'N',
        ]; 
    
        if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
            echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
        } else {
            echo "Error: " . $el->LAST_ERROR . '<br>';
        }
    }
    fclose($handle);
} 
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>