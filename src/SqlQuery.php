<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PDOStatement;

use function array_pop;
use function count;
use function explode;
use function file_get_contents;
use function stripos;
use function strpos;
use function strtolower;
use function trim;

class SqlQuery implements SqlQueryInterface
{
    private ExtendedPdoInterface $pdo;
    private MediaQueryLoggerInterface $log;

    public function __construct(ExtendedPdoInterface $pdo, MediaQueryLoggerInterface $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    /**
     * @return array<string, string>
     */
    public function __invoke(string $sqlFile, array $params): array
    {
        $sqls = $this->getSqls($sqlFile);
        if (count($sqls) === 0) {
            return [];
        }

        foreach ($sqls as $sql) {
            $pdoStatement = $this->pdo->perform($sql, $params);
        }

        $lastQuery = trim((string) $pdoStatement->queryString);
        if (stripos($lastQuery, 'select') === 0) {
            $fetchResult = (array) $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
            $isSingleRow = count($fetchResult) === 1;

            return $isSingleRow ? array_pop($fetchResult) : $fetchResult;
        }

        return [];
    }

    /**
     * @return array<string>
     */
    private function getSqls(string $sqlFile): array
    {
        $sqls = (string) file_get_contents($sqlFile);
        if (! strpos($sqls, ';')) {
            $sqls .= ';';
        }

        $sqls = explode(';', trim($sqls, "\\ \t\n\r\0\x0B"));
        array_pop($sqls);

        return $sqls;
    }
}
