<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use PDOStatement;
use Ray\MediaQuery\Annotation\SqlTemplate;

use function str_replace;

final class PerformTemplatedSql implements PerformSqlInterface
{
    public function __construct(
        #[SqlTemplate]
        private string $sqlTemplate,
    ) {
    }

    #[Override]
    public function perform(ExtendedPdoInterface $pdo, string $sqlId, string $sql, array $values): PDOStatement
    {
            $templatedSql = str_replace(['{{ id }}', '{{ sql }}'], [$sqlId, $sql], $this->sqlTemplate);

            /** @var array<string, mixed> $values */
            return $pdo->perform($templatedSql, $values);
    }
}
