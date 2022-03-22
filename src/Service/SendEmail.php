<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendEmail
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        string $senderEmail,
        string $senderName
    )
    {
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * @param array<mixed> $arguments
     * @throws TransportExceptionInterface
     */
    public function send(array $arguments): void
    {
        [
            'email_recipient' => $emailRecipient,
            'subject' => $subject,
            'html_template' => $htmlTemplate,
            'context' => $context
        ] = $arguments;

        $email = new TemplatedEmail();
        $email->from(new Address($this->senderEmail, $this->senderName))
            ->to($emailRecipient)
            ->subject($subject)
            ->htmlTemplate($htmlTemplate)
            ->context($context)
        ;

        $this->mailer->send($email);
    }
}
