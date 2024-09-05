<?php

require 'API.php';
require 'Database.php';

$target = "1754604672583913472";

$db = new Database($target);
die();

$api = new API();

$res = $api->userTweets(ProfileSection::Tweets, $target);
if ($res == "") die("Couldn't fetch tweets!");
/*$j = fopen("test/5.json", "w");
fwrite($j, $res);
fclose($j);*/

foreach (json_decode($res)->data->user->result->timeline_v2->timeline->instructions as $instruction) {
    switch ($instruction->type) {
        case "TimelinePinEntry":
            parseEntry($instruction->entry);
            break;
        case "TimelineAddEntries":
            foreach ($instruction->entries as $entry)
                parseEntry($entry);
            break;
    }
}

function parseEntry(stdClass $entry): void {
    if (str_starts_with($entry->entryId, "who-to-follow") ||
        str_starts_with($entry->entryId, "cursor")) return;

    $data = fixTweet($entry->content->itemContent->tweet_results->result);
    $j = fopen("results/" . $data->id_str . ".json", "w");
    fwrite($j, json_encode($data, JSON_PRETTY_PRINT));
    fclose($j);
    echo $data->id_str . ' => ' . $data->full_text . '
';
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
        echo "KIR". $data->id_str.'   ';
    }
    if (property_exists($data, "retweeted_status_result"))
        $data->retweeted_status_result = fixTweet($data->retweeted_status_result->result);

    return $data;
}
