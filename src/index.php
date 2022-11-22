<?php
    session_start();

    const __ROOT = '';
    const __DB_HOST = '';
    const __DB_USER = '';
    const __DB_PASSWORD = '';
    const __DB_NAME = '';

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    require_once(__ROOT . '/src/models/RouteManager.php');
    $Router = new RouteManager();

    $Router->addRoute('/test', 'src/routes/test.php');

    $Router->run();

