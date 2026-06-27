<?php

namespace Nodeloc\Telegram\Job;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Nodeloc\Telegram\Notifications\TelegramMailer;

class SendTelegramNotificationJob extends AbstractJob
{
    /**
     * @param User[] $recipients
     */
    public function __construct(
        protected BlueprintInterface $blueprint,
        protected array $recipients = []
    ) {
    }

    public function handle(TelegramMailer $mailer): void
    {
        $mailer->send($this->blueprint, $this->recipients);
    }
}
