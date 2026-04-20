<?php

namespace App\Notifications\Messages;

class OneSignalMessage
{
    public string $title = '';

    public string $body = '';

    public ?string $url = null;

    public array $data = [];

    public ?string $icon = null;

    public static function create(string $body = ''): self
    {
        return (new self)->body($body);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function url(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function icon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
}
