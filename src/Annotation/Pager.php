<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;

/**
 * Paginates the #[DbQuery] result into a lazily queried PagesInterface
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/database_media.html#pagination
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Pager
{
    /**
     * @param int|string $perPage  int:the number of items, string: the name of the argument of the number of items
     * @param string     $template URI template of the page links in the pager HTML
     */
    public function __construct(
        public int|string $perPage = 10,
        public string $template = '/{?page}',
    ) {
    }
}
