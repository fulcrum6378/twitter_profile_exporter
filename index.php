<?php

# Read the headers ../../OneDrive/Hacks/toolbox/
$headers = array();
foreach (json_decode(file_get_contents("headers_twitter.json")) as $key => $value)
    $headers[] = $key . ': ' . $value;

# Initiate the request
$curl = curl_init();
/** @noinspection SpellCheckingInspection */
curl_setopt_array($curl, array(
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_URL => "https://x.com/i/api/graphql/E3opETHurmVJflFsUBVuUQ/UserTweets" .
        "?variables=" . urlencode('{' .
            '"userId":"1754604672583913472",' .
            '"count":20,' .
            '"includePromotedContent":true,' .
            '"withQuickPromoteEligibilityTweetFields":true,' .
            '"withVoice":true,' .
            '"withV2Timeline":true' .
            '}') .
        "&features=" . urlencode('{' .
            '"rweb_tipjar_consumption_enabled":true,' .
            '"responsive_web_graphql_exclude_directive_enabled":true,' .
            '"verified_phone_label_enabled":false,' .
            '"creator_subscriptions_tweet_preview_api_enabled":true,' .
            '"responsive_web_graphql_timeline_navigation_enabled":true,' .
            '"responsive_web_graphql_skip_user_profile_image_extensions_enabled":false,' .
            '"communities_web_enable_tweet_community_results_fetch":true,' .
            '"c9s_tweet_anatomy_moderator_badge_enabled":true,' .
            '"articles_preview_enabled":true,' .
            '"responsive_web_edit_tweet_api_enabled":true,' .
            '"graphql_is_translatable_rweb_tweet_is_translatable_enabled":true,' .
            '"view_counts_everywhere_api_enabled":true,' .
            '"longform_notetweets_consumption_enabled":true,' .
            '"responsive_web_twitter_article_tweet_consumption_enabled":true,' .
            '"tweet_awards_web_tipping_enabled":false,' .
            '"creator_subscriptions_quote_tweet_preview_enabled":false,' .
            '"freedom_of_speech_not_reach_fetch_enabled":true,' .
            '"standardized_nudges_misinfo":true,' .
            '"tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled":true,' .
            '"rweb_video_timestamps_enabled":true,' .
            '"longform_notetweets_rich_text_read_enabled":true,' .
            '"longform_notetweets_inline_media_enabled":true,' .
            '"responsive_web_enhance_cards_enabled":false' .
            '}') .
        "&fieldToggles=" . urlencode('{"' .
            'withArticlePlainText":false' .
            '}'),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_PROXY => '127.0.0.1:8580',
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_RETURNTRANSFER => true,
    //CURLOPT_VERBOSE => 1,
));

$res = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if (!$res) die("Couldn't connect to Twitter!");

echo $res;
$j = fopen("test.json", "w");
fwrite($j, $res);
fclose($j);
