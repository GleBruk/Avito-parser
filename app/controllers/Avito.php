<?php

require_once 'app/controllers/Curl.php';
require_once 'app/controllers/AvitoContact.php';
require_once 'vendor/autoload.php';
use DiDom\Document;

class Avito extends Controller {

    public $loadCard;
    public $loadStat;

    function __construct() {
        $this->curl = new Curl;
    }

    public function index(){
        $this->view('avito');
    }

    public function parse(){
        $this->loadCard = $_POST['load-card'];
        $this->loadStat = $_POST['load-stat'];
        $this->curl->sleepMin = $_POST['sleep_min'];
        $this->curl->sleepMax = $_POST['sleep_max'];

        //Собираем данные
        $data = $this->parseAll($_POST['url'], $fromPage=1, $_POST['maxPage']);
        //Переходим в шаблон
        $this->view('avito', $data);
    }

    public function loadCard($url){
        $this->parseCard($url, $data);
        $this->view('avito', $data);
    }

    public function loadPhone($url){
        // Подготавливаем входные параметры
        preg_match('~_(\d+)$~i', $url, $a);
        $id = $a[1];
        $cardContent = $this->curl->load($url, 0);
        $avitoContact = new AvitoContact;
        $data['phone_url'] = $avitoContact->getPhoneUrl($cardContent, $id);
        $imgContent = $this->curl->load($data['phone_url'], 0);
        $img = json_decode($imgContent);
        $data['image'] = $img->image64;
        $avitoContact->saveInFile($data['image'], 'phone.png');

        // Распознаем файл
        $data['result'] = $avitoContact->recognize('phone.png');

        $this->view('avito', $data);
        exit;
    }

    public function parseAll($url, $fromPage=1, $maxPage=false){
        $dataAll = [];
        $page = $fromPage;
        while (true) {
            //Определяем url
            if ($page == 1) {
                $urlCurrent = $url;
            } else {
                if (strpos($url, '?')) {
                    $urlCurrent = $url . '&p=5';
                }
            }

            //Парсим данные по указанному url
            $data = $this->parsePage($urlCurrent);

            //Если ничего не загрузилось, то выходим из цикла
            if (!count($data)) {
                break;
            }
            $dataAll = array_merge($dataAll, $data);

            //Если дошли до крайней страницы, то выходим из цикла
            if ($maxPage && $page == $maxPage) {
                break;
            }
            $page ++;
        }
        return $dataAll;
    }

    public function parsePage($url){
        //Загружаем страницу
        $content = $this->curl->load($url, $cash=3600);
        $pageContent = new Document($content);
        $items = $pageContent->find('div[data-marker="catalog-serp"]')[0];
        //print_r($items);
        foreach ($items->find('div[data-marker=item]') as $item){
            $row['name'] = $item->find('h3[itemprop=name]')[0]->text();
            $row['link'] = 'https://www.avito.ru' . $item->find('a[data-marker="item-title"]')[0]->attr('href');
            $row['price'] = $item->find('meta[itemprop="price"]')[0]->attr('content');
            /*
             * Парсим карточку. Из-за новой защиты авито от парсинга данная функция к сожалению больше не работает.
             * А для обхода данной защиты руки пока не дошли
            */
            if ($this->loadCard) {
                $this->parseCard($row['url'], $row);
            }

            $data[] = $row;
        }
        return $data;
    }

    public function parseCard($url, &$row){
        $content = $this->curl->load($url, 86400);
        $cardContent = new Document($content);

        //Получаем просмотры
        $views = $cardContent->find('div .title-info-metadata-views')[0]->text();
        if($views != '') {
            preg_match('~\s*(\d+)\s*\(\+(\d+)\)~', $views, $a);
            if($a[1] && $a[2] != ''){
                $row['views-total'] = $a[1];
                $row['views-today'] = $a[2];
            }else{
                $row['views-total'] = $views;
                $row['views-today'] = $views;
            }

        } else{
            $row['views-total'] = 0;
            $row['views-today'] = 0;
        }

        $row['text'] = $cardContent->find('div[itemprop=description]')[0]->text();

        $main_img = $cardContent->find('.gallery-img-frame')[0];
        $row['main_image'] = $main_img->attr('data-url');

        $img_list = [];
        $i = 0;
        foreach($cardContent->find('.gallery-list-item-link') as $img){
            $img_list[$i] = $img->find('img')[0]->attr('src');
            $i++;
        }
        $row['img_list'] = $img_list;

        $param_list = [];
        $i = 0;
        foreach($cardContent->find('.item-params-list-item') as $param){
            $param;
            $param_list[$i][0] = $param->find('.item-params-label')[0]->text();
            $param_list[$i][1] = $param->text();
            $i++;
        }
        $row['param_list'] = $param_list;
    }

}