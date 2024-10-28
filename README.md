# Twitter Profile Exporter

A web-based application which crawls profiles on Twitter for all of their tweets, all tweets related to them,
including their attachments, statistics and data of their authors.
Main data is stored in an SQLite database and all media are downloaded.
Then it'll be able to reconstruct a Twitter profile in front-end.

## Requirements

1. **PHP 8+** with *curl* and *sqlite3* extensions enabled.
2. A Twitter account, logged in via Google Chrome desktop.

## Setup

1. Clone this project in a PHP server.

2. **Sniff Twitter headers**:

    1. Open Twitter in your browser and log in to the account by which you want to crawl Twitter.
    2. Open Chrome, go to **Developer tools -> Network section** and filter all requests by **Fetch/XHR**.
       Then make Twitter do something like a search or open someone's profile.
    3. Find the related request, right click on it, select **Copy -> Copy as fetch (Node.js)**.
    4. Open a file called **headers.json** in an editor and paste the contents there.
    5. Now you have to carefully trim the headers after `"headers":`, select the opening curly bracket until where it
       ends,
       it'll usually be 22 lines (make sure to include the curly brackets themselves).
    6. Now here's your headers file. Put it in the main directory of the project, beside the PHP files.

3. Run `frontend/install.php` to download the required front-end assets.
4. Open the index page in a browser like it's a typical website. You'll see an empty table.
5. Enter the username of the profile you want to export its contents, then click on **Add**...
   It'll immediately start crawling that profile and download a bunch of latest tweets. You can download more later.
6. Now return to the index page and see all contents of your target profile.

## Pages

- [**manager.php**](manager.php) : the first page with a table of target profiles you saw during the setup.
  You'll be able to add multiple profiles and delete them.
  Its data is stored in *targets.json* via the module *config.php*.

- [**viewer.php**](viewer.php) : reads databases and shows their contents,
  also has a UI for using *crawler.php*.

## Workers

- [**crawler.php**](crawler.php) : crawls Twitter using the module *API.php*,
  parses responses, stores data inside databases via the module *Database.php* and downloads related media.

  Expected GET parameters to be run via a web server:
  - `t=` target Twitter ID number (required)
  - `search=` URL-encoded search query
  - `sect=` section number (defaults to 2)
  - `update_only=` whether it should abandon crawling if it finds already parsed tweets.
    Only values `1` and `0` are valid as yes and no (defaults to 0(no))
  - `use_cache=` whether it should store JSON responses in /cache/ and use them again (typically for debugging).
    Only values `1` and `0` (defaults to 0)
  - `max_entries=` maximum entries allowed to be retrieved
    (entries not tweets; entries can be follow suggestions as well). Set to 0 in order to turn it off. (defaults to 0)
  - `delay=` delay (in seconds) between each API request in order not to be detected as a bot. (defaults to 10)
  - `sse=` whether or not it must send [Server-Sent Events](https://en.wikipedia.org/wiki/Server-sent_events).
    Only values `1` and `0` (defaults to 0)

  To run via command line (especially as a cron job):

  `~$ php crawler.php <*TARGET_TWITTER_ID_NUMBER> <*UPDATE_ONLY[0,1]> {SEARCH QUERY}`


- [**printer.php**](printer.php) : creates a TXT file out of main tweets of a profile,
  usually in order to be analysed by an AI like ChatGPT.

  Expected GET parameters to be run via a web server:
  - `t=` target Twitter ID number (required)

  To run via command line:

  `~$ php printer.php <*TARGET_TWITTER_ID_NUMBER>`


- [**cleaner.php**](cleaner.php) : removes old profile pictures and banners.

  Expected GET parameters to be run via a web server:
    - `t=` target Twitter ID number (required)

  To run via command line:

  `~$ php cleaner.php <*TARGET_TWITTER_ID_NUMBER>`

## Modules

- [**config.php**](modules/config.php) : controls *targets.json* containing a list of target profiles.

- [**API.php**](modules/API.php) : connects to the Twitter API and gets JSON responses (but doesn't parse them).

- [**Database.php**](modules/Database.php) : controls SQLite databases containing all data from Twitter profiles.

## License

```
            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
                    Version 2, December 2004

 Copyright (C) 2024 Mahdi Parastesh <fulcrum1378@gmail.com>

 Everyone is permitted to copy and distribute verbatim or modified
 copies of this license document, and changing it is allowed as long
 as the name is changed.

            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

  0. You just DO WHAT THE FUCK YOU WANT TO.
```
