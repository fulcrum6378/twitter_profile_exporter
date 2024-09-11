<?php
require 'API.php';
require 'Database.php';

# $_GET
$target = '1754604672583913472';
$maxTweets = 10;
$useCache = true;

# modules
$db = new Database($target);
$api = new API();

# constants
$twimgImages = 'https://pbs.twimg.com/profile_images/';
$twimgBanners = 'https://pbs.twimg.com/profile_banners/';
$twimgMedia = 'https://pbs.twimg.com/media/';
$maxRetry = 3;

# create necessary directories
$cacheDir = "cache/$target";
if (!file_exists($cacheDir)) mkdir($cacheDir, recursive: true);
$mediaDir = "media/$target";
if (!file_exists($mediaDir)) mkdir($mediaDir, recursive: true);

# loop on consecutive requests
$ended = false;
$cursor = null;
$iFetch = 1;
$parsedTweetsCount = 0;
$parsedUsers = array();
while (!$ended) {
    $cacheFile = "$cacheDir/$iFetch.json";
    $doUseCache = $useCache && file_exists($cacheFile);
    $j = fopen($cacheFile, $doUseCache ? 'r' : 'w');
    if (!$doUseCache) {
        $res = $api->userTweets(ProfileSection::Tweets, $target, $cursor);
        if ($res == "") {
            fclose($j);
            unlink($cacheFile);
            die("Couldn't fetch tweets!");
        }
        fwrite($j, $res);
    } else
        $res = fread($j, filesize($cacheFile));
    fclose($j);

    foreach (json_decode($res)->data->user->result->timeline_v2->timeline->instructions as $instruction) {
        switch ($instruction->type) {
            case 'TimelinePinEntry':
                parseEntry($instruction->entry);
                break;
            case 'TimelineAddEntries':
                if (count($instruction->entries) <= 2)
                    $ended = true;
                foreach ($instruction->entries as $entry)
                    parseEntry($entry);
                break;
        }
        if ($parsedTweetsCount >= $maxTweets) {
            $ended = true;
            break;
        }
    }
    $iFetch++;
}

function parseEntry(stdClass $entry): void {
    global $cursor, $parsedTweetsCount;
    if (str_starts_with($entry->entryId, 'who-to-follow') ||
        str_starts_with($entry->entryId, 'cursor-top')) return;
    if (str_starts_with($entry->entryId, 'cursor-bottom')) {
        $cursor = $entry->content->value;
        return;
    }

    if (property_exists($entry->content, 'itemContent'))
        parseTweet($entry->content->itemContent->tweet_results->result);
    else foreach ($entry->content->items as $item)
        parseTweet($item->item->itemContent->tweet_results->result);
    $parsedTweetsCount++;
}

function parseTweet(stdClass $tweet): void {
    global $db, $maxRetry, $mediaDir, $parsedUsers, $twimgImages, $twimgBanners, $twimgMedia;
    $tweetId = intval($tweet->rest_id);

    # User
    $userId = intval($tweet->core->user_results->result->rest_id);
    if (!in_array($userId, $parsedUsers)) {
        $ul = $tweet->core->user_results->result->legacy;
        $photo = str_replace('_normal', '', substr($ul->profile_image_url_https, strlen($twimgImages)));
        $banner = substr($ul->profile_banner_url, strlen($twimgBanners));
        $link = property_exists($ul, 'url') ? $ul->entities->url->urls[0]->expanded_url : null;
        $pinnedTweet = (count($ul->pinned_tweet_ids_str) > 0) ? $ul->pinned_tweet_ids_str[0] : null;
        if (!$db->checkIfRowExists($db->User, $userId))
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
        return;
    }


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
        parseTweet($tweet->legacy->retweeted_status_result->result);
    }
    if (property_exists($tweet, 'quoted_status_result')) {
        $retweet_of = intval($tweet->quoted_status_result->result->rest_id);
        $is_quote = true;
        parseTweet($tweet->quoted_status_result->result);
    }

    # insert Media(s) and download file(s)
    $media = null;
    $iMed = 0;
    if (property_exists($tweet->legacy->entities, 'media')) foreach ($tweet->legacy->entities->media as $med) {
        if ($media == null) $media = $med->id_str;
        else $media .= $med->id_str;
        $medId = intval($med->id_str);
        $fileName = substr($med->media_url_https, strlen($twimgMedia));

        # insert a reference into the database
        $db->insertMedia($medId, $fileName, $tweetId, $iMed);

        # download
        $dlRes = file_exists("$mediaDir/$fileName");
        $retryCount = 0;
        while (!$dlRes) {
            $dlRes = download($med->media_url_https, "$mediaDir/$fileName");
            if (!$dlRes) {
                $retryCount++;
                if ($retryCount >= $maxRetry) {
                    echo "Couldn't download $med->media_url_https\n";
                    break;
                } else
                    echo "Retrying for media...\n";
            }
        }
        $iMed++;

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
}

function download(string $url, string $store): bool {
    $file = fopen($store, 'w');
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_FILE => $file,
        CURLOPT_PROXY => '127.0.0.1:8580',
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_TIMEOUT => 60,
    ));
    $res = curl_exec($curl);
    curl_close($curl);
    fclose($file);
    return $res == 1;
}
