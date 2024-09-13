<?php
require 'Database.php';

# $_GET
$target = $_GET['target'];

$db = new Database($target);
$u = $db->queryUser($target);
if (!$u) die("Unknown user ID: $target");

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
          TwitterChirp, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      }
      #banner {
          width: 100%;
      }
      main {
          margin-top: -4rem;
          padding: 0 1rem;
      }
      #photo {
          width: 20%;
          border-radius: 50%;
          border: 4px white solid;
      }
  </style>
</head>
<body class="col-6 border-start border-end">
<img id="banner" src="<?php echo "media/$target/" . str_replace('/', '_', $u['banner']) . '.jfif' ?>">
<main>
  <img id="photo" src="<?php echo "media/$target/" . str_replace('/', '_', $u['photo']) ?>">

  <h2><?php echo "{$u['name']}"; ?></h2>
  <h4><?php echo "@{$u['user']}"; ?></h4>

</main>
</body>
</html>