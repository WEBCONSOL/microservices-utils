<?php

namespace Ezpizee\Utils;

use RuntimeException;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;

final class Mailer
{
    private $recipients = [];
    private $subject;
    private $content;
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function send(): bool
    {
        try {
            $email = new Mail();

            $email->setFrom(SENDGRID_SEND_FROM, SENDGRID_SEND_FROM_NAME);

            $email->setSubject($this->getSubject());
            foreach ($this->getRecipients() as $receiver) {$email->addTo($receiver['email'], $receiver['name']);}

            $email->addContent("text/html", $this->getContent());

            $sendGrid = new SendGrid(SENDGRID_API_KEY);
            $sendGrid->send($email);

            return true;
        }
        catch (TypeException $e) {
            throw new RuntimeException($e->getMessage(), ResponseCodes::CODE_ERROR_INTERNAL_SERVER);
        }
    }

    public function addRecipient(string $email, string $name=null) {
        if (!StringUtil::isEmail($email)) {
            throw new RuntimeException(ResponseCodes::MESSAGE_ERROR_INVALID_EMAIL, ResponseCodes::CODE_ERROR_INTERNAL_SERVER);
        }
        $this->recipients[] = ['email'=>$email, 'name'=>$name];
    }
    public function getRecipients(): array
    {
        if (empty($this->recipients)) {
            $to = $this->request->getRequestParam('recipient_email');
            if (!StringUtil::isEmail($to)) {
                throw new RuntimeException(ResponseCodes::MESSAGE_ERROR_INVALID_EMAIL, ResponseCodes::CODE_ERROR_INTERNAL_SERVER);
            }
            $this->recipients[] = ['email'=>$to, 'name'=>$this->request->getRequestParam('recipient_name')];
        }
        return $this->recipients;
    }

    public function setSubject(string $subject) {$this->subject = $subject;}
    public function getSubject(): string
    {
        if (!$this->subject) {
            $this->subject = $this->request->getRequestParam('subject');
        }
        return $this->subject;
    }

    public function setContent(string $content) {$this->content = $content;}
    public function getContent(): string
    {
        if (!$this->content) {
            $this->content = $this->request->getRequestParam('email_content');
        }
        return $this->content;
    }
}