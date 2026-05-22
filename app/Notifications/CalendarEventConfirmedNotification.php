<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CalendarEventConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly CalendarEvent $calendarEvent) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->calendarEvent;

        return (new MailMessage)
            ->subject('Your session has been confirmed')
            ->greeting('Hello!')
            ->line('Great news — your upcoming session has been confirmed by the mentor.')
            ->line('**'.$event->title.'**')
            ->line('Date: '.$event->start_date_time->format('D, d M Y'))
            ->line('Time: '.$event->start_date_time->format('H:i').' – '.$event->end_date_time->format('H:i').' UTC')
            ->action('View Event', route('pages.calendar.show', $event))
            ->line('See you there!');
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $event = $this->calendarEvent;

        return [
            'type'              => 'calendar_event_confirmed',
            'calendar_event_id' => $event->getKey(),
            'title'             => $event->title,
            'start_date_time'   => $event->start_date_time->toIso8601String(),
            'end_date_time'     => $event->end_date_time->toIso8601String(),
            'message'           => 'Your session "'.$event->title.'" has been confirmed.',
        ];
    }
}
