<?php

$fConfig = 'config.json';
$jConfig = file_exists('config.json')
    ? file_get_contents($fConfig)
    : '{targets:[]}';
$config = json_decode($jConfig);

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
<table class="table">
  <thead>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Last Sync</th>
    <th>View</th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($config->targets as $target) : ?>
    <tr>
      <td><?php echo $target->id ?></td>
      <td><?php echo $target->name ?></td>
      <td><?php echo $target->last ?></td>
      <td><a href="viewer.php?u=<?php echo $target->id ?>">GO</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>