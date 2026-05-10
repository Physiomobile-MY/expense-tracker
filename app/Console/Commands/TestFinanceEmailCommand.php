<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestFinanceEmailCommand extends Command
{
    protected $signature = 'expenseflow:test-finance-email {--to= : Override the finance notification recipient}';

    protected $description = 'Send a test email using the configured finance notification SMTP settings.';

    public function handle(): int
    {
        $to = (string) ($this->option('to') ?: config('expenseflow.notifications.finance_approval_email'));

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Finance notification email is not valid.');

            return self::FAILURE;
        }

        $this->line('Mailer: '.config('mail.default'));
        $this->line('SMTP host: '.(config('mail.mailers.smtp.host') ?: '-'));
        $this->line('SMTP port: '.(config('mail.mailers.smtp.port') ?: '-'));
        $this->line('SMTP scheme: '.(config('mail.mailers.smtp.scheme') ?: 'auto'));
        $this->line('From: '.config('mail.from.address'));
        $this->line('To: '.$to);

        try {
            Mail::raw('SMTP test from Physiomobile ExpenseFlow.', function ($message) use ($to): void {
                $message
                    ->to($to)
                    ->subject('ExpenseFlow SMTP Test');
            });
        } catch (Throwable $exception) {
            $this->error('Email failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Test email sent.');

        return self::SUCCESS;
    }
}
