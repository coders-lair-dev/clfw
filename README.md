# Минимальный PHP-фреймворк (ClFw)

Учебно-демонстрационный микрофреймворк. Написан как материал к открытому уроку [Как устроены современные PHP-фреймворки](https://vkvideo.ru/video-145052891_456249610?pl=-145052891_497), чтобы
показать, как устроены DI-контейнер и роутинг современных
PHP-фреймворков (Symfony, Laravel и т.п.).

> **Это обучающий код.** Ряд механизмов намеренно упрощён ради наглядности. 
> Места, где production-решение выглядело бы иначе, отмечены ниже в разделе
> [«Сознательные упрощения»](#сознательные-упрощения). Это сделано сознательно.

## Требования
- PHP 8.2+
- nginx + php-fpm (пример конфигурации - ниже)

## Установка
```
composer require coderslairdev/clfw
```

## Что демонстрирует
- **DI-контейнер с autowiring** - автоматическое разрешение зависимостей по
  типам параметров конструктора, через Reflection.
- **Сканирование сервисов** - рекурсивный обход каталогов приложения и
  построение карты сервисов из найденных классов.
- **Атрибутный роутинг** - `#[AsController]` / `#[AsRoute]`, как в актуальном
  Symfony.
- **PSR-совместимость** - PSR-7 (HTTP-сообщения) и PSR-17 (фабрики) на базе
  `nyholm/psr7`.
- **Middleware pipeline** - запрос проходит через цепочку middleware до dispatch.

## Как это работает внутри

При старте `Kernel`:

1. **Сканирует** каталоги, указанные в конфиге (`services`), и для каждого
   найденного класса строит объект `\ReflectionClass`.
2. **Инстанцирует листья графа** - сначала создаёт все сервисы *без зависимостей*
   (конструктор отсутствует или без параметров).
3. **Достраивает граф итеративно.** Пока созданы не все найденные классы,
   контейнер делает повторные проходы: на каждом проходе пытается собрать
   сервисы, все зависимости которых уже инстанцированы (зависимости берутся по
   типу параметра конструктора).
4. **Строит карту роутов** из атрибутов `#[AsRoute]` на методах контроллеров,
   помеченных `#[AsController]`.
5. На каждый HTTP-запрос: запрос (PSR-7) проходит через middleware pipeline и
   попадает в `dispatch()`, который по карте роутов находит контроллер и
   возвращает PSR-7 `ResponseInterface`.

## Сознательные упрощения

- **Нет детекции циклических зависимостей.** Цикл разрешения предполагает, что
  граф ацикличен и разрешим. При циклической (`A -> B -> A`) или неразрешимой
  зависимости production-контейнер обнаружил бы, что за полный проход не
  добавилось ни одного сервиса, и бросил бы исключение с описанием цикла. Здесь
  это опущено ради простоты примера.
- **Эффективность принесена в жертву наглядности.** Многопроходный алгоритм в
  худшем случае близок к O(n^2). На реальных объёмах разумнее однопроходный
  топологический resolve по построенному графу либо ленивая инициализация по
  требованию (как делает Symfony) с компиляцией контейнера.
- **Autowiring - только по типу.** Разрешаются зависимости-объекты по типу
  параметра конструктора. Скалярные параметры, union-типы, значения по умолчанию
  и именованные аргументы конфигурации - вне зоны демонстрации.
- **PSR-17 фабрика в контроллере создаётся через `new`** (в примере
  `RootController`) - ради краткости.

## Регистрация сервисов вне сканирования

Классы, живущие во фреймворке (`vendor/`), сканированием не охватываются, поэтому
регистрируются явно через `factories` - это показывает разницу между autowiring и
ручной регистрацией фабрикой:

```php
'factories' => [
    MiddlewarePipeline::class => fn($c) => new MiddlewarePipeline(),
],
```

## Запуск

Точка входа - `public/index.php`. Конфигурация (каталоги сервисов, фабрики,
bootstrap-хуки middleware) задаётся массивом и передаётся в `Kernel`. Пример -
в `public/index.php`.

### nginx.conf (для docker-окружения)

```nginx
server {
    listen 80;
    server_name localhost;

    error_log  /dev/stderr;
    access_log /dev/stdout;

    root /app/public;

    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    rewrite ^/index\.php/?(.*)$ /$1 permanent;

    try_files $uri @rewriteapp;

    location @rewriteapp {
        rewrite ^(.*)$ /index.php/$1 last;
    }

    location ~ /\. {
        deny all;
    }

    location ~ ^/index\.php(/|$) {
        internal;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_index index.php;
        send_timeout 1800;
        fastcgi_read_timeout 1800;
        fastcgi_pass php-fpm:9000;
    }
}
```

## Пример контроллера

```php
<?php

namespace App\Root\Infrastructure\Http\Web;

use CodersLairDev\ClFw\Http\Response\Trait\ResponseTrait;
use CodersLairDev\ClFw\Routing\Attribute\AsController;
use CodersLairDev\ClFw\Routing\Attribute\AsRoute;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

#[AsController]
class RootController
{
    use ResponseTrait;

    #[AsRoute(path: '/')]
    public function rootIndex(): ResponseInterface
    {
        $data = [
            'success' => true,
            'data' => __CLASS__ . '::' . __FUNCTION__ . '()',
            'messages' => [uniqid()],
        ];

        return $this->createResponse(
            psr17Factory: new Psr17Factory(),
            content: json_encode($data, JSON_THROW_ON_ERROR),
            status: 200
        );
    }
}
```
