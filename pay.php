<?php
$apikey = '2ynf1rxpt37wke06ilojd5mz';
$project_id = 278;
$order_id = mt_rand(1, 999999);
$amount = 1000;
$desc = 'Оплата услуги';

$data = [
    'project_id' => $project_id,
    'apikey' => $apikey,
    'order_id' => $order_id,
    'amount' => $amount,
    'desc' => $desc
];

$ch = curl_init('https://p2pkassa.online/api/v1/link');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
$result = curl_exec($ch);
curl_close($ch);

print($result);