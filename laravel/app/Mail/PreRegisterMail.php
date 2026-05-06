<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

class PreRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $registerUrl;

    public function __construct(string $registerUrl)
    {
        $this->registerUrl = $registerUrl;
    }

    public function build()
    {
        return $this->subject('会員登録のご案内')
            ->view('emails.pre_register')
            ->with(['url' => $this->registerUrl]);
    }
}