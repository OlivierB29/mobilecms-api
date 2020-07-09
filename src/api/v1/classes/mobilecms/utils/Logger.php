<?php namespace mobilecms\utils;

/**
 * Basic logger class.
 * Migrate to Monolog ? https://github.com/Seldaek/monolog
 */
class Logger
{
    /**
     * output file
     */
    private $file = '';

    /**
     * log to console
     */
    private $console = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $value : message to log
     */
    public function info($value)
    {
        $level = 'INFO';
        $this->log('INFO', $value);
    }

    /**
    * @param string $level : TRACE, DEBUG, INFO, WARN, ERROR, FATAL
    * @param string $value : message to log
    */
    public function log($level, $value)
    {
        $message = '';

        $message .= $level;

        if (!empty($value)) {
            $message .= ' ';
            $message .= $value;
        }

        // console log enabled
        if ($this->console) {
            echo $message;
        }

        if (empty($this->file)) {
            // default log destination
            error_log($message);
        } else {
            // log to file
            // message is appended to the file destination. A newline is not automatically added to the end of the message string.
            error_log($message . \n, 3, $this->file);
        }
        
    }

    /**
     * Set output file.
     */
    public function setFile(string $newval)
    {
        $this->file = $newval;
    }
}
