<?php

# target database
if (!isset($_GET['t'])) die("No target detected!");
$target = '1754604672583913472';
require 'Database.php';
$db = new Database($target);

# user
$uid = $_GET['u'] ?? $target;
$u = $db->queryUser($uid);
if (!$u) die("Unknown user ID: $uid");

# other parameters
$section = isset($_GET['section']) ? intval($_GET['section']) : 0;

# miscellaneous
$rtl = ['fa', 'ar', 'he'];
date_default_timezone_set("Asia/Tehran");

$results = $db->queryTweets($uid, $section);

?><!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#1DA1F2">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#222222">
  <meta charset="UTF-8">
  <title><?= "{$u['name']} (@{$u['user']})"; ?></title>

  <link href="frontend/bootstrap.min.css" rel="stylesheet">
  <link href="frontend/viewer.css" rel="stylesheet">

  <script src="frontend/jquery-3.7.1.min.js"></script>
  <script src="frontend/bootstrap.bundle.min.js"></script>
</head>
<body class="col-6 border-start border-end">
<img id="banner" src="<?= "media/$target/$uid/" .
str_replace('/', '_', $u['banner']) . '.jfif' ?>">
<header>
  <figure>
    <img id="photo" src="<?= "media/$target/$uid/" . str_replace('/', '_', $u['photo']) ?>">
  </figure>

  <p class="fs-3 fw-bold mb-0 mt-2"><?= "{$u['name']}"; ?></p>
  <p class="fs-6 text-body-secondary"><?= "@{$u['user']}"; ?></p>

  <p class="fs-6 mb-2"><?= "{$u['description']}" ?></p>
  <p class="fs-6 mb-2 text-body-secondary">
    <?php if ($u['location'] != null) : ?>
    <img class="icon" src="frontend/icons/location.svg">
    <?= $u['location'] ?>
    &nbsp;&nbsp;&nbsp;
    <?php endif ?>
    <?php if ($u['link'] != null) : ?>
    <img class="icon" src="frontend/icons/link.svg">
    <a href="<?= $u['link'] ?>" target="_blank"><?= str_replace('https://', '', $u['link']) ?></a>
    &nbsp;&nbsp;&nbsp;
    <?php endif ?>

    <img class="icon" src="frontend/icons/date.svg">
    Joined <?= date('j F Y, H:i:s', $u['created_at']) ?>

  </p>

  <p class="text-body-secondary">
    <span class="text-body fw-semibold"><?= "{$u['following']}" ?></span> Following
    &nbsp;&nbsp;&nbsp;
    <span class="text-body fw-semibold"><?= "{$u['followers']}" ?></span> Followers
  </p>
</header>

<nav class="text-center navbar border-top border-bottom fs-5">
  <div class="col nav-item">
    <a class="nav-link<?php if ($section == 0) echo ' fw-bold' ?>" href="javascript:void(0)" id="tweets">Tweets</a>
  </div>
  <div class="col nav-item">
    <a class="nav-link<?php if ($section == 1) echo ' fw-bold' ?>" href="javascript:void(0)" id="replies">Replies</a>
  </div>
  <div class="col nav-item">
    <a class="nav-link<?php if ($section == 2) echo ' fw-bold' ?>" href="javascript:void(0)" id="media">Media</a>
  </div>
</nav>

<main>
  <?php while ($twt = $results->fetchArray()) : ?>
  <article class="border-bottom">
  <time><?= date('Y/m/j H:i', $twt['time']) ?></time><br>
  <p dir="<?= (in_array($twt['lang'], $rtl)) ? 'rtl' : 'ltr' ?>">
<?= $twt['text'] ?>

  </p>
  </article>

<?php endwhile ?>
</main>

<script type="text/javascript" src="frontend/viewer.js"></script>
</body>
</html>