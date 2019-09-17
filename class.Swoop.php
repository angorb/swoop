<?php
const SWOOP_VERSION = "pre-2";

class Swoop
{
    private $_host = null;
    private $_console;

    public function __construct($argv)
    {
        $this->_console = new League\CLImate\CLImate;

        // set up commant line arguments accepted by the app
        $this->_console->arguments->add(
            ['version' => [
                'prefix' => 'v',
                'longPrefix' => 'version',
                'description' => 'the current version number',
                'noValue' => true,
            ],
            'help' => [
                'prefix' => 'h',
                'longPrefix' => 'help',
                'description' => 'Prints a usage statement',
                'noValue' => true,
            ],
            'host' => [
                'description' => 'hostname',
            ],]
        );

        $this->_console->arguments->parse();

        if ($this->_console->arguments->defined('version')) {
            $this->_printVersion();
            exit(0);
        }

        if ($this->_console->arguments->defined('help')) {
            $this->_printVersion();
            $this->_console->usage();
            exit(0);
        }

        if (isset($argv[1])) {
            $this->_validateHost($argv[1]);
        }

        while (is_null($this->_host)) {
            $this->_promptForHostname();
        }

        $this->_showHTTPHeaders();

    }

    private function _printVersion()
    {
        $this->_console->description('<background_black><bold>S<red>w</red><yellow>o</yellow><blue>o</blue>p</bold></background_black> v.' . SWOOP_VERSION);
    }

    private function _promptForHostname()
    {
        $input = $this->_console->input('<bold>Enter URL:</bold>');
        $this->_validateHost(strtolower($input->prompt()));
    }

    private function _validateHost($host)
    {

        if ($host == "localhost") {
            $this->_console->out("Requesting HTTP headers from <bold>localhost</bold> (127.0.0.1)");
            $this->_host = "localhost";
            return true;
        }

        if ($this->_isHostname($host)) {
            if (dns_check_record($host)) {
                $dns = dns_get_record($host, DNS_A);
                $this->_console->out("Requesting HTTP headers from <bold>{$host}</bold> ({$dns[0]['ip']})");
                $this->_host = $host;
                return true;
            } else {
                $this->_console->out("<background_red><bold><black>Error:</black></bold></background_red> could not resolve {$host}");
                return false;
            }
        } else {
            $this->_console->out("<background_red><bold><black>Error:</black></bold></background_red> {$host} is not a valid URL");
            return false;
        }
    }

    private function _showHTTPHeaders()
    {
        $response = get_headers("http://" . $this->_host);
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
        $this->_console->columns(($headers));
    }

    private static function _isHostname($host)
    {
        if (preg_match('/^[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}' . '((:[0-9]{1,5})?\\/.*)?$/i', $host)) {
            return true;
        } else {
            return false;
        }
    }
}
