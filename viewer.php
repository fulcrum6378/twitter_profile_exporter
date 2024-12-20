<?php

# target database
if (isset($_GET['t'])) $target = $_GET['t'];
if (!isset($target)) die("No target detected!");
require __DIR__ . '/modules/Database.php';
$db = new Database($target, true);

# user
$users = array();
$uid = $_GET['u'] ?? $target;
$u = u($uid);
if ($u == -1)
    die('Database is locked!');
if ($u == -2) {
    if ($uid == $target) {
        header("Location: crawler.php?t=$target&sect=1&max_entries=" . Database::PAGE_LENGTH . '&delay=0');
        // crawler won't be able to redirect back neither via PHP nor HTML\JS!
        die();
    } else die("Unknown user ID: $uid");
}

# page
$section = isset($_GET['sect']) ? intval($_GET['sect']) : 1;
$page = isset($_GET['p']) ? (intval($_GET['p']) - 1) : 0;
$pageLength = isset($_GET['length']) ? intval($_GET['length']) : Database::PAGE_LENGTH;
$tweets = $db->queryTweets($uid, $section, $page, $pageLength);

# pagination
const MAX_PAGE_LINKS = 3;
$tweetCount = $db->countTweets($uid, $section);
if ($tweetCount == 0) {
    $pRng = array(0);
    $pageCount = 1;
} else {
    $pageCount = ceil($tweetCount / $pageLength);
    $pMin = 0;
    $pMax = $pageCount - 1;
    if ($page > MAX_PAGE_LINKS) $pMin = $page - MAX_PAGE_LINKS;
    if (($pMax - $page) > MAX_PAGE_LINKS) $pMax = $page + MAX_PAGE_LINKS;
    $pRng = range($pMin, $pMax);
    if ($pMin > 0) array_unshift($pRng, 0);
    if ($pMax < $pageCount - 1) $pRng[] = $pageCount - 1;
}

# miscellaneous
require __DIR__ . '/frontend/install.php';  // install the front-end assets if needed
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

  <link rel="icon" href="frontend/icons/twitter.svg" sizes="any" type="image/svg+xml">
  <link href="frontend/bootstrap.min.css" rel="stylesheet">
  <link href="frontend/viewer.css" rel="stylesheet">

  <script src="frontend/jquery.min.js"></script>
  <script src="frontend/bootstrap.bundle.min.js"></script>
  <script src="frontend/bootstrap-auto-dark-mode.js"></script>
  <script src="frontend/query.js"></script>
</head>

<body class="container border-start border-end">
<?php if ($u['banner'] != null) : ?>
<img id="banner" src="<?= profileBanner($u) ?>">
<?php else : ?>
<div id="banner"></div>
<?php endif ?>
<header>
  <figure>
    <img id="photo" src="<?= profilePhoto($u) ?>">
  </figure>

  <div id="actions">
    <a href="printer.php?t=<?= $target ?>" target="_blank">
      <img id="print" class="btn btn-light border" src="frontend/icons/share.svg"
           title="Get a plain text file containing all independent tweets and retweets from this profile,
usually to be analysed by AI.">
    </a>
    <img id="crawl" class="btn btn-light border" src="frontend/icons/connect.svg"
         title="Crawl for tweets on Twitter/X.">
  </div>

  <p class="fs-3 fw-bold mb-0 mt-2"><?= "{$u['name']}"; ?></p>
  <p class="fs-6 text-body-secondary">
    <a href="https://x.com/<?= "@{$u['user']}"; ?>" target="_blank"><?= "@{$u['user']}"; ?></a>
  </p>
  <p class="fs-6 mb-2"><?= "{$u['description']}" ?></p>

  <p class="fs-6 mb-2 text-body-secondary">
<?php if ($u['location'] != null) : ?>
    <img class="icon" src="frontend/icons/location.svg">
    <?= $u['location'] ?>

    &nbsp;&nbsp;&nbsp;
<?php endif ?>
<?php if ($u['link'] != null) : ?>
    <img class="icon" src="frontend/icons/link.svg">
    <a href="<?= $u['link'] ?>" target="_blank" id="link">
      <?= str_replace('https://', '', $u['link']) ?></a>
    &nbsp;&nbsp;&nbsp;
<?php endif ?>
    <img class="icon" src="frontend/icons/date.svg">
    Joined <?= date('j F Y, H:i:s', $u['created_at']) ?>

  </p>

  <p class="text-body-secondary">
    <span class="text-body fw-semibold"><?= n($u['following']) ?></span> Following
    &nbsp;&nbsp;&nbsp;
    <span class="text-body fw-semibold"><?= n($u['followers']) ?></span> Followers
    &nbsp;&nbsp;&nbsp;
    <span class="text-body fw-semibold"><?= n($db->countTweets($uid)) ?></span> Tweets
  </p>
</header>

<a href="manager.php" id="home">
  <img class="btn btn-light border" src="frontend/icons/back.svg"
       title="Back to the Profile Manager">
</a>

<nav class="text-center navbar border-top border-bottom fs-5">
  <div class="col nav-item">
    <a class="nav-link<?= $section == 1 ? ' fw-bold' : '' ?>" href="javascript:void(0)" id="tweets">Tweets</a>
  </div>
  <div class="col nav-item">
    <a class="nav-link<?= $section == 2 ? ' fw-bold' : '' ?>" href="javascript:void(0)" id="replies">Replies</a>
  </div>
  <div class="col nav-item">
    <a class="nav-link<?= $section == 3 ? ' fw-bold' : '' ?>" href="javascript:void(0)" id="mentions">Mentions</a>
  </div>
  <div class="col nav-item">
    <a class="nav-link<?= $section == 4 ? ' fw-bold' : '' ?>" href="javascript:void(0)" id="media">Media</a>
  </div>
</nav>

<main>
<?php
while ($ent = $tweets->fetchArray()) :
    $thread = array($ent);
    $bottomId = $ent['id'];
    if ($section != 1) {
        $rep = $ent['reply'];
        while ($rep != null) {
            $reply = $db->queryTweet($rep);
            if (!$reply) break;
            $thread[] = $reply;
            $rep = $reply['reply'];
        }
        $thread = array_reverse($thread);
    }
    foreach ($thread as $twt) :
        $isRetweet = $twt['retweet'] != null && $twt['is_quote'] == 0;
        if ($isRetweet) {
            $retweetId = $twt['id'];
            $retweetDate = date('Y.m.d - H:i:s', $twt['time']);
            $twt = $db->queryTweet($twt['retweet']);
            $bottomId = $twt['id'];
        }
        $tu = u($twt['user'], true);
        $stat = $db->queryTweetStat($twt['id']);
?>
  <section<?= $bottomId == $twt['id'] ? ' class="border-bottom"' : '' ?>>
    <figure>
      <img class="author mt-<?= $isRetweet ? 4 : 2 ?>" src="<?= profilePhoto($tu) ?>">
<?php
        if ($bottomId != $twt['id']) :
?>
        <div class="continuum border border-2"></div>
<?php
        endif;
?>
      </figure>
      <article>
<?php
        if ($isRetweet) :
?>
        <p class="retweeted">
          <img class="icon" src="frontend/icons/retweet.svg">
          <a href="https://x.com/<?= $u['user'] ?>/status/<?= $retweetId ?>"
              target="_blank" class=" text-body-tertiary">
            <?= $u['name'] ?> retweeted at <?= $retweetDate ?>

          </a>

        </p>
<?php
        endif;
?>
        <p class="author text-body-secondary">
          <a href="viewer.php?t=<?= $target ?>&u=<?= $twt['user'] ?>&sect=1" target="_blank">
            <span class="text-body fw-bold"><?= $tu['name'] ?></span>
            @<span><?= $tu['user'] ?></span>
          </a>
          ·
          <a href="https://x.com/<?= $tu['user'] ?>/status/<?= $twt['id'] ?>" target="_blank">
            <time><?= date('Y.m.d - H:i:s', $twt['time']) ?></time>
          </a>
        </p>
        <p class="tweet" dir="<?= (in_array($twt['lang'], $rtl)) ? 'rtl' : 'ltr' ?>">
          <?= str_replace("\n", "<br>\n", $twt['text']) ?>

        </p>

<?php /** @noinspection DuplicatedCode */
        if ($twt['media'] != null) :
            $mediaIds = explode(',', $twt['media']);
?>
        <div class="media media-<?= count($mediaIds) ?>">
<?php
            foreach ($mediaIds as $medId) :
                $med = $db->queryMedium($medId);
                $medSrc = mediumSrc($twt['user'], $med);
                if ($med['ext'] != 'mp4') : ?>
          <img src="<?= $medSrc ?>" class="border">
<?php
                else :
?>
          <video controls class="border">
            <source src="<?= $medSrc ?>" type="video/mp4">
          </video>
<?php
                endif;
?>
<?php
            endforeach;
?>
        </div>
<?php
        endif;
?>

<?php
        if ($twt['is_quote'] == 1) :
            $qut = $db->queryTweet($twt['retweet']);
?>
        <div class="quote border">
<?php
            if ($qut) :
                $quu = u($qut['user'], true);
?>
          <p class="author text-body-secondary">
            <img src="<?= profilePhoto($quu) ?>">
            <a href="viewer.php?t=<?= $target ?>&u=<?= $qut['user'] ?>&sect=1" target="_blank">
              <span class="text-body fw-bold"><?= $quu['name'] ?></span>
              @<span><?= $quu['user'] ?></span>
            </a>
            ·
            <time><?= date('Y.m.d - H:i:s', $qut['time']) ?></time>
          </p>
          <p dir="<?= (in_array($qut['lang'], $rtl)) ? 'rtl' : 'ltr' ?>">
            <?= $qut['text'] ?>
          </p>
<?php /** @noinspection DuplicatedCode */
                if ($qut['media'] != null) :
                    $mediaIds = explode(',', $qut['media']);
?>
          <div class="media media-<?= count($mediaIds) ?>">
<?php
                    foreach ($mediaIds as $medId) :
                        $med = $db->queryMedium($medId);
                        $medSrc = mediumSrc($qut['user'], $med);
                        if ($med['ext'] != 'mp4') :
?>
            <img src="media/<?= $medSrc ?>" class="border">
<?php
                        else :
?>
            <video controls class="border">
              <source src="media/<?= $medSrc ?>" type="video/mp4">
            </video>
<?php
                        endif;
?>
<?php
                    endforeach;
?>
          </div>
<?php
                endif;
?>
<?php
            else:
?>
          The quoted tweet is unavailable.
<?php
            endif;
?>
        </div>
<?php
        endif;
?>
        <div class="tweetStat">
          <p><img src="frontend/icons/reply.svg"> <?= n($stat['reply']) ?></p>
          <p><img src="frontend/icons/retweet.svg"> <?= n($stat['retweet']) ?></p>
          <p><img src="frontend/icons/quote.svg"> <?= n($stat['quote']) ?></p>
          <p><img src="frontend/icons/like.svg"> <?= n($stat['favorite']) ?></p>
          <p><img src="frontend/icons/stat.svg"> <?= n($stat['view']) ?></p>
          <p><img src="frontend/icons/bookmark.svg"> <?= n($stat['bookmark']) ?></p>
        </div>
      </article>
    </section>

<?php
    endforeach;
endwhile;
?>
</main>

<nav id="pagination">
  <ul class="pagination justify-content-center">
<?php if ($page == 0) : ?>
    <li class="page-item disabled"><a class="page-link">&#8592;</a></li>
<?php else : ?>
    <li class="page-item"><a class="page-link" href="javascript:void(0)" data-p="<?= $page ?>">&#8592;</a></li>
<?php endif ?>
<?php foreach ($pRng as $p) : ?>
<?php if ($page == $p) : ?>
    <li class="page-item active" aria-current="page"><a class="page-link"><?= $p + 1 ?></a></li>
<?php else : ?>
    <li class="page-item"><a class="page-link" href="javascript:void(0)"><?= $p + 1 ?></a></li>
<?php endif ?>
<?php endforeach ?>
<?php if ($page == $pageCount - 1) : ?>
    <li class="page-item disabled"><a class="page-link">&#8594;</a></li>
<?php else : ?>
    <li class="page-item"><a class="page-link" href="javascript:void(0)" data-p="<?= $page + 2 ?>">&#8594;</a></li>
<?php endif ?>
  </ul>
</nav>

<div id="crawler" class="modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Crawl</h4>
      </div>
      <div class="modal-body">
        <form id="crawlForm">
          <input type="hidden" id="target" name="t" value="<?= $target ?>">
          <div>
            Profile:
            &nbsp;&nbsp;
            <input class="btn-check" type="radio" name="sect" value="1" id="crwPfTweets">
            <label class="btn btn-outline-primary" for="crwPfTweets">Tweets</label>
            <input class="btn-check" type="radio" name="sect" value="2" id="crwPfReplies" checked>
            <label class="btn btn-outline-primary" for="crwPfReplies">Replies</label>
            <input class="btn-check" type="radio" name="sect" value="3" id="crwPfMedia">
            <label class="btn btn-outline-primary" for="crwPfMedia">Media</label>
            <input class="btn-check" type="radio" name="sect" value="4" id="crwPfLikes">
            <label class="btn btn-outline-primary" for="crwPfLikes">Likes</label>
          </div>

          <div class="mt-4">
            Search:
            &nbsp;&nbsp;
            <input class="btn-check crwSc crwUnsorted" type="radio" name="sect" value="1" id="crwScTop">
            <label class="btn btn-outline-primary" for="crwScTop">Top</label>
            <input class="btn-check crwSc" type="radio" name="sect" value="2" id="crwScLatest">
            <label class="btn btn-outline-primary" for="crwScLatest">Latest</label>
            <input class="btn-check crwSc" type="radio" name="sect" value="3" id="crwScMedia">
            <label class="btn btn-outline-primary" for="crwScMedia">Media</label>
          </div>
          <input type="text" name="search" id="crwSearch" class="form-control mt-2" placeholder="Search query..."
                 value="from:<?= $u['user'] ?> " disabled>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" role="switch" name="update_only" value="1"
                   id="crwUpdateOnly" checked>
            <label class="form-check-label" for="crwUpdateOnly">Crawl only for newest tweets</label>
          </div>

          <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" role="switch" name="download_media" value="1"
                   id="crwDownloadMedia" checked>
            <label class="form-check-label" for="crwDownloadMedia">Download media</label>
          </div>

          <div class="d-flex mt-2">
            <input type="number" name="delay" id="crwDelay" class="form-control" value="5">
            <label class="form-label mt-2 ms-2" for="crwDelay">seconds waiting between each request</label>
          </div>
        </form>
        <code id="crawlEvents"></code>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="crawlCancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="crawlGo">Go</button>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="crawlHalt">Halt!</button>
      </div>
    </div>
  </div>
</div>

<script src="frontend/viewer.js"></script>
</body>
</html><?php
function u(string|int $id, bool $defaultNullArray = false): array|int|null {
    global $db, $users;
    if (is_int($id)) $id = strval($id);
    if (array_key_exists($id, $users))
        return $users[$id];
    else {
        $nu = $db->queryUser($id);
        if (gettype($nu) != 'array')
            return !$defaultNullArray ? $nu : array('user' => null, 'name' => null, 'photo' => null);
        if (!$defaultNullArray) $users[$id] = $nu;
        return $nu;
    }
}

function profileBanner(array $user): string {
    global $target;
    if ($user['banner'] != null) {
        $medium = "media/$target/{$user['id']}/" . str_replace('/', '_', $user['photo']) . '.jfif';
        if (is_file($medium)) return $medium;
        return Database::TWIMG_BANNERS . $user['banner'];
    }
    return ""; // FIXME
}

function profilePhoto(array $user): string {
    global $target;
    if ($user['photo'] != null) {
        $medium = "media/$target/{$user['id']}/" . str_replace('/', '_', $user['photo']);
        if (is_file($medium)) return $medium;
        return Database::TWIMG_IMAGES . $user['photo'];
    }
    return "https://abs.twimg.com/sticky/default_profile_images/default_profile_200x200.png";
}

function mediumSrc(string $userId, array $med): string {
    global $target;
    $medium = "media/$target/$userId/{$med['id']}.{$med['ext']}";
    if (is_file($medium)) return $medium;
    return $med['url'];
}

function n(?int $num): string {
    if (is_null($num)) return '-';
    //if ($num >= 1000000000) return intval($num / 1000000000) . 'b';
    if ($num >= 1000000) return intval($num / 1000000) . 'm';
    if ($num >= 10000) return intval($num / 1000) . 'k';
    return $num;
}
