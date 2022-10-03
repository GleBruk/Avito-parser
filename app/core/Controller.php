<?php

class Controller{
    //Подключаем нужную нам модель
    protected function model($model) {
        require_once 'app/models/' . $model . '.php';
        return new $model();
    }

    //Подключаем нужный нам шаблон
    protected function view($view, $data = []){
        require_once 'app/views/' . $view . '.php';
    }
}