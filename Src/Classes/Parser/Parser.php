<?php
/**
 * Parser (parser.php)
 *
 * @author      Satsko Vladislav <djvla64rus@gmail.com>
 *
 */
namespace Parser;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;
/*
 * Parser
 */
class Parser {

    private $config;
    private $xml;
    private $logger;
    private $datetime; // время создания

    /**
     *
     *
     * @param string $env   path of environment
     * @throws \Exception
     * @return mixed
     */
    public function __construct($env)
    {
        //xmlmethods
        $this->xml = new Xml($env);

        //config
        $this->config = (new Config($env))->get();

        //logger
        $this->initLog($env);

        //created_at
        $this->datetime = date('-m-d-Y-his', time());

        return true;
    }

    /**
     *
     * @throws Exception
     */

    public function initLog($env){
        if (!class_exists('Monolog\Logger'))
            throw new \Exception('Logger не установлен');

        $this->logger = new Logger("Parser");
        $this->logger->pushHandler(new StreamHandler($env . $this->config->logPath, Logger::NOTICE));
    }

    /**
     *
     * @throws Exception
     * @return true|false
     */

    public function run(){

        $dirCreated = createDirectories([
            "/storage/",
            $this->config->imagesOutput,
            $this->config->xmlOutput,
            $this->config->jsonOutput
        ], $this->config->envPath);

        if ($dirCreated) {
            $lastCreated = lastCreatedFile($this->config->envPath . $this->config->xmlOutput);

            $similarFiles = compareFiles($lastCreated, $this->config->xmlInput);

            if (!$similarFiles || $this->config->debug) {
                $xmlFile = $this->copyXmlFile($this->config->xmlInput);
                if ($this->serializeXml($xmlFile))
                    return true;

            } else {
                $this->logger->notice("Файлы идентичные {$this->config->xmlInput} и {$lastCreated}");
            }
        }

        return false;
    }

    /**
     *
     *
     * @return string
     */
    public function copyXmlFile($filename) {

        $srcfilepath = $this->config->envPath . $this->config->xmlOutput . "realtyObjects{$this->datetime}.xml";
        limitFiles($this->config->envPath . $this->config->xmlOutput);

        if (copy($filename, $srcfilepath))
            chmod($srcfilepath,0777);

        return $srcfilepath;
    }

    public function returnToWeb($data) {
        $json = json_encode($data,JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json');
        echo $json;
    }

    /**
     *
     * @throws Exception
     */

    public function saveAsJson($data, $name) {
        $json = json_encode($data,JSON_UNESCAPED_UNICODE);

        $directory = $this->config->envPath . $this->config->jsonOutput;

        $fullpath = $this->config->envPath . $this->config->jsonOutput . "{$name}{$this->datetime}.json";

        $filesLimited = limitFiles($this->config->envPath . $this->config->jsonOutput);

        if ($filesLimited) {
            throw new Exception("Нет доступа к папке {$directory}");
        } else {
            if (!saveJsonFile($fullpath, $json)) {
                throw new Exception("Не удалость сохранить json файл {$fullpath}");
            } else {
                if (!createSymlink("{$this->config->symlink}{$name}.json", $fullpath))
                    throw new Exception('Не удалость создать символьную ссылку');
            }

        }

        return true;
    }


    /**
     * Создание json array из xml с помощью функци appendApartment, appendFloor
     * @throws Exception
     * @return true|false
     */

    function serializeXml($xmlPath) {

        $xmlFile = simplexml_load_file($xmlPath);

        $building = $xmlFile
            ->new_developmentproject
            ->new_developmentphases
            ->new_developmentphase
            ->new_buildings
            ->new_building;

        $sections = [];
        foreach ($building->new_sections->new_section as $i => $section) {
            $floors = [];
            foreach ($section->new_floors->new_floor as $floor) {
                $apartments = [];
                foreach ($floor->new_realtyobjects->new_realtyobject as $apartment) {

                    $type = $apartment->new_realtyobjecttype->__toString();
                    $id = $apartment->new_realtyobjectid->__toString();
                    $imageArray = [];
                    $imageExist = true;
                    foreach (['Планировка', 'Планировка этажа'] as $folder)
                        ($entry = $this->copyImage($id, $folder)) ?
                            $imageArray[$folder] = $entry : $imageExist = false;

                            if ($optionalEntry = $this->copyImage($id, 'Планировка с мебелью'))
                                $imageArray['Планировка с мебелью'] = $optionalEntry;

                            $number = $apartment->new_number_on_floor->__toString();
                            $apartment->section = $section->new_name->__toString();
                            $apartment->floor = $floor->new_name->__toString();

                    if (in_array($type, ["Квартира", "Апартаменты", 'Машиноместо']) && $imageExist)
                        $apartments[$number] = $this->xml->appendApartment($apartment, $imageArray);
                }
                $floorName = $floor->new_name->__toString();
                $floors[$floorName] = $this->xml->appendFloor($floor, $apartments);
                unset($apartments);
            }
            $sectionName = $section->new_name->__toString();
            $sections[$i][$sectionName] = $this->xml->appendSection($section, $floors);
            unset($floors);
        }

        $list = $this->returnListApartments($sections);

        createSymlink(
            $this->config->symlinkImages,
            $this->config->envPath . $this->config->imagesOutput . "apartment{$this->datetime}/"
        );


        if ($this->config->mode == "f") {
            $this->saveAsJson($sections, 'apartments');
            $this->saveAsJson($list, 'list');
        } elseif ($this->config->mode == "w"){
            $this->returnToWeb($list);
        } else {
            return false;
        }

        return false;
    }

    function returnListApartments($data) {

        $list = [];

        foreach ($data as $korpus) {
            foreach ($korpus as $sections) {
                foreach ($sections as $floors) {
                    if (is_array($floors)) {
                        foreach ($floors as $floor) {
                            foreach ($floor['apartments'] as $apartment) {
                                if (in_array($apartment['status'], ['Свободен', 'Бронь']))
                                    $list[] = $apartment;
                            }
                        }
                    }

                }
            }
        }

        return $list;
    }

    /**
     *
     * @throws Exception
     * @return string
     */
    function copyImage($id, $folder) {

        $dir = $this->config->envPath . $this->config->imagesOutput ."apartment{$this->datetime}";
            if (!is_dir($dir)) mkdir($dir,0777, true);

        $srcPath = "{$this->config->imagesPath}/{$id}/{$folder}/";
        $destPath = "{$dir}/{$id}/{$folder}/";

        $image = null;
        if (is_dir($srcPath) && !is_dir_empty($srcPath))
            $image = scandir($srcPath)[2];

        if ($image && !is_dir($destPath)) {

            $enableWatermark = getenv('WATERMARK');
            mkdir($destPath,0777, true);

            if (!$enableWatermark) {
                copy(
                    $srcPath.$image,
                    $destPath.$image
                );
            } else {
                ImageEdit::watermark(
                    $srcPath.$image,
                    $destPath.$image
                );
            }
        }

        return $image;
    }

}
