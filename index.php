<?php

require 'API.php';

# Initiate the request
$api = new API();

$api->userTweets("1754604672583913472");

$j = fopen("test.json", "w");
fwrite($j, $res);
fclose($j);
