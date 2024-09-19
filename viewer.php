<?php

# target database
if (isset($_GET['t'])) $target = $_GET['t'];
if (!isset($target)) die("No target detected!");
require 'Database.php';
$db = new Database($target);

# user
$users = array();
$uid = $_GET['u'] ?? $target;
$u = u($uid);
if (!$u) die("Unknown user ID: $uid");

# page
$section = isset($_GET['section']) ? intval($_GET['section']) : 0;
$page = isset($_GET['p']) ? (intval($_GET['p']) - 1) : 0;
$pageLength = isset($_GET['length']) ? intval($_GET['length']) : 50;
$tweets = $db->queryTweets($uid, $section, $page, $pageLength);

# pagination
const MAX_PAGE_LINKS = 7;
$pageCount = ceil($db->countTweets($uid, $section) / $pageLength);
$pMin = 0;
$pMax = $pageCount - 1;
if ($page > MAX_PAGE_LINKS) $pMin = $page - MAX_PAGE_LINKS;
if (($pMax - $page) > MAX_PAGE_LINKS) $pMax = $page + MAX_PAGE_LINKS + 1;
$pRng = range($pMin, $pMax);
if ($pMin > 0) array_unshift($pRng, 0);
if ($pMax < $pageCount - 1) $pRng[] = $pageCount - 1;

# miscellaneous
$rtl = ['fa', 'ar', 'he'];
date_default_timezone_set('Asia/Tehran');

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
<body class="container border-start border-end">
<img id="banner" src="media/<?= "$target/$uid/" .
str_replace('/', '_', $u['banner']) . '.jfif' ?>">
<header>
  <figure>
    <img id="photo" src="media/<?= "$target/$uid/" . profilePhoto($u) ?>">
  </figure>

  <div id="actions">
    <img id="sync" class="btn btn-light border" src="frontend/icons/retweet.svg" data-target="<?= $target ?>">
  </div>

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
<?php
while ($twt = $tweets->fetchArray()) :
$isRetweet = $twt['retweet'] != null && $twt['is_quote'] == 0;
if ($isRetweet) {
    $retweetDate = date('Y.m.d - H:i:s', $twt['time']);
    $twt = $db->queryTweet($twt['retweet']);
}
$tu = u($twt['user']);
?>
  <section class="border-bottom">
    <img class="author mt-<?= $isRetweet ? 4 : 2 ?>" src="media/<?= "$target/{$twt['user']}/" . profilePhoto($tu) ?>">
    <article>
<?php if ($isRetweet) : ?>
      <p class="retweeted text-body-tertiary">
        <img class="icon" src="frontend/icons/retweet.svg">
        <?= $u['name'] ?> retweeted at <?= $retweetDate ?>

      </p>
<?php endif ?>
      <p class="author text-body-secondary">
        <a href="viewer.php?t=<?= $target ?>&u=<?= $twt['user'] ?>&section=1" target="_blank"
            class="link-body-emphasis link-underline-opacity-0">
          <span class="text-body fw-bold"><?= $tu['name'] ?></span>
          @<span><?= $tu['user'] ?></span>
        </a>
        · <time><?= date('Y.m.d - H:i:s', $twt['time']) ?></time>
      </p>
      <p class="tweet" dir="<?= (in_array($twt['lang'], $rtl)) ? 'rtl' : 'ltr' ?>">
<?= $twt['text'] ?>

      </p>
<?php if ($twt['media'] != null) :
    $mediaIds = explode(',', $twt['media']); ?>
      <div class="media media-<?= count($mediaIds) ?>">
<?php foreach ($mediaIds as $med) : ?>
<?php $ext = $db->queryMedium($med)['ext']; if ($ext != 'mp4') : ?>
        <img src="media/<?= "$target/{$twt['user']}/$med.$ext" ?>" class="border">
<?php else : ?>
        <video controls class="border">
          <source src="media/<?= "$target/{$twt['user']}/$med.mp4" ?>" type="video/mp4">
        </video>
<?php endif ?>
<?php endforeach ?>
      </div>
<?php endif ?>
<?php if ($twt['is_quote'] == 1) :
    $qut = $db->queryTweet($twt['retweet']);
    $quu = u($qut['user']);
?>
      <div class="quote border">
        <p class="author text-body-secondary">
          <img src="media/<?= "$target/{$qut['user']}/" . profilePhoto($quu) ?>">
          <a href="viewer.php?t=<?= $target ?>&u=<?= $qut['user'] ?>&section=1" target="_blank"
             class="link-body-emphasis link-underline-opacity-0">
            <span class="text-body fw-bold"><?= $quu['name'] ?></span>
            @<span><?= $quu['user'] ?></span>
          </a>
          · <time><?= date('Y.m.d - H:i:s', $qut['time']) ?></time>
        </p>
        <p dir="<?= (in_array($qut['lang'], $rtl)) ? 'rtl' : 'ltr' ?>">
          <?= $qut['text'] ?>
        </p>
      </div>
<?php endif ?>
    </article>
  </section>

<?php endwhile ?>
</main>

<nav id="pagination">
  <ul class="pagination">
<?php if ($page == 0) : ?>
    <li class="page-item disabled"><a class="page-link">Previous</a></li>
<?php else : ?>
    <li class="page-item"><a class="page-link" href="javascript:void(0)" data-p="<?= $page ?>">Previous</a></li>
<?php endif ?>
<?php foreach ($pRng as $p) : ?>
<?php if ($page == $p) : ?>
    <li class="page-item active" aria-current="page"><a class="page-link"><?= $p + 1 ?></a></li>
<?php else : ?>
    <li class="page-item"><a class="page-link" href="javascript:void(0)"><?= $p + 1 ?></a></li>
<?php endif ?>
<?php endforeach ?>
<?php if ($page == $pageCount - 1) : ?>
    <li class="page-item disabled"><a class="page-link">Next</a></li>
<?php else : ?>
    <li class="page-item"><a class="page-link" href="javascript:void(0)" data-p="<?= $page + 2 ?>">Next</a></li>
<?php endif ?>
  </ul>
</nav>

<script type="text/javascript" src="frontend/viewer.js"></script>
</body>
</html><?php
function u(string|int $id): false|array {
    global $db, $users;
    if (is_int($id)) $id = strval($id);
    if (array_key_exists($id, $users))
        return $users[$id];
    else {
        $nu = $db->queryUser($id);
        $users[$id] = $nu;
        return $nu;
    }
}

function profilePhoto(array $user): ?string {
    return str_replace('/', '_', $user['photo']);
}
