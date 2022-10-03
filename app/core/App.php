<?php

class App{
    protected $controller = 'Avito';
    protected $method = 'index';
    protected $params = '';

    public function __construct(){
        // Получаем введённый пользователем url в виде массива
        $url = $this->parseUrl();

        // Если существует указанный пользователем в url контроллер, то переменная
        // $controller присвоит его значение переведя первый символ в верхний регистр,
        // а из массива url удаляется элемент с названием контроллера
        if(file_exists('app/controllers/' . ucfirst($url[0]) . '.php')) {
            $this->controller = ucfirst($url[0]);
            unset($url[0]);
        }

        // Подключаем указанный пользователем контроллер. Если контроллер не был
        // указан, то подключается контроллер Home. Затем переменную $controller
        // делаем новым объектом подключенного контроллера
        require_once 'app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Если существует указанный пользователем в url метод, то переменная
        // $method присвоит его значение, а из массива url удаляется элемент
        // с названием метода
        if(isset($url[1])){
            if(method_exists($this->controller, $url[1])){
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // Если $url не является пустым массивом, то $params принимает его значение
        // обнулив индексы и объединив элементы в одну строку
        $this->params = $url ? (array)implode($url, '/') : [];

        // Вызываем метод контроллера и передаём параметры
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl() {
        // Берём введёный пользователем url, делим его по знаку "/" и возвращаем
        // в виде массива
        if(isset($_GET['url'])){
            return explode('/', filter_var(
                rtrim($_GET['url'], '/'),
                FILTER_SANITIZE_STRING
            ));
        }
    }
}