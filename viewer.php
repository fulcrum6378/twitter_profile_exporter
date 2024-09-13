<?php
require 'Database.php';

$target = '1754604672583913472';
$db = new Database($target);

if (!isset($_GET['user'])) die("No user ID detected!");
$uid = $_GET['user'];
$u = $db->queryUser($uid);
if (!$u) die("Unknown user ID: $uid");

?><!DOCTYPE html>
<html lang="">
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#1DA1F2">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#222222">
  <meta charset="UTF-8">
  <title><?php echo "{$u['name']} (@{$u['user']})"; ?></title>

  <script src="tools/jquery-3.7.1.min.js"></script>
  <link href="tools/bootstrap.min.css" rel="stylesheet">
  <script src="tools/bootstrap.bundle.min.js"></script>
  <style>
      body {
          margin: auto;
          font-family: TwitterChirp, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      }
      #banner {
          width: 100%;
      }
      header {
          margin-top: -11%;
          padding: 0 1rem;
      }
      #photo {
          width: 23%;
          border-radius: 50%;
          border: 4px white solid;
      }
      .icon {
          width: 21px;
          height: 21px;
          opacity: 0.7;
      }
  </style>
</head>
<body class="col-6 border-start border-end">
<img id="banner" src="<?php echo "media/$uid/" . str_replace('/', '_', $u['banner']) . '.jfif' ?>">
<header class="border-bottom">
  <img id="photo" src="<?php echo "media/$uid/" . str_replace('/', '_', $u['photo']) ?>">

  <p class="fs-3 fw-bold mb-0 mt-2"><?php echo "{$u['name']}"; ?></p>
  <p class="fs-6 text-body-secondary"><?php echo "@{$u['user']}"; ?></p>

  <p class="fs-6 mb-2"><?php echo "{$u['description']}" ?></p>
  <p class="fs-6 mb-2 text-body-secondary">
      <?php if ($u['location'] != null) : ?>
        <img class="icon" src="icons/location.svg">
          <?php echo $u['location'] ?>
        &nbsp;&nbsp;&nbsp;
      <?php endif; ?>
      <?php if ($u['link'] != null) : ?>
        <img class="icon" src="icons/link.svg">
        <a href="<?php echo $u['link'] ?>" target="_blank">
            <?php echo str_replace('https://', '', $u['link']) ?></a>
        &nbsp;&nbsp;&nbsp;
      <?php endif; ?>
    <img class="icon" src="icons/date.svg">
    Joined <?php echo date('j F Y, G:i:s', $u['created_at']) ?>
  </p>

  <p class="text-body-secondary">
    <span class="text-body fw-semibold"><?php echo "{$u['following']}" ?></span> Following
    &nbsp;&nbsp;&nbsp;
    <span class="text-body fw-semibold"><?php echo "{$u['followers']}" ?></span> Followers
  </p>
</header>
</body>
</html>