<?php
require 'Database.php';

$target = '1754604672583913472';
$db = new Database($target);

if (!isset($_GET['u'])) die("No user ID detected!");
$uid = $_GET['u'];
$u = $db->queryUser($uid);
if (!$u) die("Unknown user ID: $uid");

# constants
$rtl = ['fa', 'ar', 'he'];

/*
http://localhost:290/viewer.php?u=1754604672583913472
http://localhost:290/viewer.php?u=2286930721
https://getbootstrap.com/docs/5.3/components/navs-tabs/
https://www.w3schools.com/jsref/prop_loc_search.asp
*/

?><!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#1DA1F2">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#222222">
  <meta charset="UTF-8">
  <title><?php echo "{$u['name']} (@{$u['user']})"; ?></title>

  <link href="frontend/bootstrap.min.css" rel="stylesheet">
  <link href="frontend/viewer.css" rel="stylesheet">

  <script src="frontend/jquery-3.7.1.min.js"></script>
  <script src="frontend/bootstrap.bundle.min.js"></script>
</head>
<body class="col-6 border-start border-end">
<img id="banner" src="<?php echo "media/$target/$uid/" .
    str_replace('/', '_', $u['banner']) . '.jfif' ?>">
<header class="border-bottom">
  <img id="photo" src="<?php echo "media/$target/$uid/" .
      str_replace('/', '_', $u['photo']) ?>">

  <p class="fs-3 fw-bold mb-0 mt-2"><?php echo "{$u['name']}"; ?></p>
  <p class="fs-6 text-body-secondary"><?php echo "@{$u['user']}"; ?></p>

  <p class="fs-6 mb-2"><?php echo "{$u['description']}" ?></p>
  <p class="fs-6 mb-2 text-body-secondary">
      <?php if ($u['location'] != null) : ?>
        <img class="icon" src="frontend/icons/location.svg">
          <?php echo $u['location'] ?>
        &nbsp;&nbsp;&nbsp;
      <?php endif; ?>
      <?php if ($u['link'] != null) : ?>
        <img class="icon" src="frontend/icons/link.svg">
        <a href="<?php echo $u['link'] ?>" target="_blank">
            <?php echo str_replace('https://', '', $u['link']) ?></a>
        &nbsp;&nbsp;&nbsp;
      <?php endif; ?>
    <img class="icon" src="frontend/icons/date.svg">
    Joined <?php echo date('j F Y, G:i:s', $u['created_at']) ?>
  </p>

  <p class="text-body-secondary">
    <span class="text-body fw-semibold"><?php echo "{$u['following']}" ?></span> Following
    &nbsp;&nbsp;&nbsp;
    <span class="text-body fw-semibold"><?php echo "{$u['followers']}" ?></span> Followers
  </p>
</header>

<nav>
  <ul class="nav nav-tabs">
    <li class="nav-item">
      <a class="nav-link active" aria-current="page" href="#">Tweets</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#">Replies</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#">Media</a>
    </li>
  </ul>
</nav>

<main>
    <?php
    $results = $db->queryTweets($uid, $_GET['section']);
    while ($twt = $results->fetchArray()) : ?>
      <article class="border-bottom" dir="<?php echo (in_array($twt['lang'], $rtl)) ? 'rtl' : 'ltr' ?>">
          <?php echo $twt['text'] ?>
      </article>
    <?php endwhile; ?>
</main>
</body>
</html>