<?php
require 'API.php';
require 'Database.php';

# $_GET
$target = '1754604672583913472';
$maxTweets = 10;

# modules
$db = new Database($target);
$api = new API();

# constants
$profileImages = 'https://pbs.twimg.com/profile_images/';
$profileBanners = 'https://pbs.twimg.com/profile_banners/';

# loop on consecutive requests
$cacheDir = "cache/$target";
if (!file_exists($cacheDir)) mkdir($cacheDir, recursive: true);
$ended = false;
$cursor = null;
$iFetch = 1;
$parsedTweetsCount = 0;
$parsedUsers = array();
while (!$ended) {
    $cacheFile = "$cacheDir/$iFetch.json";
    $useCache = file_exists($cacheFile);
    $j = fopen($cacheFile, $useCache ? 'r' : 'w');
    if (!$useCache) {
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
    global $db, $parsedUsers, $profileImages, $profileBanners;
    $tweetId = intval($tweet->rest_id);

    if ($db->checkIfRowExists($db->Tweet, intval($tweet->rest_id))) {
        // TODO update TweetStat
        return;
    }

    # User
    $userId = intval($tweet->core->user_results->result->rest_id);
    if (!in_array($userId, $parsedUsers)) { // TODO $db->checkIfUserExists($userId) then UPDATE
        $ul = $tweet->core->user_results->result->legacy;
        $pinnedTweets = $ul->pinned_tweet_ids_str;
        $db->insertUser($userId,
            $ul->screen_name, $ul->name, $ul->description,
            strtotime($ul->created_at), $ul->location,
            substr($ul->profile_image_url_https, strlen($profileImages)),
            substr($ul->profile_banner_url, strlen($profileBanners)),
            $ul->friends_count, $ul->followers_count,
            $ul->statuses_count, $ul->media_count,
            (count($pinnedTweets) > 0) ? $pinnedTweets[0] : null);
        $parsedUsers[] = $userId;
    }

    # Media
    $media = null;
    // TODO process media
    // TODO insert Media
    // TODO download media

    # reply
    $replied_to = null;
    if (property_exists($tweet->legacy, 'in_reply_to_status_id_str'))
        $replied_to = intval($tweet->legacy->in_reply_to_status_id_str);

    # retweet & quote
    $retweet_of = null;
    if (property_exists($tweet->legacy, 'quoted_status_id_str'))
        $retweet_of = intval($tweet->legacy->quoted_status_id_str);
    $is_quote = property_exists($tweet, 'quoted_status_result');
    if (property_exists($tweet->legacy, 'retweeted_status_result'))
        parseTweet($tweet->legacy->retweeted_status_result->result);
    if ($is_quote)
        parseTweet($tweet->quoted_status_result->result);

    $db->insertTweet($tweetId,
        $userId, strtotime($tweet->legacy->created_at),
        $tweet->legacy->retweeted ? null : $tweet->legacy->full_text, $tweet->legacy->lang,
        $media, $replied_to, $retweet_of, $is_quote
    );

    # TweetStats
    $db->insertTweetStat($tweetId,
        $tweet->legacy->bookmark_count,
        $tweet->legacy->favorite_count,
        $tweet->legacy->quote_count,
        $tweet->legacy->reply_count,
        $tweet->legacy->retweet_count,
        intval($tweet->views->count)); // FIXME PHP Warning:  Undefined property: stdClass::$count
}

/*function parseDateTime(string $str): int {
    // "Wed Sep 04 21:52:19 +0000 2024"

}*/
