<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use LogicException;
use PDO;
use PDOStatement;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactoryInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\Di\Di\Named;

use function array_pop;
use function assert;
use function count;
use function explode;
use function file;
use function file_get_contents;
use function is_bool;
use function sprintf;
use function stripos;
use function strpos;
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

    /** @var ?PDOStatement */
    private $pdoStatement;

    /** @var AuraSqlPagerFactoryInterface */
    private $pagerFactory;

    #[Named('sqlDir=Ray\MediaQuery\Annotation\SqlDir')]
    public function __construct(
        ExtendedPdoInterface $pdo,
        string $sqlDir,
        MediaQueryLoggerInterface $logger,
        AuraSqlPagerFactoryInterface $pagerFactory
    ) {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->sqlDir = $sqlDir;
        $this->pagerFactory = $pagerFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): void
    {
        $this->perform($sqlId, $params, $fetchMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getRow(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array
    {
        $rowList = $this->perform($sqlId, $params, $fetchMode);
        /** @var array<string, mixed> $row */
        $row = (array) array_pop($rowList);

        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function getRowList(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array
    {
        /** @var array<array<mixed>> $list */
        $list =  $this->perform($sqlId, $params, $fetchMode);

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function getCount(string $sqlId, array $params): int
    {
        return (new ExtendedPdoAdapter($this->pdo, $this->getSql($sqlId), $params))->getNbResults();
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<mixed>
     */
    private function perform(string $sqlId, array $params, int $fetchModode): array
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $sqls = $this->getSqls($sqlFile);
        if (count($sqls) === 0) {
            return [];
        }
        $this->logger->start();

        foreach ($sqls as $sql) {
            $pdoStatement = $this->pdo->perform($sql, $params);
        }

        assert(isset($pdoStatement)); // @phpstan-ignore-line
        assert($pdoStatement instanceof PDOStatement);
        $lastQuery = trim((string) $pdoStatement->queryString);
        $isSelect = stripos($lastQuery, 'select') !== 0;
        $result = $isSelect ? (array) $pdoStatement->fetchAll($fetchModode) : [];
        $this->logger->log($sqlId, $params);

        return $result;
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

    public function getStatement(): ?PDOStatement
    {
        return $this->pdoStatement;
    }

    /**
     * {@inheritDoc}
     */
    public function getPages(string $sqlId, array $params, int $perPage, string $queryTemplate = '/{?page}'): Pages
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $pager = $this->pagerFactory->newInstance($this->pdo, $this->getSql($sqlId), $params, $perPage, $queryTemplate);

        return new Pages($pager, $this->pdo, $this->getSql($sqlId), $params);
    }

    private function getSql(string $sqlId): string
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $file = file($sqlFile);
        if (is_bool($file) || ! isset($file[0])) {
            throw new LogicException($sqlId);
        }

        $firstRow = $file[0];

        return trim($firstRow, "; \n\r\t\v\0");
    }
}
