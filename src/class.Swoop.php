<?php
namespace oxcrime\Swoop;

/**
 * A stupid-simple utility for viewing HTTP header information.
 * @author Nick Brogna <oxcrime@gmail.com>
 * @version pre-1
 * @
 */
class Swoop
{

    const VERSION = '0.0.1';

    /**
     * Version object, for git version information
     *
     * @var \SebastianBergmann\Version $_version
     * @see https://packagist.org/packages/sebastian/version
     */
    private $_version;

    /**
     * Holds the value of and/or waits for the hostname we're trying to look up
     *
     * @var string $_host
     */
    private $_host = null;

    /**
     * CLImate object for interacting with the program via the command line
     *
     * @var \League\CLImate\CLImate $_console
     * @see http://climate.thephpleague.com/
     */
    private $_console;

    /**
     * Entry point for the main program execution.
     * It's essentially procedural and could use some work
     * //TODO fix this
     *
     * @param array $argv
     */
    private function __construct()
    {
        // set the version string with the current git
        $this->_version = new \SebastianBergmann\Version(
            self::VERSION, __DIR__
        );

        // instantiate a new CLImate console to help make creating a
        // PHP CLI app much more reasonable
        $this->_console = new \League\CLImate\CLImate;

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
                ]]
        );

        $this->_console->out(
            "<bold>S<red>w</red><yellow>o</yellow><blue>o</blue>p</bold> v. " . 
            $this->_version->getVersion()
        );
        $this->_console->arguments->parse();

        // do nothing and exit after printing the version string
        if ($this->_console->arguments->defined('version')) {
            exit(0);
        }

        if ($this->_console->arguments->defined('help')) {
            $this->_console->usage();
            exit(0);
        }

        global $argv;
        if (isset($argv[1])) {
            $this->_validateHost($argv[1]);
        }

        while (is_null($this->_host)) {
            $this->_promptForHostname();
        }

        $this->_showHTTPHeaders();

    }

    /**
     * Executes the main application
     *
     * @return self
     * @see Swoop::__construct()
     */
    public static function init() : self
    {
        return new self();
    }

    /**
     * Prompts the user for the hostname if cli arguments are missing or invalid
     *
     * @return void
     */
    private function _promptForHostname(): void
    {
        $input = $this->_console->input(
            '<bold>Enter URL:</bold>'
        );

        $this->_validateHost(
            strtolower(
                $input->prompt()
            )
        );
    }

    /**
     * Checks if $host is a valid url via dns_check_record() and dns_get_record()
     *
     * @param string $host
     * @return boolean
     */
    private function _validateHost(string $host): bool
    {
        // TODO look into why this is necessary
        if ($host == "localhost") {
            $this->_console->out(
                "Requesting HTTP headers from <bold>localhost</bold> (127.0.0.1)"
            );
            $this->_host = "localhost";
            return true;
        }

        if ($this->_isHostname($host)) {
            if (dns_check_record($host)) {
                $dns = dns_get_record($host, DNS_A);
                $this->_console->out(
                    "Requesting HTTP headers from <bold>{$host}</bold> ({$dns[0]['ip']})"
                );
                $this->_host = $host;
                return true;
            } else {
                $this->_console->out(
                    "<background_red><bold><black>Error:</black></bold></background_red> could not resolve {$host}"
                );
                return false;
            }
        } else {
            $this->_console->out(
                "<background_red><bold><black>Error:</black></bold></background_red> {$host} is not a valid URL"
            );
            return false;
        }
    }

    /**
     * Print the header information provided by get_headers() to the prettified console
     *
     * @return void
     */
    private function _showHTTPHeaders(): void
    {
        $response = get_headers("http://" . $this->_host);
        $headers = [];
        foreach ($response as $line) {
            $temp = explode(
                ":", $line
            );
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

    /**
     * Checks the provided hostname against a regular expression for format issues
     *
     * @param string $host
     * @return boolean
     */
    private static function _isHostname(string $host): bool
    {
        return preg_match(
            '/^[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}((:[0-9]{1,5})?\\/.*)?$/i', $host
        ) ?
        true :
        false;
    }
}
