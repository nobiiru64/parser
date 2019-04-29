<?php
/**
 * Parser (parser.php)
 *
 * @author      Satsko Vladislav <djvla64rus@gmail.com>
 *
 */
namespace Parser;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/*
 * Xml
 */
class Xml {

    public $logger;

    public function __construct($env)
    {
        $this->logger = new Logger("Parser");

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
            "kitchenover" => $apartment->new_kitchenover12->__toString(),
            "action" => $apartment->new_action->__toString(),
            "layouttype" => $apartment->new_layouttype->__toString(),
            "reservation_till" => str_replace($space, "", $apartment->new_reservation_till->__toString()),
            "area_living" => str_replace($space, "", $apartment->new_area_living->__toString()),
            "ceilingheight" => str_replace($space, "", $apartment->new_ceilingheight->__toString()),
            "objectareas" => $areas,
            "terrace" => $terrace,
            "finish" => ($apartment->new_finish->__toString() !== "1") ? "0" : "1",
            "link" => createUrl(
                $apartment['korpus'],
                $apartment['section'],
                $apartment['floor'],
                $numberOnFloor,
                $number,
                $quanity,
                $id
            ),
        ];

        $imagesOptions = [
            'Планировка' => 'layout',
            'Планировка этажа'=> 'layout_floor',
            'Планировка с мебелью' => 'layout_with'
        ];

        foreach ($imagesOptions as $folder => $option)
            if (isset($image[$folder])) {
                $nameOfFile = $image[$folder];
                $apartment[$option] = "/images/{$id}/{$folder}/{$nameOfFile}";
            }


        return $apartment;
    }



    /*
     * @return array
     */
    function appendFloor($floor, $apartments) {
        return [
            'floor_id' => $floor->new_floorid->__toString(),
            'floor_name' => $floor->new_name->__toString(),
            'apartments' => $apartments
        ];
    }

    /*
     * @return array
     */
    function appendSection($section, $floors) {

        return [
            "section_id" => $section->new_sectionid->__toString(),
            "section_name" => $section->new_name->__toString(),
            "floors" => $floors
        ];

    }

}
