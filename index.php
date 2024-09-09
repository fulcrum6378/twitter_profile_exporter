<?php
require 'API.php';
require 'Database.php';

$target = '1754604672583913472';
$db = new Database($target);
$api = new API();

# loop on consecutive requests
if (!file_exists('cache/lists')) mkdir('cache/lists');
$ended = false;
$cursor = null;
$iFetch = 1;
while (!$ended) {
    $res = $api->userTweets(ProfileSection::Tweets, $target, $cursor);
    if ($res == "") die("Couldn't fetch tweets!");
    $j = fopen('cache/lists/' . $iFetch . '.json', 'w');
    fwrite($j, $res);
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
    }
    $iFetch++;
}

function parseEntry(stdClass $entry): void {
    global $cursor;
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
}

function parseTweet(stdClass $tweet): void {
    global $db;
    if ($db->checkIfRowExists($db->Tweet, intval($tweet->rest_id)))
        return;

    // user
    $userId = intval($tweet->core->user_results->result->rest_id);
    if (!$db->checkIfUserExists($userId)) {
        // TODO insert User
    }

    // time (e.g. "Wed Sep 04 21:52:19 +0000 2024")
    $time = 0;
    // TODO parse date & time

    // media
    $media = null;
    // TODO process media
    // TODO insert Media
    // TODO download media

    // reply
    $replied_to = null;
    if (property_exists($tweet->lagecy, 'in_reply_to_status_id_str'))
        $replied_to = intval($tweet->lagecy->in_reply_to_status_id_str);
    // TODO insert the replied tweet too

    // retweet & quote
    $retweet_of = null; // TODO
    $is_quote = false; // TODO
    // TODO insert the retweeted tweet too

    $db->insertTweet($userId, $time,
        $tweet->lagecy->retweeted ? null : $tweet->legacy->full_text, $tweet->legacy->lang,
        $media, $replied_to, $retweet_of, $is_quote
    );

    // TODO insert TweetCount
}
