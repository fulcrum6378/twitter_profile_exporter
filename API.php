<?php

class API {
    private array $headers = array();
    private string $featuresAndFieldToggles;
    private string $baseUrl = "https://x.com/i/api/graphql/";

    function __construct() {
        # parse the intercepted headers
        foreach (json_decode(file_get_contents("headers_twitter.json")) as $key => $value)
            $this->headers[] = $key . ': ' . $value;

        /** @noinspection SpellCheckingInspection */
        $this->featuresAndFieldToggles = "&features=" . urlencode('{' .
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
                '}');
    }

    function get(string $url): string {
        $curl = curl_init();
        /** @noinspection SpellCheckingInspection */
        curl_setopt_array($curl, array(
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_PROXY => '127.0.0.1:8580',
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_VERBOSE => 1,
        ));

        $res = curl_exec($curl); // $err = curl_error($curl);
        curl_close($curl);
        return $res;
    }

    function userTweets(ProfileSection $sect, string $userId, int $count = 20, string $cursor = null): string {
        /** @noinspection SpellCheckingInspection */
        return $this->get($this->baseUrl .
            match ($sect) {
                ProfileSection::Tweets => "/E3opETHurmVJflFsUBVuUQ/UserTweets",
                ProfileSection::Replies => "bt4TKuFz4T7Ckk-VvQVSow/UserTweetsAndReplies",
                ProfileSection::Media => "dexO_2tohK86JDudXXG3Yw/UserMedia",
            } . "?variables=" . urlencode('{' .
                '"userId":"' . $userId . '",' .
                '"count":' . $count . ',' .
                ($cursor ? ('"cursor":"' . $cursor . '",') : '') .
                '"includePromotedContent":false,' .
                (($sect == ProfileSection::Tweets) ? '"withQuickPromoteEligibilityTweetFields":true,' : '') .
                (($sect == ProfileSection::Replies) ? '"withCommunity":true,' : '') .
                (($sect == ProfileSection::Media) ? '"withClientEventToken":false,' : '') .
                (($sect == ProfileSection::Media) ? '"withBirdwatchNotes":false,' : '') .
                '"withVoice":true,' .
                '"withV2Timeline":true' .
                '}') . $this->featuresAndFieldToggles
        );
    }
}

enum ProfileSection {
    case Tweets;
    case Replies;
    case Media;
    //case Likes;
}