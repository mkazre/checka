<?php

namespace App\Libraries;

use CodeIgniter\Email\Email;
use ErrorException;

/**
 * CodeIgniter's Email, but low-level SMTP failures (can't connect, TLS
 * handshake failed, connection dropped mid-send) are added to the debug
 * messages. The stock class only writes them to the log file, so the
 * person sending saw nothing but "Unable to send email using SMTP".
 */
class CheckaEmail extends Email
{
    protected function sendWithSmtp()
    {
        try {
            return parent::sendWithSmtp();
        } catch (ErrorException $e) {
            $this->setErrorMessage('SMTP connection failed: ' . $e->getMessage() . '<br>');

            throw $e;
        }
    }
}
