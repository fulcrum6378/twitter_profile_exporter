<?php
require 'API.php';
require 'Database.php';

# global settings
if (isset($argv)) chdir(dirname($_SERVER['PHP_SELF']));
set_time_limit(0);

# process the request
$target = $argv[1] ?? $_GET['t'];
$section = isset($_GET['sect']) ? match ($_GET['sect']) {
    '0' => ProfileSection::Tweets,
    '2' => ProfileSection::Media,
    default => ProfileSection::Replies
} : ProfileSection::Replies;
$useCache = isset($_GET['use_cache']) && $_GET['use_cache'] == '1';
$updateOnly = ($argv[2] ?? $_GET['update_only'] ?? '1') != '0';
/** entries not tweets; set to 0 in order to turn it off. */
$maxEntries = isset($_GET['max_entries']) ? intval($_GET['max_entries']) : 0;
$delay = isset($_GET['delay']) ? intval($_GET['delay']) : 10;
$verbose = ($argv[3] ?? isset($_GET['verbose']) ?? '0') == '1';

# submodules
$db = new Database($target, true);
$api = new API();

# constants
const TWIMG_IMAGES = 'https://pbs.twimg.com/profile_images/';
const TWIMG_BANNERS = 'https://pbs.twimg.com/profile_banners/';

# HTTP headers
header('X-Accel-Buffering: no');
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

# loop on consecutive requests
if ($useCache) {
    $cacheDir = "cache/$target";
    if (!file_exists($cacheDir)) mkdir($cacheDir, recursive: true);
}
$ended = false;
$cursor = null;
$iFetch = 1;
$parsedTweetsCount = 0;
$parsedUsers = array();
$iTarget = intval($target);
$lastSync = 0;
while (!$ended) {
    if ($useCache) {
        $cacheFile = "$cacheDir/$iFetch.json";
        $cacheExists = file_exists($cacheFile);
    }
    $doFetch = !$useCache || !$cacheExists;

    # fetch tweets from the Twitter/X API
    if ($doFetch) {
        $res = $api->userTweets($section, $target, $cursor);
        if ($res == "") error("Couldn't fetch tweets!");
        else if ($verbose) message("Fetched page $iFetch\n");
    }

    if ($useCache) {
        $j = fopen($cacheFile, $cacheExists ? 'r' : 'w');
        if (!$cacheExists)
            /** @noinspection PhpUndefinedVariableInspection (true negative) */
            fwrite($j, $res);
        else {
            $res = fread($j, filesize($cacheFile));
            if ($verbose) message("Using cached page $iFetch\n");
        }
        fclose($j);
    } else
        if ($lastSync == 0) $lastSync = time();

    /** @noinspection PhpUndefinedVariableInspection (true negative) */
    foreach (json_decode($res)->data->user->result->timeline_v2->timeline->instructions as $instruction) {
        switch ($instruction->type) {
            case 'TimelinePinEntry':
                parseEntry($instruction->entry);
                break;
            case 'TimelineAddEntries':
                if (count($instruction->entries) <= 2) {
                    if ($useCache) unlink($cacheFile);
                    $ended = true;
                }
                foreach ($instruction->entries as $entry) {
                    $ret = parseEntry($entry);
                    if (!$ret && $updateOnly) break 4;
                }
                break;
        }
        if ($maxEntries > 0 && $parsedTweetsCount >= $maxEntries) {
            $ended = true;
            break;
        }
    }
    $iFetch++;

    if ($doFetch && !$ended) {
        if ($verbose) message("Waiting in order not to be detected as a bot ($delay seconds)...\n");
        sleep($delay);
    }

    //if (ob_get_contents()) ob_end_flush();
    //flush();
    if (connection_aborted()) break;
}

function parseEntry(stdClass $entry): bool {
    global $cursor, $parsedTweetsCount;

    if (str_starts_with($entry->entryId, 'who-to-follow') ||
        str_starts_with($entry->entryId, 'cursor-top')) return true;
    if (str_starts_with($entry->entryId, 'cursor-bottom')) {
        $cursor = $entry->content->value;
        return true;
    }

    $ret = true;
    if (property_exists($entry->content, 'itemContent'))
        $ret = parseTweet($entry->content->itemContent->tweet_results->result);
    else foreach ($entry->content->items as $item)
        $ret = parseTweet($item->item->itemContent->tweet_results->result);

    $parsedTweetsCount++;
    return $ret;
}

function parseTweet(stdClass $tweet, ?int $retweetFromUser = null): bool {
    if (!property_exists($tweet, 'rest_id')) return true; // TweetTombstone
    global $db, $parsedUsers, $verbose, $iTarget;
    $tweetId = intval($tweet->rest_id);

    # User
    $userId = intval($tweet->core->user_results->result->rest_id);
    if (!in_array($userId, $parsedUsers)) {
        $ul = $tweet->core->user_results->result->legacy;
        $userExistsInDb = $db->checkIfRowExists($db->User, $userId);
        if (!$userExistsInDb) if ($verbose) message("Processing user @$ul->screen_name (id:$userId)\n");
        $link = property_exists($ul, 'url') ? $ul->entities->url->urls[0]->expanded_url : null;
        $pinnedTweet = (count($ul->pinned_tweet_ids_str) > 0) ? $ul->pinned_tweet_ids_str[0] : null;

        # process user images
        if (property_exists($ul, 'profile_image_url_https')) {
            $photoUrl = str_replace('_normal', '', $ul->profile_image_url_https);
            $photo = substr($photoUrl, strlen(TWIMG_IMAGES));
            download($photoUrl, str_replace('/', '_', $photo), $userId);
        } else
            $photo = null;
        if (property_exists($ul, 'profile_banner_url')) {
            $banner = substr($ul->profile_banner_url, strlen(TWIMG_BANNERS));
            download($ul->profile_banner_url, str_replace('/', '_', $banner) . '.jfif', $userId);
        } else
            $banner = null;

        # insert/update User
        if (!$userExistsInDb)
            $db->insertUser($userId,
                $ul->screen_name, $ul->name, $ul->description,
                strtotime($ul->created_at), $ul->location, $photo, $banner, $link,
                $ul->friends_count, $ul->followers_count,
                $ul->statuses_count, $ul->media_count,
                $pinnedTweet);
        else
            $db->updateUser($userId,
                $ul->screen_name, $ul->name, $ul->description,
                $ul->location, $photo, $banner, $link,
                $ul->friends_count, $ul->followers_count,
                $ul->statuses_count, $ul->media_count,
                $pinnedTweet);
        $parsedUsers[] = $userId;
    }

    # update TweetStat and end the function if the tweet already exists
    if ($db->checkIfRowExists($db->Tweet, intval($tweet->rest_id))) {
        $db->updateTweetStat($tweetId,
            $tweet->legacy->bookmark_count,
            $tweet->legacy->favorite_count,
            $tweet->legacy->quote_count,
            $tweet->legacy->reply_count,
            $tweet->legacy->retweet_count,
            property_exists($tweet->views, 'count') ? intval($tweet->views->count) : null);
        return !($userId == $iTarget);
    }


    if ($verbose) message("Processing tweet $tweetId\n");
    $important = $userId == $iTarget || $retweetFromUser == $iTarget;

    # main text
    $text = $tweet->legacy->full_text;

    # reply
    $replied_to = null;
    if (property_exists($tweet->legacy, 'in_reply_to_status_id_str'))
        $replied_to = intval($tweet->legacy->in_reply_to_status_id_str);

    # retweet & quote
    $retweet_of = null;
    $is_quote = false;
    if (property_exists($tweet->legacy, 'retweeted_status_result')) {
        $retweet_of = intval($tweet->legacy->retweeted_status_result->result->rest_id);
        parseTweet($tweet->legacy->retweeted_status_result->result, $userId); // ignore the returned value
    }
    if (property_exists($tweet, 'quoted_status_result')) {
        $retweet_of = intval($tweet->legacy->quoted_status_id_str);
        $is_quote = true;
        if (property_exists($tweet->quoted_status_result, 'result'))
            parseTweet($tweet->quoted_status_result->result, $userId); // ignore the returned value
    }

    # insert Media(s) and download file(s) if it's not a retweet
    $media = null;
    if (property_exists($tweet->legacy->entities, 'media') &&
        ($retweet_of == null || $is_quote)
    ) foreach ($tweet->legacy->entities->media as $med) {
        if ($media == null) $media = $med->id_str;
        else $media .= ',' . $med->id_str;
        $medId = intval($med->id_str);
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $medUrl = match ($med->type) {
            'photo' => $med->media_url_https,
            'video' => $important
                ? end($med->video_info->variants)->url
                : $med->video_info->variants[1]->url,
            'animated_gif' => $med->video_info->variants[0]->url,
            default => error("Unknown media type: $med->type ($med->id_str)"),
        };
        $medUrlPath = explode('.', explode('?', $medUrl, 2)[0]);
        $medExt = end($medUrlPath);

        # insert a reference into the database
        $db->insertMedia($medId, $medExt, $medUrl, $tweetId);

        # download
        try {
            download($medUrl, "$medId.$medExt", $userId);
        } catch (TypeError $e) {
            message($e);
            error("\n$tweetId");
        }

        # remove the link from the main text
        $text = str_replace($med->url, '', $text);
    }

    # links
    foreach ($tweet->legacy->entities->urls as $link)
        $text = str_replace($link->url, $link->expanded_url, $text);

    # insert Tweet
    $db->insertTweet($tweetId,
        $userId, strtotime($tweet->legacy->created_at),
        $text, $tweet->legacy->lang,
        $media, $replied_to, $retweet_of, $is_quote);

    # insert TweetStats
    $db->insertTweetStat($tweetId,
        $tweet->legacy->bookmark_count,
        $tweet->legacy->favorite_count,
        $tweet->legacy->quote_count,
        $tweet->legacy->reply_count,
        $tweet->legacy->retweet_count,
        property_exists($tweet->views, 'count') ? intval($tweet->views->count) : null);

    return true;
}

function download(string $url, string $fileName, int $user): bool {
    # ensure existence of itself and its directory
    global $target, $verbose;
    $mediaDir = "media/$target/$user";
    if (!file_exists($mediaDir)) {
        mkdir($mediaDir, recursive: true);
        $res = false;
    } else
        $res = file_exists("$mediaDir/$fileName") && filesize("$mediaDir/$fileName") > 0;

    $retryCount = 0;
    while (!$res) {
        if ($verbose && $retryCount == 0) message("Downloading $url\n");
        $file = fopen("$mediaDir/$fileName", 'w');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
            CURLOPT_TIMEOUT => 60,
        ));
        if (PHP_OS == 'WINNT') {
            curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8580');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        $res = curl_exec($curl) == 1;
        curl_close($curl);
        fclose($file);

        if (!$res) {
            $retryCount++;
            if ($retryCount >= 3) {
                unlink("$mediaDir/$fileName");
                message("Couldn't download $url\n");
                return false;
            } else
                message("Retrying for media... ($url)\n");
        }
    }
    return true;
}

function message(string $data): void {
    echo "event: message\ndata: $data\n\n";
    //if (ob_get_contents()) ob_end_flush();
    flush();
}

function error(string $data): void {
    echo "event: error\ndata: $data\n\n";
    //if (ob_get_contents()) ob_end_flush();
    flush();
    die();
}

# update the config file
if (!$useCache) {
    require 'config.php';
    $config = readTargets();
    if (!array_key_exists($target, $config))
        $config[$target] = array('name' => '', 'last' => $lastSync);
    else
        if ($lastSync != 0) $config[$target]['last'] = $lastSync;
    writeTargets($config);
}

if (!$verbose) message('DONE');
