<?php
header('Content-Type: text/plain');

# target database
$target = $argv[1] ?? $_GET['t'] ?? null;
if ($target == null) die("No target detected!");
require 'modules/Database.php';
$db = new Database($target);

# read & print tweets
$tweets = $db->queryTweets($target, length: 0);
while ($ent = $tweets->fetchArray())
    echo date('Y/m/d, H:i:s', $ent['time']) . ' : ' . $ent['text'] . "\n\n";
