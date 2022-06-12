<?php

declare(strict_types=1);

namespace Ray\MediaQuery\WebQuery;

use function assert;
use function file_get_contents;
use function is_string;
use function json_decode;
use function property_exists;

final class WebQueryConfig
{
    /** @var array<string, array{method: string, path: string}> */
    public $apis = [];

    /** @var array<string, string> */
    public $urlTemplateBindings;

    /**
     * @param array<string, string> $urlTemplateBindings
     */
    public function __construct(string $mediaQueryJson, array $urlTemplateBindings = [])
    {
        $this->urlTemplateBindings = $urlTemplateBindings;
        /** @var object $json */
        $json = json_decode((string) file_get_contents($mediaQueryJson));
        assert(property_exists($json, 'webQuery'));
        /** @var object $item */
        foreach ($json->webQuery as $item) {
            assert(property_exists($item, 'id'));
            assert(property_exists($item, 'method'));
            assert(property_exists($item, 'path'));
            assert(is_string($item->id));
            assert(is_string($item->method));
            assert(is_string($item->path));
            $this->apis[$item->id] = ['method' => $item->method, 'path' => $item->path];
        }
    }
}
