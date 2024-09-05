<?php
require 'API.php';
require 'Database.php';

$target = "1754604672583913472";
$db = new Database($target);
$api = new API();

# loop on consecutive requests
$ended = false;
$cursor = null;
$iFetch = 1;
while (!$ended) {
    $res = $api->userTweets(ProfileSection::Tweets, $target, $cursor);
    if ($res == "") die("Couldn't fetch tweets!");
    $j = fopen("results/" . $iFetch . ".json", "w");
    fwrite($j, $res);
    fclose($j);

    foreach (json_decode($res)->data->user->result->timeline_v2->timeline->instructions as $instruction) {
        switch ($instruction->type) {
            case "TimelinePinEntry":
                parseEntry($instruction->entry);
                break;
            case "TimelineAddEntries":
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
    if (str_starts_with($entry->entryId, "who-to-follow") ||
        str_starts_with($entry->entryId, "cursor-top")) return;
    if (str_starts_with($entry->entryId, "cursor-bottom")) {
        $cursor = $entry->content->value;
        return;
    }

    if (property_exists($entry->content, "itemContent"))
        parseTweet($entry->content->itemContent->tweet_results->result);
    else foreach ($entry->content->items as $item)
        parseTweet($item->item->itemContent->tweet_results->result);
}

function parseTweet(stdClass $tweet): void {
    global $db;
    $userId = intval($tweet->core->user_results->result->rest_id);
    $db->checkIfUserExists($userId);
    $db->checkIfRowExists($db->User, 2);
    // TODO fill the database
}

function fixTweet(stdClass $tweet): stdClass {
    $data = $tweet->legacy;

    // new features
    if (property_exists($tweet->views, "count"))
        $data->view_count = intval($tweet->views->count);

    // unnecessary fields
    if (property_exists($data, "bookmarked")) unset($data->bookmarked);
    if (property_exists($data, "display_text_range")) unset($data->display_text_range);
    if (property_exists($data, "extended_entities")) unset($data->extended_entities);
    if (property_exists($data, "favorited")) unset($data->favorited);
    if (property_exists($data, "retweeted")) unset($data->retweeted);

    // media
    if (property_exists($data->entities, "media"))
        foreach ($data->entities->media as $medium) {
            if (property_exists($medium, "features")) unset($medium->features);
            if (property_exists($medium, "sizes")) unset($medium->sizes);
            if (property_exists($medium, "original_info") &&
                property_exists($medium->original_info, "focus_rects"))
                unset($medium->original_info->focus_rects);
            if (property_exists($medium, "allow_download_status")) unset($medium->allow_download_status);
            if (property_exists($medium, "media_results")) unset($medium->media_results);
        }

    // attached tweets
    if (property_exists($data, "quoted_status_result"))
        $data->quoted_status_result = fixTweet($data->quoted_status_result->result);
    if (property_exists($tweet, "quoted_status_result")) {
        if (property_exists($data, "quoted_status_result")) die("WTF!");
        $data->quoted_status_result = fixTweet($tweet->quoted_status_result->result);
    }
    if (property_exists($data, "retweeted_status_result"))
        $data->retweeted_status_result = fixTweet($data->retweeted_status_result->result);

    return $data;
}
