<?php
namespace Parser;

use Imagick;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Parser {

    private $xmlInput; // xml из конфига
    private $imagesPath; // папка с изображениями
    private $datetime; // время создания
    private $symlink; // куда класть сделанный json
    private $envPath;
    private $debug = "1";
    private $mode = "f"; // w - view , f - file
    private $xmlOutput = "/storage/xml/"; // папка куда сохранять xml
    private $jsonDir = "/storage/json/";
    private $logPath = "/storage/parser.log";
    private $logger;

    public function __construct($env)
    {
        $this->datetime = date('-m-d-Y-his', time());
        $this->imagesPath = getenv('IMAGES_PATH');
        $this->symlink = getenv('SYMLINK_PATH');
        $this->xmlInput = getenv('XML_PATH');
        $this->mode = setMode();

        $this->enableLogger($env . $this->logPath);

        createDirectories(['storage/', '/storage/images/', $this->xmlOutput, $this->jsonDir], $this->envPath = $env);

        $lastCreated = lastCreatedFile($this->envPath . $this->xmlOutput);
        $similarFiles = compareFiles($lastCreated, $this->xmlInput);


        if (!$similarFiles || $this->debug) {
            $this->serializeXml($this->copyXmlFile($this->xmlInput));
        } else {
            $this->logger->notice("Файлы идентичные {$this->xmlInput} и {$lastCreated}");
        }

    }

    private function saveAsJson($data, $mode) {

        $json = json_encode($data,JSON_UNESCAPED_UNICODE);

        if ($mode == "w") {
            header('Content-Type: application/json');
            echo $json;
        }

        if ($mode == "f") {
            $fullpath = $this->envPath . $this->jsonDir . "/apartments{$this->datetime}.json";

            limitFiles($this->jsonDir);
            saveJsonFile($fullpath, $json);
            createSymlink($this->symlink,$fullpath);
        }
    }

    private function copyXmlFile($filename) {

        $srcfilepath = $this->envPath . $this->xmlOutput . "realtyObjects{$this->datetime}.xml";
        limitFiles($this->envPath . $this->xmlOutput);

        if (copy($filename, $srcfilepath))
            chmod($srcfilepath,0777);

        return $srcfilepath;
    }

    function appendApartment($apartment, $image, $areas = []) {

        foreach ($apartment->new_realtyobjectareas->new_realtyobjectarea as $area)
            $areas[$area->new_name->__toString()] = $area->new_area->__toString();

        $id = $apartment->new_realtyobjectid->__toString();
        $number = $apartment->new_number->__toString();
        $numberOnFloor = $apartment->new_number_on_floor->__toString();
        $quanity = $apartment->new_room_quantity->__toString();

        $space = "\n                        ";

        $this->logger->info("Добавлена квартира {$apartment->new_number->__toString()}");

        $terrace = 0;
        foreach ($areas as $value) if ($value == 'Терраса') $terrace = 1;

        $apartment = [
            'apartment_id' => $id,
            "section" => $apartment->section->__toString(),
            'floor' => $apartment->floor->__toString(),
            "number_on_floor" => $numberOnFloor,
            "number" => $number,
            "room_quantity" => $quanity,
            "storeysnumber" => $apartment->new_storeysnumber->__toString(),
            "type" => $apartment->new_realtyobjecttype->__toString(),
            "area" => $apartment->new_area->__toString(),
            "price" => $apartment->new_price->__toString(),
            "amount" => $apartment->new_amount->__toString(),
            "balcony" => $apartment->new_balcony->__toString(),
            "status" => $apartment->new_realtyobjectstatus->__toString(),
            "woodfireplace" => $apartment->new_woodfireplace->__toString(),
            "bathroomwindow" => $apartment->new_bathroomwindow->__toString(),
            "kitchenwindow" => $apartment->new_kitchenwindow->__toString(),
            "action" => $apartment->new_action->__toString(),
            "layouttype" => $apartment->layouttype->__toString(),
            "reservation_till" => str_replace($space, "", $apartment->new_reservation_till->__toString()),
            "area_living" => str_replace($space, "", $apartment->new_area_living->__toString()),
            "ceilingheight" => str_replace($space, "", $apartment->new_ceilingheight->__toString()),
            "objectareas" => $areas,
            "terrace" => $terrace,
            "finish" => ($apartment->new_finish->__toString() !== "1") ? "0" : "1",
            "link" => createUrl($apartment['korpus'], $apartment['section'], $apartment['floor'], $numberOnFloor, $number, $quanity, $id),
        ];

        $imagesOptions = [
            'Планировка' => 'layout',
            'Планировка этажа'=> 'layout_floor',
            'Планировка c мебелью' => 'layout_with'
        ];

        foreach ($imagesOptions as $folder => $option)
            if (isset($image[$folder])) {
                $nameOfFile = $image[$folder];
                $apartment[$option] = "/images/{$id}/{$folder}/{$nameOfFile}";
            }


        return $apartment;
    }

    function appendFloor($floor, $apartments) {
        return [
            'floor_id' => $floor->new_floorid->__toString(),
            'floor_name' => $floor->new_name->__toString(),
            'apartments' => $apartments
        ];
    }

    function appendSection($section, $floors) {

        return [
            "section_id" => $section->new_sectionid->__toString(),
            "section_name" => $section->new_name->__toString(),
            "floors" => $floors
        ];

    }

    /*
     * Создание json array из xml с помощью функци appendApartment, appendFloor
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
                        $apartments[$number] = $this->appendApartment($apartment, $imageArray);
                }
                $floorName = $floor->new_name->__toString();
                $floors[$floorName] = $this->appendFloor($floor, $apartments);
                unset($apartments);
            }
            $sectionName = $section->new_name->__toString();
            $sections[$i][$sectionName] = $this->appendSection($section, $floors);
            unset($floors);
        }
        $imagesPath = getenv('SYMLINK_IMAGES_PATH');
        createSymlink($imagesPath,$this->envPath . "/storage/images/apartment{$this->datetime}/");

        $this->saveAsJson($sections, $this->mode);

        return true;
    }

    /*
     * Инициализация логгера
     *
     */
    function enableLogger($logPath){
        try {
            $this->logger = new Logger("Parser");
            $this->logger->pushHandler(new StreamHandler($logPath, Logger::NOTICE));
        } catch (\Exception $e) {
            throw new \Exception('Logger Not Found');
        }
    }

    function copyImage($id, $folder) {

        $dir = $this->envPath . "/storage/images/apartment{$this->datetime}";
            if (!is_dir($dir)) mkdir($dir,0777, true);

        $srcPath = "{$this->imagesPath}/{$id}/{$folder}/";
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
                $this->watermark(
                    $srcPath.$image,
                    $destPath.$image
                );
            }
        }

        return $image;
    }


    public function watermark($origImage, $destImage) {
        $image = new Imagick();
        $image->readImage($origImage);

        $watermark = new Imagick();
        $watermark->readImage($this->envPath . "/imagick.png");

        $iWidth = $image->getImageWidth();
        $iHeight = $image->getImageHeight();
        $wWidth = $watermark->getImageWidth();
        $wHeight = $watermark->getImageHeight();

        if ($iHeight < $wHeight || $iWidth < $wWidth) {
            $watermark->scaleImage($iWidth/4, $iHeight/4);

            $wWidth = $watermark->getImageWidth();
            $wHeight = $watermark->getImageHeight();
        }

        $x = ($iWidth - $wWidth) / 2;
        $y = ($iHeight - $wHeight) / 2;

        $image->compositeImage($watermark, imagick::COMPOSITE_OVER, $x+270, $y+300);
        $image->writeImage ($destImage);

    }

}
