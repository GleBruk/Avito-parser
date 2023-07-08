<?php
//При обращении, функция определяет изначальное значение у конкретного поля ввода
function POST($key, $default='')
{
    //Если значение для поля было заполнено ранее, то оно будет в массиве POST, иначе возвращаем значение
    //по умолчанию, указанное при вызове функции
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    } else {
        return $default;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Парсер Авито</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <style type="text/css">
    h1 {margin:20px 0 15px; font-size:24px;}
    .avito-form > div {margin-right:10px;}
    </style>
  </head>
  <body>

<div class="container-fluid">

    <h1>Парсер Авито</h1>

    <form class="form-inline avito-form" action="/avito/parse" method="post">
      <div class="form-group">
        <label><a href="<?=POST('url', 'https://www.avito.ru/syktyvkar/avtomobili?radius=200')?>" target="_blank">URL</a></label>
        <input type="text" class="form-control" name="url" value="<?=POST('url', 'https://www.avito.ru/syktyvkar/avtomobili?radius=200')?>" style="width:400px;">
      </div>
      <div class="form-group">
        <label>Показать как</label>
        <?php
        //Способы вывода данных
        $showAs = [
            'table' => 'Таблицей',
            'print_r' => 'print_r'
        ];
        ?>
        <select name="display" class="form-control">
            <?php
            //Если в массиве POST указан способ вывода, то указываем его в поле
            foreach ($showAs as $k => $v) {
                $add = '';
                if ($_POST['display'] == $k) {
                	$add = ' selected';
                }
                echo '<option'.$add.' value="'.$k.'">'.$v.'</option>';
            }
            ?>
        </select>
      </div>
      <div class="form-group">
        <label>Загружать до</label>
        <input type="number" class="form-control" name="maxPage" value="<?=POST('maxPage', 1)?>" style="width:70px;">
      </div>
      <div class="form-group">
        <label>Sleep</label>
        <input type="text" class="form-control" name="sleep_min" value="<?=POST('sleep_min', 2)?>" style="width:50px;">
        <input type="text" class="form-control" name="sleep_max" value="<?=POST('sleep_max', 5)?>" style="width:50px;">
      </div>
      <div class="checkbox">
        <label><input type="checkbox" <?php if ($_POST['load-card']) echo 'checked' ?> name="load-card" value="1"> Загружать карточку</label>
      </div>
      <button type="submit" class="btn btn-default">Выполнить</button>
    </form>

<hr />

<div class="row">
    <div class="col-md-6">
<?php
    //Выводим данные в виде print_r
    if ($_POST['display'] == 'print_r') {
    	echo '<pre>'; print_r($data); echo '</pre>';
    }

    //Выводим данные в виде таблицы
    if ($_POST['display'] == 'table') {
?>

<table class="table table-condensed table-bordered table-hover" style="width:auto">
<tr>
    <th>Название</th>
    <th>Стоимость товара</th>
    <th>Телефон</th>
</tr>
<?php
foreach ($data as $k => $row) {
    ?>
    <tr>
        <td><?=$row['name']?></td>
        <td class="text-right"><?=number_format($row['price'], 0, ' ', ' ')?></td>
        <td><a href="/avito/loadPhone/<?=$row['link']?>">разобрать телефон</a></td>
    </tr>
    <?php
}
?>
</table>
    </div>
    <div class="col-md-6" id="results">
        <?php
        }
        if(isset($data['image'])) {
            ?>
           <p><img src="<?=$data['image']?>" alt="" /></p>
            <?php
                if ($data['result']) {
                    echo '<h2 class="text-success">Результат - '.$data['result'].'</h2>';
                } else {
                    echo '<h2 class="text-danger">Ничего не получилось</h2>';
                }
        }
        ?>
    </div>
</div>
</div>
  </body>
</html>