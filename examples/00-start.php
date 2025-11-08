<?php

declare(strict_types=1);

use Kaspi\DiContainer\DiContainerFactory;

require_once \dirname(__DIR__).'/vendor/autoload.php';

class Envelope implements Stringable
{
    private string $subject = '';
    private string $body = '';

    public function __toString(): string
    {
        $subject = 'Subject: =?UTF-8?B?'.\base64_encode($this->subject).'?=';
        $body = $this->body;

        return <<< MAIL
Subject: {$subject}
MIME-Version: 1.0
Content-Type: text/html;charset=utf-8

{$body}
MAIL;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function message(string $message): static
    {
        $this->body = $message;

        return $this;
    }
}

class Mail
{
    public function __construct(private Envelope $envelope) {}

    public function envelop(): Envelope
    {
        return $this->envelope;
    }

    public function send(): false|int
    {
        $resource = @\fopen('php://memory', 'wb');

        return \fwrite($resource, (string) $this->envelope);
    }
}

class Post
{
    public string $title;
    // ...
}

// Контроллер для обработки действия.
class PostController
{
    public function __construct(private Mail $mail) {}

    public function send(Post $post): int
    {
        $this->mail->envelop()
            ->subject('Publication success')
            ->message('Post <'.$post->title.'> was published.')
        ;

        $r = $this->mail->send();

        return false !== $r ? $r : throw new RuntimeException('Cannot send message');
    }
}

$container = (new DiContainerFactory())->make();

// Заполняем модель данными.
$post = new Post();
$post->title = 'Publication about DiContainer';

// получить класс PostController с внедренным сервисом Mail и выполнить метод "send"
echo '🕸  DiContainer::get ✉  send bytes: '.$container->get(PostController::class)->send($post).PHP_EOL;

// Использование call
echo '🖥  DiContainer::call ✉  send bytes: '.$container->call(
    definition: [PostController::class, 'send'],
    arguments: ['post' => $post]
).PHP_EOL;
