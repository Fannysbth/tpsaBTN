<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AssessmentReport extends Mailable
{
    use Queueable, SerializesModels;

    public $assessment;
    public $filePath;
    public $customSubject;
    public $customMessage;

    public function __construct($assessment, $filePath, $subject, $message)
    {
        $this->assessment = $assessment;
        $this->filePath = $filePath;
        $this->customSubject = $subject;
        $this->customMessage = $message;
    }

    public function build()
    {
        return $this->subject($this->customSubject)
                    ->view('emails.assessment-report')
                    ->attach(storage_path('app/' . $this->filePath));
    }
}