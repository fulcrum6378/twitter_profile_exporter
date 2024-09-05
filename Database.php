<?php

/** Requires the `sqlite3` extension to be enabled. */
class Database {
    public string $User = "User";
    public string $Tweet = "Tweet";
    public string $TweetCount = "TweetCount";
    public string $Media = "Media";

    private SQLite3 $db;
    public array $userIds = array();

    function __construct(string $userId) {
        $preExisting = file_exists("databases/" . $userId . ".db");
        $this->db = new SQLite3("databases/" . $userId . ".db");
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
        pinned_tweet   INT,
        FOREIGN KEY (pinned_tweet) REFERENCES Tweet(id)
    );
    CREATE TABLE $this->Tweet
    (
        id INT PRIMARY KEY     NOT NULL,
        user           INT     NOT NULL,
        date           INT     NOT NULL,
        text           TEXT,
        lang           TEXT,
        retweeted      INT,
        quoted         INT,
        replied        INT,
        media1         INT,
        media2         INT,
        media3         INT,
        media4         INT,
        FOREIGN KEY (user)         REFERENCES User(id),
        FOREIGN KEY (retweeted)    REFERENCES Tweet(id),
        FOREIGN KEY (quoted)       REFERENCES Tweet(id),
        FOREIGN KEY (replied)      REFERENCES Tweet(id),
        FOREIGN KEY (media1)       REFERENCES Media(id),
        FOREIGN KEY (media2)       REFERENCES Media(id),
        FOREIGN KEY (media3)       REFERENCES Media(id),
        FOREIGN KEY (media4)       REFERENCES Media(id)
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
        FOREIGN KEY (id)           REFERENCES Tweet(id)
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

    function __destruct() {
        $this->db->close();
    }
}
