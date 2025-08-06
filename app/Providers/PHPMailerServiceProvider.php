<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class PHPMailerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Mail::extend('phpmailer', function ($config) {
            $mailer = new PHPMailer(true);

            $mailer->isSMTP();
            $mailer->Host = $config['host'];
            $mailer->Port = $config['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['username'];
            $mailer->Password = $config['password'];
            $mailer->SMTPSecure = $config['encryption'];

            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => $config['verify_peer'] ?? false,
                    'verify_peer_name' => $config['verify_peer_name'] ?? false,
                    'allow_self_signed' => $config['allow_self_signed'] ?? true,
                ],
            ];

            if (!empty($config['local_cert']) && !empty($config['local_pk'])) {
                $mailer->ClientCert = $config['local_cert'];
                $mailer->ClientKey = $config['local_pk'];
            }

            // Enable verbose debug output
            // $mailer->SMTPDebug = SMTP::DEBUG_SERVER;

            return new \App\Mail\Transport\PHPMailerTransport($mailer);
        });
    }
}
