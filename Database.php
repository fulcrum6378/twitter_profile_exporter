<?php /** @noinspection DuplicatedCode */

/** Requires the `sqlite3` extension to be enabled. */
class Database {
    public string $User = 'User';
    public string $Tweet = 'Tweet';
    public string $TweetStat = 'TweetStat';
    public string $Media = 'Media';

    private SQLite3 $db;

    function __construct(string $userId) {
        $dbDir = 'databases';
        if (!file_exists($dbDir)) mkdir($dbDir);
        $preExisting = file_exists("$dbDir/$userId.db");
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
        following      INT     NOT NULL,
        followers      INT     NOT NULL,
        tweets         INT     NOT NULL,
        media          INT     NOT NULL,
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
        type           TEXT    NOT NULL,
        url            TEXT    NOT NULL
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
        int     $following,
        int     $followers,
        int     $tweets,
        int     $media,
        ?int    $pinned_t
    ): void {
        $q = $this->db->prepare('INSERT INTO User ' .
            '(id, user, name, description, created_at, location, photo, banner, following, followers, ' .
            'tweets, media, pinned_t) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $q->bindValue(1, $id);
        $q->bindValue(2, $user);
        $q->bindValue(3, $name);
        $q->bindValue(4, $description);
        $q->bindValue(5, $created_at);
        $q->bindValue(6, $location);
        $q->bindValue(7, $photo);
        $q->bindValue(8, $banner);
        $q->bindValue(9, $following);
        $q->bindValue(10, $followers);
        $q->bindValue(11, $tweets);
        $q->bindValue(12, $media);
        $q->bindValue(13, $pinned_t);
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
        int     $following,
        int     $followers,
        int     $tweets,
        int     $media,
        ?int    $pinned_t
    ): void {
        $q = $this->db->prepare('UPDATE User SET ' .
            'user=?, name=?, description=?, location=?, photo=?, banner=?, following=?, followers=?, ' .
            'tweets=?, media=?, pinned_t=? WHERE id = ?');
        $q->bindValue(1, $user);
        $q->bindValue(2, $name);
        $q->bindValue(3, $description);
        $q->bindValue(4, $location);
        $q->bindValue(5, $photo);
        $q->bindValue(6, $banner);
        $q->bindValue(7, $following);
        $q->bindValue(8, $followers);
        $q->bindValue(9, $tweets);
        $q->bindValue(10, $media);
        $q->bindValue(11, $pinned_t);
        $q->bindValue(12, $id);
        $q->execute();
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

    function insertMedia(): void {}

    function __destruct() {
        $this->db->close();
    }
}
