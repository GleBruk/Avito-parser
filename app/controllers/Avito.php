<?php
require_once 'app/controllers/Curl.php';
require_once 'app/controllers/AvitoContact.php';
require_once 'vendor/autoload.php';
use DiDom\Document;

class Avito extends Controller {

    public $loadCard;

    function __construct() {
        $this->curl = new Curl;
    }

    public function index(){
        $this->view('avito');
    }

    public function parse(){
        $this->loadCard = $_POST['load-card'];
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
        //Извлекаем id из url карточки
        preg_match('~_(\d+)$~i', $url, $a);
        $id = $a[1];
        $avitoContact = new AvitoContact;
        //Получаем url картинки телефона
        $data['phone_url'] = $avitoContact->getPhoneUrl( $id);
        //Загружаем и сохраняем картинку
        $imgContent = $this->curl->load($data['phone_url'], 0);
        $img = json_decode($imgContent);
        $data['image'] = $img->image64;
        $avitoContact->saveInFile($data['image'], 'phone.png');

        // Распознаем картинку
        $data['result'] = $avitoContact->recognize('phone.png');

        $this->view('avito', $data);
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
            //Парсим карточку
            if ($this->loadCard) {
                $this->parseCard($row['link'], $row);
            }

            $data[] = $row;
        }
        return $data;
    }

    public function parseCard($url, &$row){
       $content = $this->curl->load($url, 86400);
        $cardContent = new Document($content);
        $text = $cardContent->find('div[itemprop=description]')[0];
        if($text != null){
            $row['text'] = $text->text();
        } else{
            echo "<br>" . $url . "<br>";
            print_r($text);
        }
        $image = $cardContent->find('div[data-marker=image-frame/image-wrapper]')[0];
        if($image != null){
            $row['main_image'] = $image->attr('data-url');
        } else{
            echo "<br>" . $url . "<br>";
        }
    }

}