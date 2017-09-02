<?php

class MailUtils
{
  public function getHeaders($from) : string
  {
    $headers  = 'MIME-Version: 1.0 \n';
    $headers .= 'Content-type: text/html; charset=utf-8 \n';
    $headers .= 'From:' . $from .'\n';
    $headers .= 'Disposition-Notification-To:'. $from . '\n';
    return $headers;
  }

  public function getMailData($subject, $text) : string
  {
    $mail_Data = '';
    $mail_Data .= '<html>';
    $mail_Data .= '<head>';
    $mail_Data .= '<title>' . $subject . '</title>';
    $mail_Data .= '</head>';
    $mail_Data .= '<body>';
    $mail_Data .=   $bodytag = str_replace('\n', '<br>', $text);
    $mail_Data .= '</body>';
    $mail_Data .= '</html>';

    return $mail_Data;
  }

  public function send($to, $from, $subject, $text) {

    return @mail($to, $Subject, $this->getMailData($subject, $text), $this->getHeaders($from));

    /*if ($result === FALSE) {
       throw new Exception('send error to ' . $to . ' ' .  $subject);
   }*/

  }

}
