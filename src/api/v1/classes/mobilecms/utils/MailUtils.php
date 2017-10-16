<?php namespace mobilecms\utils;

/**
 * Mail Utility.
 */
class MailUtils
{
    /**
     * eg : /var/www/html.
     */
    private $rootdir;

    /**
     * Constructor.
     */
    public function __construct(string $rootdir)
    {
        $this->rootdir = $rootdir;
    }

    /**
     * @param string $from mail address
     *
     * @return string mail headers
     */
    public function getHeaders(string $from) : string
    {
        $headers = 'From: ' . strip_tags($from) . '\r\n';
        $headers .= 'Reply-To: ' . strip_tags($from) . '\r\n';
        $headers .= 'MIME-Version: 1.0\r\n';
        $headers .= 'Content-Type: text/html; charset=UTF-8\r\n';

        return $headers;
    }

    /**
     * Generate new password. Should separate technical functions and business.
     *
     * @param string $subject    mail subject
     * @param string $password   new password
     * @param string $clientinfo client data (IP, browser, ...)
     *
     * @return string notification content
     */
    public function getNewPassword(string $subject, string $password, string $clientinfo) : string
    {
        $message = file_get_contents($this->rootdir . '/api/v1/mail/newpassword.html');
        $message = str_replace('%subject%', $subject, $message);
        $message = str_replace('%password%', $password, $message);
        $message = str_replace('%clientinfo%', $clientinfo, $message);

        return $message;
    }
}
