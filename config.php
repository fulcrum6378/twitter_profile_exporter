<?php

const CONFIG_PATH = 'config.json';

function readConfig(): array {
    return json_decode(
        file_exists(CONFIG_PATH)
            ? file_get_contents(CONFIG_PATH)
            : '{}', true);
}

function writeConfig(array $data): void {
    $f = fopen(CONFIG_PATH, 'w');
    fwrite($f, json_encode($data, JSON_PRETTY_PRINT));
    fclose($f);
}
