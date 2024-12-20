<?php /** @noinspection SpellCheckingInspection */

if (is_file(__DIR__ . '/jquery.min.js') && filesize(__DIR__ . '/jquery.min.js') != 0)
    return;

# Bootstrap
$BOOTSTRAP_VERSION = '5.3.3';
file_put_contents(
    __DIR__ . '/bootstrap.min.css', file_get_contents(
    "https://cdn.jsdelivr.net/npm/bootstrap@$BOOTSTRAP_VERSION/dist/css/bootstrap.min.css"));
file_put_contents(
    __DIR__ . '/bootstrap.min.css.map', file_get_contents(
    "https://cdn.jsdelivr.net/npm/bootstrap@$BOOTSTRAP_VERSION/dist/css/bootstrap.min.css.map"));
file_put_contents(
    __DIR__ . '/bootstrap.bundle.min.js', file_get_contents(
    "https://cdn.jsdelivr.net/npm/bootstrap@$BOOTSTRAP_VERSION/dist/js/bootstrap.bundle.min.js"));
file_put_contents(
    __DIR__ . '/bootstrap.bundle.min.js.map', file_get_contents(
    "https://cdn.jsdelivr.net/npm/bootstrap@$BOOTSTRAP_VERSION/dist/js/bootstrap.bundle.min.js.map"));

# jQuery
$JQUERY_VERSION = '3.7.1';
file_put_contents(
    __DIR__ . '/jquery.min.js', file_get_contents(
    "https://code.jquery.com/jquery-$JQUERY_VERSION.min.js"));
file_put_contents(
    __DIR__ . '/jquery.min.js.map', file_get_contents(
    "https://code.jquery.com/jquery-$JQUERY_VERSION.min.map"));
