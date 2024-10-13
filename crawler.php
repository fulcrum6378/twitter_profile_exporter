<?php

# process the request
$target = $argv[1] ?? $_GET['t'];
$search = isset($_GET['search']) ? urldecode($_GET['search']) : null;
$sect = isset($_GET['sect']) ? intval($_GET['sect']) : 2;
$updateOnly = ($argv[2] ?? $_GET['update_only'] ?? '0') == '1';
$useCache = ($_GET['use_cache'] ?? '0') == '1';
/** entries not tweets; set to 0 in order to turn it off. */
$maxEntries = isset($_GET['max_entries']) ? intval($_GET['max_entries']) : 0;
$delay = isset($_GET['delay']) ? intval($_GET['delay']) : 10;
$sse = ($_GET['sse'] ?? '0') == '1';

# global settings
if (isset($argv)) chdir(dirname($_SERVER['PHP_SELF']));
set_time_limit(0);

# submodules
require 'Database.php';
$db = new Database($target, true);
require 'API.php';
$api = new API();

# constants
const TWIMG_IMAGES = 'https://pbs.twimg.com/profile_images/';
const TWIMG_BANNERS = 'https://pbs.twimg.com/profile_banners/';

# HTTP headers
header('X-Accel-Buffering: no');
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

# loop on consecutive requests to Twitter API
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
    $res = ''; // in order to silent IDE warnings.

    # fetch tweets from the Twitter/X API
    if ($doFetch) {
        if ($search == null)
            $res = $api->userTweets($target, $sect, $cursor);
        else
            $res = $api->searchTweets($search, $sect, $cursor);
        if ($res == '') error("Couldn't fetch tweets!");
        else say("Fetched page $iFetch");
    }

    if ($useCache) {
        $j = fopen($cacheFile, $cacheExists ? 'r' : 'w');
        if (!$cacheExists)
            fwrite($j, $res);
        else {
            $res = fread($j, filesize($cacheFile));
            say("Using cached page $iFetch");
        }
        fclose($j);
    } else
        if ($lastSync == 0) $lastSync = time();

    if (connection_aborted()) die();

    $res = json_decode($res);
    if (!property_exists($res, 'data'))
        error('Invalid response from Twitter!');
    if ($search == null) {
        if (!property_exists($res->data->user->result->timeline_v2, 'timeline'))
            error('Nothing found!');
        $instructions = $res->data->user->result->timeline_v2->timeline->instructions;
    } else
        $instructions = $res->data->search_by_raw_query->search_timeline->timeline->instructions;

    $containedNewData = false;
    foreach ($instructions as $instruction) {
        switch ($instruction->type) {
            case 'TimelinePinEntry':  // only in userTweets()
                parseEntry($instruction->entry);
                break;
            case 'TimelineAddEntries':
                $containedNewData = true;
                if (count($instruction->entries) <= 2) {
                    if ($useCache) unlink($cacheFile);
                    $ended = true;
                }
                foreach ($instruction->entries as $entry) {
                    $ret = parseEntry($entry);
                    if (!$ret && $updateOnly) break 4;
                    if (connection_aborted()) die();
                }
                break;
        }
        if ($maxEntries > 0 && $parsedTweetsCount >= $maxEntries) {
            $ended = true;
            break;
        }
    }
    if (!$containedNewData && !($search == null && $sect == 4)) $ended = true;
    // this will cancel crawling for tweets with many deleted in between;
    // like if you delete a large number of your liked tweets.

    $iFetch++;
    if (connection_aborted()) die();
    if ($doFetch && !$ended) {
        say("Waiting for $delay seconds...");
        sleep($delay);
    }
    if (connection_aborted()) die();
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
    global $parsedUsers, $db, $iTarget;
    $tweetId = intval($tweet->rest_id);

    # User
    $userId = intval($tweet->core->user_results->result->rest_id);
    if (!in_array($userId, $parsedUsers)) {
        $ul = $tweet->core->user_results->result->legacy;
        $dbUser = $db->queryUser($userId, 'photo,banner');
        if (!$dbUser) say("Processing user @$ul->screen_name (id:$userId)");

        # process user images
        if (property_exists($ul, 'profile_image_url_https')) {
            $photoUrl = str_replace('_normal', '', $ul->profile_image_url_https);
            $photo = substr($photoUrl, strlen(TWIMG_IMAGES));
            if (download($photoUrl, str_replace('/', '_', $photo), $userId) == 0
                && $dbUser && $dbUser['photo'] != null && $iTarget != $userId) {
                unlink("media/$iTarget/$userId/" .
                    str_replace('/', '_', $dbUser['photo']));
                say('Old profile photo was removed.');
            }
        } else
            $photo = null;
        if (property_exists($ul, 'profile_banner_url')) {
            $banner = substr($ul->profile_banner_url, strlen(TWIMG_BANNERS));
            if (download($ul->profile_banner_url,
                    str_replace('/', '_', $banner) . '.jfif', $userId
                ) == 0 && $dbUser && $dbUser['banner'] != null && $iTarget != $userId) {
                unlink("media/$iTarget/$userId/" .
                    str_replace('/', '_', $dbUser['banner']) . '.jfif');
                say('Old profile banner was removed.');
            }
        } else
            $banner = null;

        # insert/update User
        $link = property_exists($ul, 'url') ? $ul->entities->url->urls[0]->expanded_url : null;
        $pinnedTweet = (count($ul->pinned_tweet_ids_str) > 0) ? $ul->pinned_tweet_ids_str[0] : null;
        if (!$dbUser)
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


    say("Processing tweet $tweetId");
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
            say($e);
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

/** @return int 0(OK), 1(already exists), 2(download failed) */
function download(string $url, string $fileName, int $user): int {
    # ensure existence of itself and its directory
    global $target;
    $mediaDir = "media/$target/$user";
    if (!file_exists($mediaDir))
        mkdir($mediaDir, recursive: true);
    else
        if (file_exists("$mediaDir/$fileName") && filesize("$mediaDir/$fileName") > 0) {
            return 1;
        }

    $res = false;
    $retryCount = 0;
    while (!$res) {
        if ($retryCount == 0) say("Downloading $url");
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
                say("Couldn't download $url");
                return 2;
            } else
                say("Retrying for media... ($url)");
        }
    }
    return 0;
}

function say(string $data): void {
    global $sse;
    if ($sse) echo "event: message\ndata: $data\n\n";
    else echo "$data\n";
    if (ob_get_contents()) ob_end_flush();
    flush();
}

function error(string $data): void {
    global $argv;
    if (isset($argv)) die($data);
    echo "event: error\ndata: $data\n\n";
    if (ob_get_contents()) ob_end_flush();
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

say('DONE');
