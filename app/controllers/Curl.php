<?php

class Curl extends Controller {

    function __construct()
    {
        $this->sleepMin = 2;
        $this->sleepMax = 5;
    }

    function setCache($content, $cacheId){
        if ($content == '') {
            return ;
        }
        //Определяем название файла
        $fileName = 'cash/'.md5($cacheId);
        if (!file_exists('cash')) {
            mkdir('cash');
        }
        //Записываем файл
        $f = fopen($fileName, 'w+');
        fwrite($f, $content);
        fclose($f);
    }

    function getCache($cacheId, $cashExpired=true, &$fileName=''){
        //Если не указана продолжительность жизни файла, то выходим из функции
        if (!$cashExpired) {
            return;
        }
        //Определяем название файла
        $fileName = 'cash/'.md5($cacheId);
        if (!file_exists($fileName)) {
            return false;
        }
        //Если файл слишком старый, то выходим из функции
        $time = time() - filemtime($fileName);
        if ($time > $cashExpired) {
            return false;
        }
        //Получаем содержимое страницы
        return file_get_contents($fileName);
    }

    function load($url, $cash=0){
        //Если страница есть в кеше, то берём её оттуда и не используем curl
        $this->fromCash = false;
        $cacheId = $url;
        if ($content = $this->getCache($cacheId, $cash)) {
            if (!strpos($content, 'Location: https://www.avito.ru/blocked')) {
                $this->fromCash = true;
            	return $content;
            }
        }

        //Получаем контент через curl. Для получения контента таким образом нужно настроить TLS-отпечаток.
        //Т.к. это устаревшая версия парсера, то здесь TLS-отпечаток не настроен и вероятно парсер
        //не сработает. Если вас интересует актуальная версия парсера - свяжитесь со мной.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 YaBrowser/21.3.3.234 Yowser/2.5 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content = curl_exec($ch);
        curl_close($ch);

        sleep(rand($this->sleepMin, $this->sleepMax));

        //Логи
        $file = fopen('log.txt', 'a+');
        fwrite($file, "\n".date('Y-m-d H:i:s').' '.$url);
        fclose($file);

        //Добавляем страницу в кеш
        $this->setCache($content, $cacheId);
        return $content;
    }
}
