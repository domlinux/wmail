<?php

namespace App\Mail\Transport;

use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class PHPMailerTransport extends AbstractTransport
{
    protected $mailer;

    public function __construct(PHPMailer $mailer, ?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher, $logger);
        $this->mailer = $mailer;
    }

    protected function doSend(SentMessage $message): void
    {
        $this->mailer->clearAllRecipients();
        $this->mailer->clearAttachments();
        $this->mailer->clearCustomHeaders();

        foreach ($message->getOriginalMessage()->getTo() as $to) {
            $this->mailer->addAddress($to->getAddress(), $to->getName());
        }

        foreach ($message->getOriginalMessage()->getCc() as $cc) {
            $this->mailer->addCC($cc->getAddress(), $cc->getName());
        }

        foreach ($message->getOriginalMessage()->getBcc() as $bcc) {
            $this->mailer->addBCC($bcc->getAddress(), $bcc->getName());
        }

        $this->mailer->Subject = $message->getOriginalMessage()->getSubject();

        // Set Charset
        $charset = $message->getOriginalMessage()->getHeaders()->getHeaderBody('X-Mailer-Charset') ?? 'utf-8';
        
        $this->mailer->CharSet = $charset;

        // Set Encoding
        $encoding = $message->getOriginalMessage()->getHeaders()->getHeaderBody('X-Mailer-Encoding');
        
        if ($encoding === '7bit') {
            $this->mailer->Encoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_7BIT;
        } elseif ($encoding === '8bit') {
            $this->mailer->Encoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_8BIT;
        } elseif ($encoding === 'base64') {
            $this->mailer->Encoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64;
        } elseif ($encoding === 'quoted-printable') {
            $this->mailer->Encoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_QUOTED_PRINTABLE;
        } else {
            // Default to 8bit if not specified or unknown
            $this->mailer->Encoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_8BIT;
        }

        if ($message->getOriginalMessage()->getHtmlBody()) {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $message->getOriginalMessage()->getHtmlBody();
            $this->mailer->AltBody = $message->getOriginalMessage()->getTextBody() ?? '';
        } else {
            $this->mailer->isHTML(false);
            $this->mailer->Body = $message->getOriginalMessage()->getTextBody();
        }

        foreach ($message->getOriginalMessage()->getAttachments() as $attachment) {
            // Determine attachment encoding based on X-Mailer-Encoding header
            $attachmentEncoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_8BIT; // Default
            if ($encoding === 'base64') {
                $attachmentEncoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64;
            } elseif ($encoding === 'quoted-printable') {
                $attachmentEncoding = \PHPMailer\PHPMailer\PHPMailer::ENCODING_QUOTED_PRINTABLE;
            }

            $attachmentBody = $attachment->getBody();
            $attachmentFilename = $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename');
            $attachmentType = $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(); // Correct MIME type
            $attachmentDisposition = $attachment->getDisposition(); // Correct disposition

            // Convert attachment filename to the target charset
            if (strtolower($charset) !== 'utf-8') {
                $convertedFilename = iconv('UTF-8', strtoupper($charset) . '//IGNORE', $attachmentFilename);
                if ($convertedFilename !== false) {
                    $attachmentFilename = $convertedFilename;
                }
            }

            $this->mailer->addStringAttachment(
                $attachmentBody,
                $attachmentFilename,
                $attachmentEncoding, // Encoding
                $attachmentType, // Type
                $attachmentDisposition // Disposition
            );
        }

        $this->mailer->send();
    }

    public function __toString(): string
    {
        return 'phpmailer';
    }
}