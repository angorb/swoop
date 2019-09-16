<?php
error_reporting(E_ERROR);
const SWOOP_VERSION = "pre-1";
require "vendor/autoload.php";

$term = new League\CLImate\CLImate;

$term->arguments->add([
    'version' => [
        'prefix'      => 'v',
        'longPrefix'  => 'version',
        'description' => 'Display the current version number and app info.',
        'noValue'     => true,
    ],
]);

$url = null;

if($term->arguments->defined('version')){
    $term->out("<bold>Swoop</bold> v.".SWOOP_VERSION);
    exit(0);
}

while (is_null($url)) {
    if (isset($argv[1])) {
        $user_url = $argv[1];
        unset($argv[1]);
    } else {
        $input = $term->input('<bold>Enter URL:</bold>');
        $user_url = strtolower($input->prompt());
    }

    // Remove all illegal characters from a url
    $url = filter_var($user_url, FILTER_SANITIZE_URL);

    // Validate url
    if ($url == "localhost") {
        $term->out("Requesting HTTP headers from <bold>$url</bold> (127.0.0.1)"); // lol this is so hacky
    } elseif (is_url($url)) {
        if (dns_check_record($url)) {
            $dns = dns_get_record($url, DNS_A);
            $term->out("Requesting HTTP headers from <bold>$url</bold> ({$dns[0]['ip']})");
        } else {
            $term->out("<background_red><bold><black>Error:</black></bold></background_red> could not resolve $url");
            $url = null;
        }
    } else {
        $term->out("<background_red><bold><black>Error:</black></bold></background_red> $url is not a valid URL");
        $url = null;
    }
}

if (!strpos($url, "http://")) {
    $url = "http://" . $url;
}

$response = get_headers($url);
$headers = [];
foreach ($response as $line) {
    $temp = explode(":", $line);
    if (count($temp) < 2) {
        $headers[] = array(
            "",
            "<background_blue><bold><black>>> {$line}\t\t</black></bold></background_blue>",
        );
    } else {
        $headers[] = array(
            "<background_light_gray><bold><black>{$temp[0]}:</black></bold></background_light_gray>",
            implode(":", array_slice($temp, 1)),
        );
    }
}

$term->columns(($headers));

function is_url($uri)
{
    if (preg_match('/^[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}' . '((:[0-9]{1,5})?\\/.*)?$/i', $uri)) {
        return $uri;
    } else {
        return false;
    }
}
