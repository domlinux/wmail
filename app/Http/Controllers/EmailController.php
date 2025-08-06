<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\CustomEmail;
use Exception;

class EmailController extends Controller
{
    private $settingsFile = 'email_settings.json';

    public function create()
    {
        $defaultSettings = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'content' => 'This is a test email.',
            'content_type' => 'both',
            'charset' => 'utf-8',
            'encoding' => 'base64',
        ];

        if (Storage::disk('local')->exists($this->settingsFile)) {
            $savedSettings = json_decode(Storage::disk('local')->get($this->settingsFile), true);
            $defaultSettings = array_merge($defaultSettings, $savedSettings);
        }

        return view('email.create', compact('defaultSettings'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'content' => 'required|string',
            'attachment' => 'nullable|file',
            'content_type' => 'required|in:text,html,both',
            'charset' => 'required|in:utf-8,gbk,big5',
            'encoding' => 'required|in:7bit,8bit,base64,quoted-printable',
        ]);

        // Handle attachment separately to avoid serialization issues
        $attachment = $request->file('attachment');
        $mailData = $data;
        unset($mailData['attachment']); // Remove UploadedFile instance from $mailData

        try {
            $email = new CustomEmail($mailData);
            if ($attachment) {
                $email->attach($attachment->getRealPath(), [
                    'as' => $attachment->getClientOriginalName(),
                    'mime' => $attachment->getMimeType(),
                ]);
            }
            Mail::to($mailData['to'])->send($email);

            // Save current settings for next time
            $settingsToSave = [
                'to' => $mailData['to'],
                'subject' => $mailData['subject'],
                'content' => $mailData['content'],
                'content_type' => $mailData['content_type'],
                'charset' => $mailData['charset'],
                'encoding' => $mailData['encoding'],
            ];
            Storage::disk('local')->put($this->settingsFile, json_encode($settingsToSave));

            // Flash only the relevant input for old() helper
            $request->session()->flashInput($settingsToSave);

            return back()->with('success', 'Email sent successfully!');
        } catch (Exception $e) {
            Log::error('Failed to send email. Error: ' . $e->getMessage());

            return back()->with('error', 'Failed to send email. Please check the logs for more details.');
        }
    }
}
