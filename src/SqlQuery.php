<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PDOStatement;
use Ray\Di\Di\Named;

use function array_pop;
use function count;
use function explode;
use function file_get_contents;
use function is_array;
use function sprintf;
use function stripos;
use function strpos;
use function strtolower;
use function trim;

class SqlQuery implements SqlQueryInterface
{
    /** @var ExtendedPdoInterface  */
    private $pdo;

    /** @var MediaQueryLoggerInterface  */
    private $logger;

    /** @var string */

    /** @var string */
    private $sqlDir;

    /** @var PDOStatement */
    private $pdoStatement;

    #[Named('sqlDir=Ray\MediaQuery\Annotation\SqlDir')]
    public function __construct(
        ExtendedPdoInterface $pdo,
        MediaQueryLoggerInterface $logger,
        string $sqlDir
    ) {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->sqlDir = $sqlDir;
    }

    public function exec(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): void
    {
        $this->perform($sqlId, $params, $fetchMode);
    }

    public function getRow(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array
    {
        $rowList = $this->perform($sqlId, $params, $fetchMode);
        $row = array_pop($rowList);

        return (array) $row;
    }

    public function getRowList(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array
    {
        return $this->perform($sqlId, $params, $fetchMode);
    }

    /**
     * @return array<string>
     */
    private function perform(string $sqlId, array $params, int $fetchModode): array
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $sqls = $this->getSqls($sqlFile);
        if (count($sqls) === 0) {
            return [];
        }

        foreach ($sqls as $sql) {
            $pdoStatement = $this->pdo->perform($sql, $params);
        }

        $this->logger->log($sqlId, $params);

        $lastQuery = trim((string) $pdoStatement->queryString);
        if (stripos($lastQuery, 'select') === 0) {
            return (array) $pdoStatement->fetchAll($fetchModode);
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

    public function getStatement(): PDOStatement
    {
        return $this->pdoStatement;
    }
}
