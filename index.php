<?php

require 'config.php';
$targets = readTargets();

date_default_timezone_set("Asia/Tehran");

?><!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#1DA1F2">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#222222">
  <meta charset="UTF-8">
  <title>Twitter Profile Exporter</title>

  <base target="_blank">
  <script src="frontend/jquery-3.7.1.min.js"></script>
  <link href="frontend/bootstrap.min.css" rel="stylesheet">
  <script src="frontend/bootstrap.bundle.min.js"></script>
</head>

<body>
<?php /*if (count($targets) > 1) :*/ ?>
<table class="table">
  <thead>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Last Sync</th>
    <th>Actions</th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($targets as $id => $u) : ?>
  <tr>
    <td><?= $id ?></td>
    <td><?= $u['name'] ?></td>
    <td><?= date('Y/m/j H:i', $u['last']) ?></td>
    <td>
      <a href="viewer.php?t=<?= $id ?>">View</a>
      &nbsp;
      <a href="crawler.php?t=<?= $id ?>" class="sync">Sync</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php /*endif*/ ?>
</body>
</html>