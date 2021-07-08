<?
/**
 * Найти и убить файлы от удаленных товаров (или от старого сайта), которые лежат вместе с картинками товаров в одной директории на диске
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

// Подключаем ядро 1С-Битрикс
require($DOCUMENT_ROOT . "/bitrix/modules/main/include/prolog_before.php");

// Скидываем буферизацию
while (ob_get_level()) {
    ob_end_flush();
}

$iTime = time();

/**
 * Основные действия скрипта
 */

// Создаем массив файлов из таблицы b_file.
$arFiles = array();
$result = $DB->Query('SELECT SUBDIR, FILE_NAME FROM b_file WHERE MODULE_ID = "iblock"');
while ($row = $result->Fetch()) {
    $arFiles[ $row['FILE_NAME'] ] = $row['SUBDIR'];
}

$rootDirPath = $DOCUMENT_ROOT . "/upload/iblock";
$rootDir = opendir($rootDirPath);
$filesCount = 0;
$fileSize = 0;
while (false !== ($subDirName = readdir($rootDir))) {
    if ($subDirName == '.' || $subDirName == '..')
        continue;
    $subDirPath = $rootDirPath.'/'.$subDirName;
    $subDir = opendir($subDirPath);
    while (false !== ($fileName = readdir($subDir))) {
        if ($fileName == '.' || $fileName == '..')
            continue;
        if (array_key_exists($fileName, $arFiles)) {
            continue;
        }
        $fullPath = $subDirPath.'/'.$fileName;
        $fileSize += filesize($fullPath); // Считаем общий размер удаляемых файлов
        if (unlink($fullPath)) { // Удаляем файл
            echo 'Удаляем файл: '.$fullPath.PHP_EOL;
            $filesCount++; // Считаем количество удаляемых файлов
        }
    }
    closedir($subDir);
    // Удаляем пустую папку
    if (!glob($subDirPath . "/*")) {
        echo 'Удаляем папку: '.$subDirPath.PHP_EOL;
        @rmdir($subDirPath);
    }
}
closedir($rootDir);

$fileSizePrint = (round($fileSize / 1024 , 2) > 1024) ? ( (round($fileSize / 1024 / 1024, 2) > 1024) ? round($fileSize / 1024 / 1024 / 1024, 2).' Gb' : round($fileSize / 1024 / 1024, 2).' Mb' ) : round($fileSize / 1024, 2).' Kb';

echo 'Удалено файлов: '.$filesCount.PHP_EOL;
echo 'Освобождено '.$fileSizePrint.' места на диске'.PHP_EOL;

/**
 * Отладочная информация
 */
$sMemory = (!function_exists('memory_get_usage')) ? '-' : round(memory_get_usage() / 1024 / 1024, 2).' Mb';
$iTime = time() - $iTime;

echo PHP_EOL.'Использовано ресурсов: '.PHP_EOL.'Memory: '.$sMemory.PHP_EOL.'Time: '. $iTime.' s'.PHP_EOL;

require($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_after.php");
?>
