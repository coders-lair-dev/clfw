# Минимальный PHP-фреймворк

## Описание
Простой микрофреймворк для демонстрации принципов работы Dependency Injection современных PHP-фреймворков (например, Symfony, Laravel etc.).

## Требования
- PHP 8.2+

### nginx.conf (для docker-окружения)
```
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

### index.php (образец)

```php
<?php

declare(strict_types=1);


use CodersLairDev\ClFw\Http\Middleware\MiddlewarePipeline;
use CodersLairDev\ClFw\Kernel\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$projectDir = dirname(__DIR__);

$config = [
    /*
     * Здесь сервисы, контроллеры etc. самого приложения
     */
    'services' => [
        [
            'path' => 'src/Root',
            'namespace' => 'App\Root',
        ],
        //  [
        //       'path' => 'src/Payment',
        //       'namespace' => 'App\Payment',
        // ],
    ],

    /*
     * MiddlewarePipeline живёт во фреймворке (vendor/), сканирование туда не идёт,
     * поэтому регистрируется явно.
     */
    'factories' => [
        MiddlewarePipeline::class => fn($c) => new MiddlewarePipeline(),
    ],
    
    /*
     * Pipeline для примера.
     * Запрос проходит через пустой pipeline и попадает прямо в dispatch().
     */
    'bootstrap' => [
        // function ($c) {
        //     $pipeline = $c->getService(MiddlewarePipeline::class);
        //     // $pipeline->add($c->getService(...));
        // },
    ],
];


$kernel = new Kernel($projectDir, $config);
$kernel->run();
```

### Пример App\Root\Infrastructure\Http\Web\RootController
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
            'messages' => [
                uniqid(),
            ],
        ];

        return $this->createResponse(
            psr17Factory: new Psr17Factory(),
            content: json_encode($data),
            status: 200
        );
    }
}
```