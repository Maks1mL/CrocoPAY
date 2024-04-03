<?php
$json_str = file_get_contents('php://p2pkassa.online/api/v1/json?project_id=278&order_id=8&amount=1000&apikey=2ynf1rxpt37wke06ilojd5mz&country=ua&method=card');
 
# Получить объект
$json_obj = json_decode($json_str);
echo $json_obj;
?>