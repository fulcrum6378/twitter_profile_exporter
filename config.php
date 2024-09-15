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
