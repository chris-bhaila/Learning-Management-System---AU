<?php

namespace App\Mail;

use App\Models\User;
use App\Repositories\Contracts\SiteContentRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to {$this->siteName()}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome',
            with: ['siteName' => $this->siteName()],
        );
    }

    /** Resolved at render time (when the queued job actually runs), not at
     *  construction/queue-push time — so a site name change is reflected in
     *  every email sent after the change, not just ones queued after it. */
    private function siteName(): string
    {
        return app(SiteContentRepositoryInterface::class)->get('site.name', 'EduNest');
    }
}
