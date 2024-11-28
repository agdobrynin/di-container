<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.my-srv')] // 🔃 Получить по идентификатору контейнера
interface MyInterface {}
