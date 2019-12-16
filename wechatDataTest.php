<?php

header('Content-Type:application/json; charset=utf-8');
$return=[
"data"=>'test',
 "statusCode"=>0
];

die(json_encode($return));

?>