<?php

const TARGETS_PATH = 'targets.json';

function readTargets(): array {
    return json_decode(
        file_exists(TARGETS_PATH)
            ? file_get_contents(TARGETS_PATH)
            : '{}', true);
}

function writeTargets(array $data): void {
    $f = fopen(TARGETS_PATH, 'w');
    fwrite($f, json_encode($data, JSON_PRETTY_PRINT));
    fclose($f);
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: text/plain');

    $config = readTargets();
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'PUT':
            if (!isset($_GET['id'])) die('Twitter ID not specified!');
            if (!isset($_GET['name'])) die('Person Name not specified!');
            if (!array_key_exists($_GET['id'], $config))
                $config[$_GET['id']] = array('name' => $_GET['name'], 'last' => 0);
            else
                $config[$_GET['id']]['name'] = $_GET['name'];
            echo 'Done';
            break;
        case 'DELETE':
            if (!isset($_GET['t'])) die('Target not specified!');
            if (!array_key_exists($_GET['t'], $config)) die("Target doesn't exist!");
            unset($config[$_GET['t']]);
            echo 'Done';
            break;
    }
    writeTargets($config);
}
