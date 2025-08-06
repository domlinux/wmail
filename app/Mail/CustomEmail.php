<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Component\Mime\Part\HtmlPart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;

class CustomEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $content = $this->data['content'];
        $charset = $this->data['charset'];

        // Convert content to the target charset if it's not UTF-8
        if (strtolower($charset) !== 'utf-8') {
            $convertedContent = iconv('UTF-8', strtoupper($charset) . '//IGNORE', $content);
            if ($convertedContent === false) {
                // Handle conversion error, fallback to original content or throw exception
                \Illuminate\Support\Facades\Log::warning('Failed to convert content from UTF-8 to ' . $charset . '. Using original content.');
            } else {
                $content = $convertedContent;
            }
        }

        $email = $this->subject(iconv('UTF-8', strtoupper($charset) . '//IGNORE', $this->data['subject']));

        if ($this->data['content_type'] === 'html') {
            $email->html($content, $charset);
        } elseif ($this->data['content_type'] === 'text') {
            $email->text('emails.custom_text', ['content' => $content], $charset);
        } elseif ($this->data['content_type'] === 'both') {
            $email->html($content, $charset);
            // Attempt to convert HTML to plain text for AltBody
            $plainTextContent = strip_tags($content);
            $email->text('emails.custom_text', ['content' => $plainTextContent], $charset);
        }

        if (isset($this->data['attachment'])) {
            $email->attach(
                $this->data['attachment']->getRealPath(),
                [
                    'as' => $this->data['attachment']->getClientOriginalName(),
                    'mime' => $this->data['attachment']->getMimeType(),
                ]
            );
        }

        $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) {
            // Store the chosen encoding in a custom header for PHPMailerTransport to read
            $message->getHeaders()->addTextHeader('X-Mailer-Encoding', $this->data['encoding']);
            // Store the chosen charset in a custom header for PHPMailerTransport to read
            $message->getHeaders()->addTextHeader('X-Mailer-Charset', $this->data['charset']);

            $body = $message->getBody();
            $newBody = null;

            $encoding = $this->data['encoding'] === '7bit' ? null : $this->data['encoding'];

            $encoder = function ($part) use ($encoding) {
                if ($part instanceof TextPart) {
                    return new TextPart($part->getBody(), $this->data['charset'], $part->getMediaSubtype(), $encoding);
                }
                if ($part instanceof HtmlPart) {
                    return new HtmlPart($part->getBody(), $this->data['charset'], $encoding);
                }
                return $part;
            };

            if ($body instanceof MixedPart) {
                $parts = $body->getParts();
                $newParts = array_map($encoder, $parts);
                $newBody = new MixedPart(...$newParts);
            } else {
                $newBody = $encoder($body);
            }

            if ($newBody) {
                $message->setBody($newBody);
            }
        });

        return $email;
    }
}
