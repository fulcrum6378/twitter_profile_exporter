<?php /** @noinspection DuplicatedCode */

/** Requires the `sqlite3` extension to be enabled. */
class Database {
    public string $User = 'User';
    public string $Tweet = 'Tweet';
    public string $TweetStat = 'TweetStat';
    public string $Media = 'Media';

    private SQLite3 $db;

    const int PAGE_LENGTH = 20;

    function __construct(string $userId, bool $createIfNotExists = false) {
        $dbDir = 'databases';
        if (!file_exists($dbDir)) mkdir($dbDir);
        $preExisting = file_exists("$dbDir/$userId.db");
        if (!$preExisting && !$createIfNotExists) return;

        $this->db = new SQLite3("$dbDir/$userId.db");
        if (!$preExisting) $this->createTables();
    }

    function createTables(): void {
        $this->db->exec(<<<EOF
    CREATE TABLE $this->User
    (
        id INT PRIMARY KEY     NOT NULL,
        user           TEXT    NOT NULL,
        name           TEXT    NOT NULL,
        description    TEXT,
        created_at     INT     NOT NULL,
        location       TEXT,
        photo          TEXT,
        banner         TEXT,
        link           TEXT,
        following      INT     NOT NULL,
        followers      INT     NOT NULL,
        tweet_count    INT     NOT NULL,
        media_count    INT     NOT NULL,
        pinned_t       INT,
        FOREIGN KEY (pinned_t) REFERENCES Tweet(id)
    );
    CREATE TABLE $this->Tweet
    (
        id INT PRIMARY KEY     NOT NULL,
        user           INT     NOT NULL,
        time           INT     NOT NULL,
        text           TEXT,
        lang           TEXT,
        media          TEXT,
        reply          INT,
        retweet        INT,
        is_quote       INT     DEFAULT(0),
        FOREIGN KEY (user)     REFERENCES User(id),
        FOREIGN KEY (reply)    REFERENCES Tweet(id),
        FOREIGN KEY (retweet)  REFERENCES Tweet(id)
    );
    CREATE TABLE $this->TweetStat
    (
        id INT PRIMARY KEY     NOT NULL,
        bookmark       INT,
        favorite       INT,
        quote          INT,
        reply          INT,
        retweet        INT,
        view           INT,
        FOREIGN KEY (id)       REFERENCES Tweet(id)
    );
    CREATE TABLE $this->Media
    (
        id INT PRIMARY KEY     NOT NULL,
        ext            TEXT    NOT NULL,
        url            TEXT    NOT NULL,
        tweet          INT     NOT NULL,
        FOREIGN KEY (tweet)    REFERENCES Tweet(id)
    );
EOF
        );
    }

    function checkIfRowExists(string $table, int $id): bool {
        return $this->db->query("SELECT EXISTS(SELECT 1 FROM $table WHERE id = $id);")->fetchArray()[0] == 1;
    }

    function insertUser(
        int     $id,
        string  $user,
        string  $name,
        ?string $description,
        int     $created_at,
        ?string $location,
        ?string $photo,
        ?string $banner,
        ?string $link,
        int     $following,
        int     $followers,
        int     $tweet_count,
        int     $media_count,
        ?int    $pinned_t
    ): void {
        $q = $this->db->prepare('INSERT INTO User ' .
            '(id, user, name, description, created_at, location, photo, banner, link, following, followers, ' .
            'tweet_count, media_count, pinned_t) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $q->bindValue(1, $id);
        $q->bindValue(2, $user);
        $q->bindValue(3, $name);
        $q->bindValue(4, $description);
        $q->bindValue(5, $created_at);
        $q->bindValue(6, $location);
        $q->bindValue(7, $photo);
        $q->bindValue(8, $banner);
        $q->bindValue(9, $link);
        $q->bindValue(10, $following);
        $q->bindValue(11, $followers);
        $q->bindValue(12, $tweet_count);
        $q->bindValue(13, $media_count);
        $q->bindValue(14, $pinned_t);
        $q->execute();
    }

    function updateUser(
        int     $id,
        string  $user,
        string  $name,
        ?string $description,
        ?string $location,
        ?string $photo,
        ?string $banner,
        ?string $link,
        int     $following,
        int     $followers,
        int     $tweet_count,
        int     $media_count,
        ?int    $pinned_t
    ): void {
        $q = $this->db->prepare('UPDATE User SET ' .
            'user=?, name=?, description=?, location=?, photo=?, banner=?, link=?, following=?, followers=?, ' .
            'tweet_count=?, media_count=?, pinned_t=? WHERE id = ?');
        $q->bindValue(1, $user);
        $q->bindValue(2, $name);
        $q->bindValue(3, $description);
        $q->bindValue(4, $location);
        $q->bindValue(5, $photo);
        $q->bindValue(6, $banner);
        $q->bindValue(7, $link);
        $q->bindValue(8, $following);
        $q->bindValue(9, $followers);
        $q->bindValue(10, $tweet_count);
        $q->bindValue(11, $media_count);
        $q->bindValue(12, $pinned_t);
        $q->bindValue(13, $id);
        $q->execute();
    }

    function queryUsers(): false|SQLite3Result {
        return $this->db->query("SELECT * FROM User");
    }

    function queryUser(string $id, string $columns = '*'): array|false {
        return $this->db->query("SELECT $columns FROM User WHERE id = $id LIMIT 1")->fetchArray();
    }

    function insertTweet(
        int     $id,
        int     $user,
        int     $time,
        ?string $text,
        ?string $lang,
        ?string $media = null,
        ?int    $replied_to = null,
        ?int    $retweet_of = null,
        bool    $is_quote = false
    ): void {
        $q = $this->db->prepare('INSERT INTO Tweet ' .
            '(id, user, time, text, lang, media, reply, retweet, is_quote) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $q->bindValue(1, $id);
        $q->bindValue(2, $user);
        $q->bindValue(3, $time);
        $q->bindValue(4, $text);
        $q->bindValue(5, $lang);
        $q->bindValue(6, $media);
        $q->bindValue(7, $replied_to);
        $q->bindValue(8, $retweet_of);
        $q->bindValue(9, $is_quote ? 1 : 0);
        $q->execute();
    }

    function tweetSectionClause(int $section): string {
        return match ($section) {
            2 => "",
            3 => "AND media IS NOT NULL",
            default => "AND reply IS NULL ",
        };
    }

    function countTweets(
        string $user,
        int    $section = 2,
    ): int {
        $clause = $this->tweetSectionClause($section);
        return $this->db->query("SELECT COUNT(1) FROM Tweet WHERE user = $user $clause")->fetchArray()[0];
    }

    function queryTweets(
        string $user,
        int    $section = 1,
        int    $page = 0,
        int    $length = Database::PAGE_LENGTH
    ): false|SQLite3Result {
        $clause = $this->tweetSectionClause($section);
        $offset = $page * $length;
        if ($length > 0)
            $limit = " LIMIT $length OFFSET $offset";
        else
            $limit = '';
        return $this->db->query(
            "SELECT * FROM Tweet WHERE user = $user $clause ORDER BY time DESC$limit"
        );
    }

    function queryTweet(int $id): array|false {
        return $this->db->query("SELECT * FROM Tweet WHERE id = $id LIMIT 1")->fetchArray();
    }

    function insertTweetStat(
        int  $id,
        ?int $bookmark,
        ?int $favorite,
        ?int $quote,
        ?int $reply,
        ?int $retweet,
        ?int $view,
    ): void {
        $q = $this->db->prepare('INSERT INTO TweetStat ' .
            '(id, bookmark, favorite, quote, reply, retweet, view) VALUES(?, ?, ?, ?, ?, ?, ?)');
        $q->bindValue(1, $id);
        $q->bindValue(2, $bookmark);
        $q->bindValue(3, $favorite);
        $q->bindValue(4, $quote);
        $q->bindValue(5, $reply);
        $q->bindValue(6, $retweet);
        $q->bindValue(7, $view);
        $q->execute();
    }

    function updateTweetStat(
        int  $id,
        ?int $bookmark,
        ?int $favorite,
        ?int $quote,
        ?int $reply,
        ?int $retweet,
        ?int $view,
    ): void {
        $q = $this->db->prepare('UPDATE TweetStat SET ' .
            'bookmark=?, favorite=?, quote=?, reply=?, retweet=?, view=? WHERE id = ?');
        $q->bindValue(1, $bookmark);
        $q->bindValue(2, $favorite);
        $q->bindValue(3, $quote);
        $q->bindValue(4, $reply);
        $q->bindValue(5, $retweet);
        $q->bindValue(6, $view);
        $q->bindValue(7, $id);
        $q->execute();
    }

    function queryTweetStat(int $id): array|false {
        return $this->db->query("SELECT * FROM TweetStat WHERE id = $id LIMIT 1")->fetchArray();
    }

    function insertMedia(
        int    $id,
        string $ext,
        string $url,
        int    $tweet,
    ): void {
        $q = $this->db->prepare('INSERT INTO Media (id, ext, url, tweet) VALUES(?, ?, ?, ?)');
        $q->bindValue(1, $id);
        $q->bindValue(2, $ext);
        $q->bindValue(3, $url);
        $q->bindValue(4, $tweet);
        $q->execute();
    }

    function queryMedium(int $id): array|false {
        return $this->db->query("SELECT * FROM Media WHERE id = $id LIMIT 1")->fetchArray();
    }

    function __destruct() {
        $this->db->close();
    }
}
