<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\Http\Request;

class Request
{
    private array $queryBag;
    private array $postBag;
    private array $requestUriBag;

    public function __construct()
    {
        $this->queryBag = $this->prepareQueryBag();
        $this->postBag = $this->preparePostBag();
        $this->requestUriBag = $this->prepareRequestUriBag();
    }

    private function prepareQueryBag(): array
    {
        if (empty($_GET)) {
            return [];
        }

        return $this->prepareBag($_GET);
    }

    private function prepareBag($source): array
    {
        $bag = [];

        if (!is_array($source)) {
            $source = [$source];
        }

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $val = [];

                foreach ($value as $k_index => $k_val) {
                    $kv = strip_tags($k_val);

                    $val[$k_index] = is_numeric($kv) ? $kv : urldecode($kv);
                }
            } else {
                $val = strip_tags(urldecode($value));
            }

            $bag[$key] = $val;
        }

        return $bag;
    }

    private function preparePostBag(): array
    {
        if (empty($_POST)) {
            return [];
        }

        return $this->prepareBag($_POST);
    }

    private function prepareRequestUriBag(): array
    {
        $queryParams = explode('?', $_SERVER['REQUEST_URI'] ?? '');

        if (!empty($queryParams)) {
            $requestUri = array_shift($queryParams);
        } else {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        }

        $parts = array_map(function ($part) {
            return trim($part);
        }, explode('/', $requestUri));

        $prepared = [];

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $prepared[] = urldecode($part);
        }

        return $prepared;
    }

    public static function fromGlobals(): self
    {
        return new self();
    }

    public function getContent(): ?string
    {
        $content = file_get_contents('php://input');

        if (empty($content)) {
            return null;
        }

        return $content;
    }

    public function getQueryBag(): array
    {
        return $this->queryBag;
    }

    public function getPostBag(): array
    {
        return $this->postBag;
    }

    public function getRequestUriBag(): array
    {
        return $this->requestUriBag;
    }
}