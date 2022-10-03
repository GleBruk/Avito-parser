<?php
require_once 'app/controllers/Curl.php';

class AvitoContact{
    function __construct() {
        $this->curl = new Curl;
        $this->offxetX = 13;
        $this->offxetY = 6;
        $this->whitePixel = 2147483647;
        $this->offxetL = 2;
    }

    public function getPhoneUrl($content, $itemId){
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
        $imageScheme = $this->getImageScheme($image);
        $phoneNumber = $this->recognizeByScheme($imageScheme);

        return $phoneNumber;
    }

    function getImageScheme($image, $columnFrom=false, $columnTo=false){
        $size = getimagesize($image);
        $img = strpos($image, 'png') ? imagecreatefrompng($image) : imagecreatefromjpeg($image);

        $w = $size[0];//x
        $h = $size[1];//y

        $data = array();
        for($x = 0; $x < $w; $x ++) {
            if ($x < $this->offxetL) {
                continue;
            }
            // $data это основной массив в который сохраняем все 0 и 1 найденных цветов пикселей в колонке
            $dataColumn = array();
            $foundedOneFilled = 0;
            for ($y = $this->offxetX; $y < ($h - $this->offxetY); $y++){
                // запись в массив каждой точки ее значения
                $pixel = imagecolorat($img, $x, $y);
                if ($pixel >= $this->whitePixel) {
                    $dataColumn []= 0;
                } else {
                    $dataColumn []= 1;
                    $foundedOneFilled = 1;
                }
            }

            // пропускаем черточку
            if (array_sum($dataColumn) == 4 && $dataColumn[18].$dataColumn[19].$dataColumn[20].$dataColumn[21] == '1111') {
                continue;
            }

            // Добавляем колонку только если нашли хотя бы 1 заполненную ячейку не белого цвета
            if ($foundedOneFilled == 1) {
                $data []= $dataColumn;
            }
        }

        return $data;
    }

    /**
     * Получаем маску распознавания
     */
    function getMask(){
        $maskFile = 'avito-mask.php';
        if (!file_exists($maskFile)) {
            $this->error('Не существует файла маски '.$maskFile);
            return ;
        }

        include $maskFile;
        return $mask;
    }


    function recognizeByScheme($imageScheme){
        $mask = $this->getMask();
        if (!$mask) {
            return ;
        }

        // Допуск похожести
        $dopusk = 3;
        $phoneNumber = '';
        $columnsSet = array();

        // Проходим по каждому столбцу изображения. Аккумулируем его в $columnsSet - там собирается набор.
        // Для каждого прохода проверяем по каждой маске, совпадает ли набранный набор с какой-то маской. Если ок, то
        // посимвольно сверяем маску с набором. Если схожеть больше 3, то значит нашли. Обнуляем идем дальше.
        // Если $columnsSet достиг макс. предела в 70 (шире цифр пока нет) - то выходим. Значит здесь косяк.
        foreach ($imageScheme as $aindex => $column) {

            // Все колонки по очереди объединяем в набор колонок до тех пор пока либо найдем подходящую под него маску
            // либо выйдем за пределы ширины букв и завершим с ошибкой
            foreach ($column as $it) {
                $columnsSet [] = $it;
            }

            foreach ($mask as $key => $mk) {

                if (count($columnsSet) == count($mk)) {

                    // Сравниваем посимвольно массив маски с собранным массивом картинки
                    // Сколько символов совпадает?
                    $countEqual = 0;
                    foreach ($columnsSet as $i => $nit) {
                        if ($nit == $mk[$i]) {
                            $countEqual ++;
                        }
                    }

                    // Да, мы нашли эту цифру!
                    // Количество либо полностью совпадает, либо находится в границах допустимого
                    if ($countEqual == count($mk) || ($countEqual > count($mk) - $dopusk && $countEqual < count($mk) + $dopusk)) {
                        $phoneNumber .= $key;
                        $columnsSet = array();
                    }

                }
            }
        }
        return $phoneNumber;
    }
}