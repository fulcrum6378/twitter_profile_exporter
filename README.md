# Twitter Profile Exporter

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
  Its data is stored in *targets.json* via the submodule *config.php*.

- [**viewer.php**](viewer.php) : reads databases and shows their contents,
  also has a UI for using *crawler.php*.

- [**crawler.php**](crawler.php) (no UI) : crawls Twitter using the submodule *API.php*,
  parses responses, stores data inside databases via the submodule *Database.php* and downloads related media.

- [**printer.php**](printer.php) (no UI) : creates a TXT file out of main tweets of a profile,
  usually in order to be analysed by an AI like ChatGPT.

- [**cleaner.php**](cleaner.php) (no UI) : removes old profile pictures and banners.

## Submodules

- [**config.php**](config.php) : controls *targets.json* containing a list of target profiles.

- [**API.php**](API.php) : connects to the Twitter API and gets JSON responses (but doesn't parse them).

- [**Database.php**](Database.php) : controls SQLite databases containing all data from Twitter profiles.
