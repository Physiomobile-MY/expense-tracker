<?php

namespace App\Mail;

use App\Models\ExpenseRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimFinanceNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ExpenseRecord $record,
        public string $event,
        public ?User $actor = null,
        public ?string $remarks = null,
    ) {
        $this->record->loadMissing(['user', 'department', 'category']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->event) {
                'approved' => '[ExpenseFlow] Claim approved '.$this->record->claim_reference_no,
                default => '[ExpenseFlow] Claim pending approval '.$this->record->claim_reference_no,
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.claims.finance-notification',
        );
    }
}
