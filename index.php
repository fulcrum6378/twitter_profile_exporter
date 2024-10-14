<?php
require 'modules/config.php';
$targets = readTargets();

if (count($targets) != 1)
    require 'manager.php';
else {
    $target = array_key_first($targets);
    require 'viewer.php';
}
