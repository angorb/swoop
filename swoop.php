<?php
require "vendor/autoload.php";

$term = new League\CLImate\CLImate;

$url = null;

while (is_null($url)) {
    if (isset($argv[1])) {
        $user_url = $argv[1];
        unset($argv[1]);
    } else {
        $input = $term->input('<bold>Enter URL:</bold>');
        $user_url = strtolower($input->prompt());
    }

    if (!strpos($user_url, "http://")) {
        $term->out("<bold><red>Error:</red></bold> No URL scheme specified. Assuming 'http'...");
        $user_url = "http://" . $user_url;
    }

    // Remove all illegal characters from a url
    $url = filter_var($user_url, FILTER_SANITIZE_URL);
    unset($user_url);

// Validate url
    if (is_url($url) || $url == "http://localhost") {
        $term->blue("Requesting headers from <bold>$url</bold>");
    } else {
        $term->out("<bold><red>Error:</red></bold> $url is not a valid URL");
        unset($argv[1]);
        $url = null;
    }
    if (!is_null($url)) {
        $ip = gethostbyname($url);
        if ($ip == $url) {
            $term->out("<bold><red>Error:</red></bold> could not resolve $url");
            $url = null;
        }
    }
}

$response = get_headers($url);
$headers = [];
foreach ($response as $line) {
    $temp = explode(":", $line);
    if (count($temp) < 2) {
        $headers[] = array(
            "",
            "<bold><green>$line</green></bold>",
        );
    } else {
        $headers[] = array(
            "<bold><yellow>{$temp[0]}:</yellow></bold>",
            implode(":", array_slice($temp, 1)),
        );
    }
}

$term->columns(($headers));

function is_url($uri)
{
    if (preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}' . '((:[0-9]{1,5})?\\/.*)?$/i', $uri)) {
        return $uri;
    } else {
        return false;
    }
}
