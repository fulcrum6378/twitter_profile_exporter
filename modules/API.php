<?php

class API {
    private const string BASE_URL = 'https://x.com/i/api/graphql/';

    private array $headers = array();
    private string $userFeatures;
    private string $userFieldToggles;
    private string $tweetFeatures;
    private string $tweetFieldToggles;

    /** Requires the `curl` extension to be enabled. */
    function __construct() {
        # parse the intercepted headers
        foreach (json_decode(file_get_contents(__DIR__ . '/../headers.json')) as $key => $value)
            $this->headers[] = $key . ': ' . $value;

        # prepare API parameters
        /** @noinspection SpellCheckingInspection */
        $this->userFeatures = '&features=' . urlencode('{' .
                '"hidden_profile_subscriptions_enabled":true,' .
                '"rweb_tipjar_consumption_enabled":true,' .
                '"responsive_web_graphql_exclude_directive_enabled":true,' .
                '"verified_phone_label_enabled":false,' .
                '"subscriptions_verification_info_is_identity_verified_enabled":true,' .
                '"subscriptions_verification_info_verified_since_enabled":true,' .
                '"highlights_tweets_tab_ui_enabled":true,' .
                '"responsive_web_twitter_article_notes_tab_enabled":true,' .
                '"subscriptions_feature_can_gift_premium":true,' .
                '"creator_subscriptions_tweet_preview_api_enabled":true,' .
                '"responsive_web_graphql_skip_user_profile_image_extensions_enabled":false,' .
                '"responsive_web_graphql_timeline_navigation_enabled":true' .
                '}');
        $this->userFieldToggles = '&fieldToggles=' . urlencode('{' .
                '"withAuxiliaryUserLabels":false' .
                '}');
        /** @noinspection SpellCheckingInspection */
        $this->tweetFeatures = '&features=' . urlencode('{' .
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
                '}');
        $this->tweetFieldToggles = '&fieldToggles=' . urlencode('{' .
                '"withArticlePlainText":false' .
                '}');
    }

    function get(string $url): string {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
        ));
        if (gethostname() == 'CHIMAERA') {
            curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8580');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            //curl_setopt($curl, CURLOPT_VERBOSE, 1);
        }
        $res = curl_exec($curl); // $err = curl_error($curl);
        curl_close($curl);
        return $res ?: '';
    }

    function userByScreenName(string $screenName): string {
        return $this->get(API::BASE_URL . 'BQ6xjFU6Mgm-WhEP3OiT9w/UserByScreenName' .
            "?variables=%7B%22screen_name%22%3A%22$screenName%22%7D" .
            $this->userFeatures . $this->userFieldToggles);
    }

    /**
     * @param string $userId Twitter ID
     * @param int $sect 1=>Tweets, 2=>Replies, 3=>Media, 4=>Likes
     * @return string JSON text
     */
    function userTweets(
        string $userId,
        int    $sect = 2,
        string $cursor = null,
        int    $count = 20
    ): string {
        $medOrLikes = $sect == 3 || $sect == 4;
        /** @noinspection SpellCheckingInspection */
        return $this->get(API::BASE_URL .
            match ($sect) {
                1 => 'E3opETHurmVJflFsUBVuUQ/UserTweets',
                2 => 'bt4TKuFz4T7Ckk-VvQVSow/UserTweetsAndReplies',
                3 => 'dexO_2tohK86JDudXXG3Yw/UserMedia',
                4 => 'aeJWz--kknVBOl7wQ7gh7Q/Likes',
            } .
            '?variables=' . urlencode('{' .
                '"userId":"' . $userId . '",' .
                '"count":' . $count . ',' . // maximum: 20
                ($cursor ? ('"cursor":"' . $cursor . '",') : '') .
                '"includePromotedContent":false,' . // true
                (($sect == 1) ? '"withQuickPromoteEligibilityTweetFields":false,' : '') . // true
                (($sect == 2) ? '"withCommunity":true,' : '') .
                ($medOrLikes ? '"withClientEventToken":false,' : '') .
                ($medOrLikes ? '"withBirdwatchNotes":false,' : '') .
                '"withVoice":true,' .
                '"withV2Timeline":true' .
                '}') . $this->tweetFeatures . $this->tweetFieldToggles
        );
    }

    /**
     * @param string $q search query
     * @param int $sect 1=>Top, 2=>Latest, 3=>People, 4=>Media, 5=>Lists
     * @return string JSON text
     */
    function searchTweets(
        string $q,
        int    $sect = 2,
        string $cursor = null,
        int    $count = 20
    ): string {
        /** @noinspection SpellCheckingInspection */
        return $this->get(API::BASE_URL . 'MJpyQGqgklrVl_0X9gNy3A/SearchTimeline' .
            '?variables=' . urlencode('{' .
                '"rawQuery":"' . $q . '",' .
                '"count":' . $count . ',' .
                ($cursor ? ('"cursor":"' . $cursor . '",') : '') .
                '"querySource":"typed_query",' .
                '"product":"' . match ($sect) {
                    1 => 'Top',
                    2 => 'Latest',
                    3 => 'People',
                    4 => 'Media',
                    5 => 'Lists',
                } . '"' .
                '}') . $this->tweetFeatures
        );
    }
}
