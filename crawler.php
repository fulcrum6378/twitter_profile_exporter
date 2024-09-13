<?php
require 'API.php';
require 'Database.php';

# $_POST
$target = '1754604672583913472';
$section = ProfileSection::Replies;
$useCache = false;
$updateOnly = false;
$maxEntries = 0; // entries not tweets; set to 0 in order to turn it off.
$wait = 10;

# modules
$db = new Database($target, true);
$api = new API();

# constants
$twimgImages = 'https://pbs.twimg.com/profile_images/';
$twimgBanners = 'https://pbs.twimg.com/profile_banners/';

# loop on consecutive requests
$cacheDir = "cache/$target";
if (!file_exists($cacheDir)) mkdir($cacheDir, recursive: true);
$ended = false;
$cursor = null;
$iFetch = 1;
$parsedTweetsCount = 0;
$parsedUsers = array();
$iTarget = intval($target);
while (!$ended) {
    $cacheFile = "$cacheDir/$iFetch.json";
    $cacheExists = file_exists($cacheFile);
    $doFetch = !$useCache || !$cacheExists;

    # fetch tweets from the Twitter/X API
    if ($doFetch) {
        $res = $api->userTweets($section, $target, $cursor);
        if ($res == "") die("Couldn't fetch tweets!");
        else echo "Fetched page $iFetch\n";
    }

    if ($useCache) {
        $j = fopen($cacheFile, $cacheExists ? 'r' : 'w');
        if (!$cacheExists)
            /** @noinspection PhpUndefinedVariableInspection (true negative) */
            fwrite($j, $res);
        else {
            $res = fread($j, filesize($cacheFile));
            echo "Using cached page $iFetch\n";
        }
        fclose($j);
    }

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
                    if (!$ret && $updateOnly) return;
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
        echo "Waiting in order not to be detected as a bot ($wait seconds)...\n";
        sleep($wait);
    }
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
    global $db, $parsedUsers, $twimgImages, $twimgBanners, $iTarget;
    $tweetId = intval($tweet->rest_id);

    # User
    $userId = intval($tweet->core->user_results->result->rest_id);
    if (!in_array($userId, $parsedUsers)) {
        $ul = $tweet->core->user_results->result->legacy;
        $userExistsInDb = $db->checkIfRowExists($db->User, $userId);
        if (!$userExistsInDb) echo "Processing user @$ul->screen_name (id:$userId)\n";
        $link = property_exists($ul, 'url') ? $ul->entities->url->urls[0]->expanded_url : null;
        $pinnedTweet = (count($ul->pinned_tweet_ids_str) > 0) ? $ul->pinned_tweet_ids_str[0] : null;

        # process user images
        if (property_exists($ul, 'profile_image_url_https')) {
            $photoUrl = str_replace('_normal', '', $ul->profile_image_url_https);
            $photo = substr($photoUrl, strlen($twimgImages));
            download($photoUrl, str_replace('/', '_', $photo), $userId);
        } else
            $photo = null;
        if (property_exists($ul, 'profile_banner_url')) {
            $banner = substr($ul->profile_banner_url, strlen($twimgBanners));
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


    echo "Processing tweet $tweetId\n";
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
        $medUrl = match ($med->type) {
            'photo' => $med->media_url_https,
            'video' => $important
                ? end($med->video_info->variants)->url
                : $med->video_info->variants[1]->url,
            'animated_gif' => $med->video_info->variants[0]->url,
            default => die("Unknown media type: $med->type ($med->id_str)"),
        };
        $medUrlPath = explode('.', explode('?', $medUrl, 2)[0]);
        $medExt = end($medUrlPath);

        # insert a reference into the database
        $db->insertMedia($medId, $medExt, $medUrl, $tweetId);

        # download
        try {
            download($medUrl, "$medId.$medExt", $userId);
        } catch (TypeError $e) {
            echo $e;
            die("\n$tweetId");
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
    $mediaDir = "media/$user";
    if (!file_exists($mediaDir)) {
        mkdir($mediaDir, recursive: true);
        $res = false;
    } else
        $res = file_exists("$mediaDir/$fileName") && filesize("$mediaDir/$fileName") > 0;

    $retryCount = 0;
    while (!$res) {
        if ($retryCount == 0) echo "Downloading $url\n";
        $file = fopen("$mediaDir/$fileName", 'w');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
            CURLOPT_PROXY => '127.0.0.1:8580',
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 60,
        ));
        $res = curl_exec($curl) == 1;
        curl_close($curl);
        fclose($file);

        if (!$res) {
            $retryCount++;
            if ($retryCount >= 3) {
                echo "Couldn't download $url\n";
                return false;
            } else
                echo "Retrying for media... ($url)\n";
        }
    }
    return true;
}
