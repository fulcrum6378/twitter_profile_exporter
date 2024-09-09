<?php

/** Requires the `sqlite3` extension to be enabled. */
class Database {
    public string $User = 'User';
    public string $Tweet = 'Tweet';
    public string $TweetCount = 'TweetCount';
    public string $Media = 'Media';

    private SQLite3 $db;
    public array $userIds = array();

    function __construct(string $userId) {
        if (!file_exists('databases')) mkdir('databases');
        $preExisting = file_exists('databases/' . $userId . '.db');
        $this->db = new SQLite3('databases/' . $userId . '.db');
        if (!$preExisting)
            $this->createTables();
        else
            while ($res = $this->db->query("SELECT id FROM User")->fetchArray(SQLITE3_ASSOC))
                $this->userIds[] = $res['id'];
    }

    function createTables(): void {
        $this->db->exec(<<<EOF
    CREATE TABLE $this->User
    (
        id INT PRIMARY KEY     NOT NULL,
        user           TEXT    NOT NULL,
        created_at     INT     NOT NULL,
        name           TEXT    NOT NULL,
        description    TEXT,
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
        FOREIGN KEY (retweet)  REFERENCES Tweet(id),
    );
    CREATE TABLE $this->TweetCount
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

    function checkIfUserExists(int $id): bool {
        return in_array($id, $this->userIds);
    }

    function checkIfRowExists(string $table, int $id): bool {
        return $this->db->query("SELECT EXISTS(SELECT 1 FROM $table WHERE id = $id);")->fetchArray()[0] == 1;
    }

    function insertTweet(
        int     $user,
        int     $time,
        ?string $text,
        ?string $lang,
        ?string $media = null,
        ?int    $replied_to = null,
        ?int    $retweet_of = null,
        bool    $is_quote = false
    ): void {
        $q = $this->db->prepare("INSERT INTO Tweet " .
            "(user, time, text, lang, media, reply, retweet, is_quote) VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
        $q->bindValue(1, $user);
        $q->bindValue(2, $time);
        $q->bindValue(3, $text);
        $q->bindValue(4, $lang);
        $q->bindValue(5, $media);
        $q->bindValue(6, $replied_to);
        $q->bindValue(7, $retweet_of);
        $q->bindValue(8, $is_quote ? 1 : 0);
        $q->execute();
    }

    function __destruct() {
        $this->db->close();
    }
}
