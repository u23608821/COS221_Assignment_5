<?php

header("Access-Control-Allow-Origin: *"); //allow all cors
header("Content-Type: application/json"); 
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"), true);

$response = [
    "status" => "success",
    "message" => "received by PHP",
    "data" => $data
];

echo json_encode($response);


?>