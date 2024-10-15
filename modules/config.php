<?php

const TARGETS_PATH = 'targets.json';

function readTargets(): array {
    return json_decode(
        file_exists(TARGETS_PATH)
            ? file_get_contents(TARGETS_PATH)
            : '{}', true);
}

function writeTargets(array $data): void {
    uasort($data, function ($a, $b): int {
        return strcmp($a['user'], $b['user']);
    });
    $f = fopen(TARGETS_PATH, 'w');
    fwrite($f, json_encode($data, JSON_PRETTY_PRINT));
    fclose($f);
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: text/plain');
    chdir('../');

    $targets = readTargets();
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'PUT':
            if (!isset($_GET['u'])) die('Username not specified!');
            require 'API.php';
            $api = new API();
            $res = $api->userByScreenName($_GET['u']);
            if ($res == '') die("Couldn't connect to Twitter!");
            $res = json_decode($res);
            if (!property_exists($res, 'data') || !property_exists($res->data, 'user'))
                die("Such a user doesn't exist!");

            $user = $res->data->user->result;
            if (!array_key_exists($_GET['id'], $targets))
                $targets[$user->rest_id] = array('user' => $user->legacy->screen_name, 'last' => 0);
            else
                $targets[$user->rest_id]['user'] = $user->legacy->screen_name;
            echo 'Done';
            break;
        case 'DELETE':
            if (!isset($_GET['t'])) die('Target not specified!');
            if (!array_key_exists($_GET['t'], $targets)) die("Target doesn't exist!");
            unset($targets[$_GET['t']]);
            echo 'Done';
            break;
    }
    writeTargets($targets);
}
