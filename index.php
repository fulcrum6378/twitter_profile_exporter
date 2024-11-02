<?php
require __DIR__ . '/modules/config.php';
$targets = readTargets();

if (count($targets) != 1)
    require __DIR__ . '/manager.php';
else {
    $target = array_key_first($targets);
    require __DIR__ . '/viewer.php';
}
