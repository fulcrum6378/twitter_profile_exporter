<?php

# targets
if (!isset($targets)) {
    require __DIR__ . '/modules/config.php';
    $targets = readTargets();
}

# miscellaneous
require __DIR__ . '/frontend/install.php';  // install the front-end assets if needed
date_default_timezone_set("Asia/Tehran");

?><!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#1DA1F2">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#222222">
  <meta charset="UTF-8">
  <title>Twitter Profile Exporter</title>

  <link rel="icon" href="frontend/icons/twitter.svg" sizes="any" type="image/svg+xml">
  <link href="frontend/bootstrap.min.css" rel="stylesheet">

  <script src="frontend/jquery.min.js"></script>
  <script src="frontend/bootstrap.bundle.min.js"></script>
  <script src="frontend/bootstrap-auto-dark-mode.js"></script>
</head>

<body class="container border-start border-end px-0">
<table class="table">
  <thead>
  <tr>
    <th>Username</th>
    <th>Last Sync</th>
    <th>Actions</th>
  </tr>
  </thead>
  <tbody>
<?php foreach ($targets as $id => $u) : ?>
    <tr data-id="<?= $id ?>">
      <td><a href="viewer.php?t=<?= $id ?>" target="_blank"><?= $u['user'] ?></a></td>
      <td><?= ($u['last'] != 0) ? date('Y/m/d H:i:s', $u['last']) : 'never' ?></td>
      <td><a href="javascript:void(0)" class="delete">Delete</a></td>
    </tr>
<?php endforeach; ?>
  <tr>
    <td><input type="text" id="newUser" placeholder="New User..."></td>
    <td></td>
    <td><a href="javascript:void(0)" id="put">Add</a></td>
  </tr>
  </tbody>
</table>

<script src="frontend/manager.js"></script>
</body>
</html>