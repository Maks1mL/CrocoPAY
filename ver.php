<?php 

$curl = curl_init(); 

curl_setopt_array($curl, array( 
 CURLOPT_URL => 'https://crocopay.app/api/v2/access-token', 
 CURLOPT_RETURNTRANSFER => true, 
 CURLOPT_ENCODING => '', 
 CURLOPT_MAXREDIRS => 10, 
 CURLOPT_TIMEOUT => 0, 
 CURLOPT_FOLLOWLOCATION => true, 
 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, 
 CURLOPT_CUSTOMREQUEST => 'POST', 
 CURLOPT_POSTFIELDS => array('client_id' => 'Dd0LHNevkxvmPziXAPl0TcLfIPIJ8v','client_secret' => 'p8cZCREB9vGZSfnAH84Y6BCrfw1WA9qINQ0sDYRjPgzEjtfLWuTcGgiN5nev8mfr2dA9qqpIYci2pL0lqjMNPjIJpjGRSvb0QBV9'), 
)); 

$response = curl_exec($curl); 

curl_close($curl); 
echo $response; 

?>