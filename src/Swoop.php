<?php

namespace Angorb\Swoop;

/**
 * A stupid-simple utility for viewing HTTP header information.
 * @author Nick Brogna <oxcrime@gmail.com>
 * @version 0.0.3
 */
class Swoop
{

    private const VERSION = '0.0.3';
    private const HEADER_STATUS_STRING = '<background_%2$s><bold><%3$s>>> %1$s </%3$s></bold></background_%2$s>';

    /**
     * Holds the value of and/or waits for the hostname we're trying to look up
     *
     * @var string $host
     */
    private $host = null;

    /**
     * CLImate object for interacting with the program via the command line
     *
     * @var \League\CLImate\CLImate $console
     * @see http://climate.thephpleague.com/
     */
    private $console;

    /**
     * The HTTP protocol handler, with or without SSL
     *
     * @var string $protocol
     */
    private $protocol = "http://";

    /**
     * Entry point for the main program execution.
     * It's essentially procedural and could use some work
     */
    private function __construct()
    {
        // instantiate a new CLImate console to help make creating a
        // PHP CLI app much more reasonable
        $this->console = new \League\CLImate\CLImate();

        // set up commant line arguments accepted by the app
        $this->console->arguments->add(
            [
                'version' => [
                    'prefix' => 'v',
                    'longPrefix' => 'version',
                    'description' => 'the current version number',
                    'noValue' => \true,
                ],
                'help' => [
                    'prefix' => 'h',
                    'longPrefix' => 'help',
                    'description' => 'Prints a usage statement',
                    'noValue' => \true,
                ],
                'https' => [
                    'prefix' => 's',
                    'longPrefix' => 'https',
                    'description' => 'Use HTTPS instead of HTTP',
                    'noValue' => \true,
                ],
                'host' => [
                    'description' => 'hostname',
                ]
            ]
        );

        $this->console->out(
            "<bold>S<red>w</red><yellow>o</yellow><blue>o</blue>p</bold> v" .
                self::VERSION
        );

        $this->console->arguments->parse();

        // do nothing and exit after printing the version string
        if ($this->console->arguments->defined('version')) {
            exit(0);
        }

        if ($this->console->arguments->defined('help')) {
            $this->console->usage();
            exit(0);
        }

        if ($this->console->arguments->defined('https')) {
            $this->protocol = "https://";
        }

        if ($this->console->arguments->exists('host') && !empty($this->console->arguments->get('host'))) {
            $this->validateHost($this->console->arguments->get('host'));
        }

        while (\is_null($this->host)) {
            $this->promptForHostname();
        }

        $this->showHTTPHeaders();
    }

    /**
     * Executes the main application
     *
     * @return self
     * @see Swoop::__construct()
     */
    public static function init(): self
    {
        return new self();
    }

    /**
     * Prompts the user for the hostname if cli arguments are missing or invalid
     *
     * @return void
     */
    private function promptForHostname(): void
    {
        $input = $this->console->input(
            '<bold>Enter URL:</bold>'
        );

        $this->validateHost(
            \strtolower(
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
    private function validateHost(string $host): bool
    {
        // TODO look into why this is necessary
        if ($host == "localhost") {
            $this->console->out(
                "Requesting headers from <bold>{$this->protocol}localhost</bold> (127.0.0.1)"
            );
            $this->host = "localhost";
            return \true;
        }

        if ($this->isHostname($host)) {
            if (\checkdnsrr($host, "A")) {
                $dns = \dns_get_record($host, DNS_A);
                $this->console->out(
                    "Requesting headers from <bold>{$this->protocol}{$host}</bold> ({$dns[0]['ip']})"
                );
                $this->host = $host;
                return \true;
            } else {
                $this->console->out(
                    "<background_red><bold><black>Error:</black></bold></background_red> could not resolve {$host}"
                );
                return \false;
            }
        } else {
            $this->console->out(
                "<background_red><bold><black>Error:</black></bold></background_red> {$host} is not a valid URL"
            );
            return \false;
        }
    }

    /**
     * Print the header information provided by get_headers() to the prettified console
     *
     * @return void
     */
    private function showHTTPHeaders(): void
    {
        $response = \get_headers($this->protocol . $this->host);
        $headers = [];
        foreach ($response as $line) {
            $parts = \explode(
                ":",
                $line
            );
            if (\count($parts) < 2) {
                $parts = \explode(
                    " ",
                    $line
                );

                // Colorize the CLImate output based on HTTP status code
                // not thoroughly tested, but works well enough for now
                $statusBkgColor = "light_gray";
                $statusTextColor = "black";

                if ($parts[1] >= 200  && $parts[1] < 300) {
                    $statusBkgColor = "green";
                    $statusTextColor = "black";
                }

                if ($parts[1] >= 300  && $parts[1] < 400) {
                    $statusBkgColor = "blue";
                    $statusTextColor = "white";
                }

                if ($parts[1] >= 300  && $parts[1] < 400) {
                    $statusBkgColor = "yellow";
                    $statusTextColor = "black";
                }

                if ($parts[1] >= 500) {
                    $statusBkgColor = "red";
                }

                $headers[] = array(
                    "",
                    \sprintf(
                        self::HEADER_STATUS_STRING,
                        $line,
                        $statusBkgColor,
                        $statusTextColor
                    ),
                );
            } else {
                $headers[] = array(
                    "<background_light_gray><bold><black>{$parts[0]}:</black></bold></background_light_gray>",
                    \implode(":", \array_slice($parts, 1)),
                );
            }
        }
        $this->console->columns(($headers));
    }

    /**
     * Checks the provided hostname against a regular expression for format issues
     *
     * @param string $host
     * @return boolean
     */
    private static function isHostname(string $host): bool
    {
        return \filter_var(
            $host,
            \FILTER_VALIDATE_DOMAIN,
            \FILTER_FLAG_HOSTNAME
        ) ?
            \true :
            \false;
    }
}
