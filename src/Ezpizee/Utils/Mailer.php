<?php

namespace Ezpizee\Utils;

use RuntimeException;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;

final class Mailer
{
    private $apiKey = '';
    private $fromEmail = "";
    private $fromName = "";
    private $recipients = [];
    private $subject;
    private $content;

    public function __construct(array $settings){
        if (isset($settings['apiKey'])) {
            $this->apiKey = $settings['apiKey'];
        }
        if (isset($settings['fromEmail'])) {
            $this->fromEmail = $settings['fromEmail'];
        }
        if (isset($settings['fromName'])) {
            $this->fromName = $settings['fromName'];
        }
        if (isset($settings['recipients']) && is_array($settings['recipients'])) {
            $this->recipients = $settings['recipients'];
        }
        if (isset($settings['subject'])) {
            $this->subject = $settings['subject'];
        }
        if (isset($settings['content'])) {
            $this->content = $settings['content'];
        }
    }

    public function send(): bool
    {
        if ($this->valid()) {
            try {
                $envelop = new Mail();
                $envelop->setFrom($this->fromEmail, $this->fromName);
                $envelop->setSubject($this->subject);
                $envelop->addContent("text/html", $this->content);
                foreach ($this->recipients as $receiver) {
                    $envelop->addTo($receiver['email'], $receiver['name']);
                }
                $sendGrid = new SendGrid($this->apiKey);
                $sendGrid->send($envelop);
                return true;
            }
            catch (TypeException $e) {
                throw new RuntimeException($e->getMessage(),
                    ResponseCodes::CODE_ERROR_INTERNAL_SERVER);
            }
        }
        else {
            throw new RuntimeException('Invalid data for SendGrid',
                ResponseCodes::CODE_ERROR_INVALID_DATA);
        }
    }

    private function valid(): bool {
        return !empty($this->fromEmail) &&
            !empty($this->fromName) &&
            !empty($this->apiKey) &&
            !empty($this->recipients) &&
            !empty($this->subject) &&
            !empty($this->content);
    }

    public function setApiKey(string $apiKey): void {$this->apiKey = $apiKey;}

    public function setFrom(string $email, string $name): void {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    public function addRecipient(string $email, string $name=null) {
        if (!StringUtil::isEmail($email)) {
            throw new RuntimeException('Invalid recipient email address for SendGrid',
                ResponseCodes::CODE_ERROR_INTERNAL_SERVER);
        }
        $this->recipients[] = ['email'=>$email, 'name'=>$name];
    }

    public function setSubject(string $subject) {$this->subject = $subject;}

    public function setContent(string $content) {$this->content = $content;}
}