<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;

/**
 * Runs the SQL file of the ID with the method arguments and returns the result
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/database_media.html#dbquery
 * @see \Ray\MediaQuery\DbQueryInterceptor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DbQuery
{
    /**
     * @param string           $id      SQL file name in the SQL directory, without `.sql`
     * @param 'row'|'row_list' $type    `row` returns a single row for an `array` return type
     * @param string           $factory Class whose factory method creates each row
     */
    public function __construct(
        public string $id,
        public string $type = 'row_list',
        public string $factory = '',
    ) {
    }
}
