# Twitter Profile Exporter

A web-based application which crawls profiles on Twitter for all of their tweets, all tweets related to them,
including their attachments, statistics and data of their authors.
Main data is stored in an SQLite database and all media are downloaded.
Then it'll be able to reconstruct a Twitter profile in front-end.

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

3. Open the index page in a browser like it's a typical website. You'll see an empty table.
4. You'll have to find the unique Twitter ID number of the profile you want to export its contents.
   It'd be a long number like `2286930721`
5. Enter the Twitter ID plus a visible name (no matter what) in the table and then click on **Add**...
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

- [**printer.php**](printer.php) : creates a TXT file out of main tweets of a profile,
  usually in order to be analysed by an AI like ChatGPT.

- [**cleaner.php**](cleaner.php) : removes old profile pictures and banners.

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
