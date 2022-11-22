<?php
    include_once(__ROOT . '/src/index.php');
    global $Router;

    $results = $Router->DB->query("SELECT * FROM PUBLIC_misc")->fetchAll();

    echo json_encode(array_merge(
        array("success" => true),
        array("message" => "Your API Works")
    ));