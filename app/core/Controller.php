<?php

class Controller{
    //Подключаем нужный нам шаблон
    protected function view($view, $data = []){
        require_once 'app/views/' . $view . '.php';
    }
}