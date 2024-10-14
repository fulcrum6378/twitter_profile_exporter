<?php
header('Content-Type: text/plain');

# target database
$target = $argv[1] ?? $_GET['t'] ?? null;
if ($target == null) die("No target detected!");
require 'modules/Database.php';
$db = new Database($target);

# global settings
if (isset($argv)) chdir(dirname($_SERVER['PHP_SELF']));

$deletable = 0;
$usersDir = "media/$target";
$users = $db->queryUsers();
while ($u = $users->fetchArray()) {
    if ($u['id'] == $target) continue;
    $mediaDir = "$usersDir/{$u['id']}";
    if (!is_dir($mediaDir)) continue;

    $photo = str_replace('/', '_', $u['photo']);
    $banner = str_replace('/', '_', $u['banner']) . '.jfif';
    foreach (scandir($mediaDir) as $med) {
        if (!str_contains($med, '_')) continue;
        if ($med != $photo && $med != $banner) {
            $path = "$mediaDir/$med";
            $deletable += filesize($path);
            echo "$path\n";
            unlink($path);
        }
    }
}
echo "\n" . ($deletable / 1048576) . ' megabytes removed.';
