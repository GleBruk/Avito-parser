<?php
require_once 'app/controllers/Curl.php';
require_once 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

class AvitoContact{
    function __construct() {
        $this->curl = new Curl;
    }

    public function getPhoneUrl($itemId){
        //Формируем url
        $contact = 'https://www.avito.ru/items/phone/'.$itemId;
        return $contact;
    }

    function saveInFile($image, $filename){
        $image = explode(',', $image)[1];
        $a = fopen($filename, 'wb');
        fwrite($a, base64_decode($image));
        fclose($a);
    }

    function recognize($image){
        return (new TesseractOCR($image))->run();
    }
}