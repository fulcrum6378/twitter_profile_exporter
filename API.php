<?php

/** Requires the `curl` extension to be enabled. */
class API {
    private const string BASE_URL = 'https://x.com/i/api/graphql/';

    private array $headers = array();
    private string $apiFeatures;
    private string $apiFieldToggles;

    function __construct() {
        # parse the intercepted headers
        foreach (json_decode(file_get_contents('headers.json')) as $key => $value)
            $this->headers[] = $key . ': ' . $value;

        /** @noinspection SpellCheckingInspection */
        $this->apiFeatures = '&features=' . urlencode('{' .
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
        $this->apiFieldToggles = '&fieldToggles=' . urlencode('{"' .
                'withArticlePlainText":false' .
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
        if (PHP_OS == 'WINNT') {
            curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8580');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            //curl_setopt($curl, CURLOPT_VERBOSE, 1);
        }
        $res = curl_exec($curl); // $err = curl_error($curl);
        curl_close($curl);
        return $res ?: '';
    }

    function userTweets(
        ProfileSect $sect,
        string      $userId,
        string      $cursor = null,
        int         $count = 20
    ): string {
        $medOrLikes = $sect == ProfileSect::Media || $sect == ProfileSect::Likes;
        /** @noinspection SpellCheckingInspection */
        return $this->get(API::BASE_URL .
            match ($sect) {
                ProfileSect::Tweets => 'E3opETHurmVJflFsUBVuUQ/UserTweets',
                ProfileSect::Replies => 'bt4TKuFz4T7Ckk-VvQVSow/UserTweetsAndReplies',
                ProfileSect::Media => 'dexO_2tohK86JDudXXG3Yw/UserMedia',
                ProfileSect::Likes => 'aeJWz--kknVBOl7wQ7gh7Q/Likes',
            } .
            '?variables=' . urlencode('{' .
                '"userId":"' . $userId . '",' .
                '"count":' . $count . ',' . // maximum: 20
                ($cursor ? ('"cursor":"' . $cursor . '",') : '') .
                '"includePromotedContent":false,' . // true
                (($sect == ProfileSect::Tweets) ? '"withQuickPromoteEligibilityTweetFields":false,' : '') . // true
                (($sect == ProfileSect::Replies) ? '"withCommunity":true,' : '') .
                ($medOrLikes ? '"withClientEventToken":false,' : '') .
                ($medOrLikes ? '"withBirdwatchNotes":false,' : '') .
                '"withVoice":true,' .
                '"withV2Timeline":true' .
                '}') . $this->apiFeatures . $this->apiFieldToggles
        );
    }

    function searchTweets(
        string     $q,
        string     $cursor = null,
        SearchSect $sect = SearchSect::Latest,
        int        $count = 20
    ): string {
        /** @noinspection SpellCheckingInspection */
        return $this->get(API::BASE_URL . 'MJpyQGqgklrVl_0X9gNy3A/SearchTimeline' .
            '?variables=' . urlencode('{' .
                '"rawQuery":"' . $q . '",' .
                '"count":' . $count . ',' .
                ($cursor ? ('"cursor":"' . $cursor . '",') : '') .
                '"querySource":"typed_query",' .
                '"product":"' . $sect->name . '"' .
                '}') . $this->apiFeatures
        );
    }
}

enum ProfileSect {
    case Tweets;
    case Replies;
    case Media;
    case Likes;
}

enum SearchSect {
    case Top;
    case Latest;
    case People;
    case Media;
    case Lists;
}
