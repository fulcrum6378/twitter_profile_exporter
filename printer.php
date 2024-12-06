<?php
header('Content-Type: text/plain');

# target database
$target = $argv[1] ?? $_GET['t'] ?? null;
if ($target == null) die("No target detected!");
require __DIR__ . '/modules/Database.php';
$db = new Database($target);
$u = $db->queryUser($target);
//header("Content-Disposition: attachment; filename=\"{$u['user']}.txt\";" );

# print user details
$createdAt = date('j F Y, H:i:s', $u['created_at']);
$tweetCount = $db->countTweets($target);
echo "{$u['name']} (@{$u['user']})

Joined at $createdAt.
Location: {$u['location']}
{$u['following']} following, {$u['followers']} followers, $tweetCount tweets
Bio:
{$u['description']}


";

# read & print tweets
$tweets = $db->queryTweets($target, length: 0);
while ($ent = $tweets->fetchArray())
    echo date('Y/m/d, H:i:s', $ent['time']) . ' : ' . $ent['text'] . "\n\n";
