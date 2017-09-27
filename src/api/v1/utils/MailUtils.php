<?php

class MailUtils
{
    public function getHeaders($from) : string
    {
        $headers = 'From: '.strip_tags($from)."\r\n";
        $headers .= 'Reply-To: '.strip_tags($from)."\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return $headers;
    }

    public function getNewPassword($subject, $password, $clientinfo) : string
    {
        $message = file_get_contents(HOME . '/api/v1/mail/newpassword.html');
        $message = str_replace('%subject%', $subject, $message);
        $message = str_replace('%password%', $password, $message);
        $message = str_replace('%clientinfo%', $clientinfo, $message);

        return $message;
    }

    public function getMailData($subject, $text) : string
    {
        $mail_Data = '';
        $mail_Data .= '<html>';
        $mail_Data .= '<head>';
        $mail_Data .= '<title>' . $subject . '</title>';
        $mail_Data .= '</head>';
        $mail_Data .= '<body>';
        $mail_Data .= str_replace('\n', '<br>', $text);
        $mail_Data .= '</body>';
        $mail_Data .= '</html>';

        return $mail_Data;
    }

    public function send($to, $from, $subject, $text)
    {
        return @mail($to, $Subject, $this->getMailData($subject, $text), $this->getHeaders($from));

        /*if ($result === FALSE) {
           throw new Exception('send error to ' . $to . ' ' .  $subject);
   }*/
    }
}
