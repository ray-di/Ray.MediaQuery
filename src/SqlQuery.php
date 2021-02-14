<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use DateTimeInterface;
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
    private const MYSQL_DATETIME = 'Y-m-d H:i:s';

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

    /**
     * @Named("sqlDir=Ray\MediaQuery\Annotation\SqlDir")
     */
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
    public function exec(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC): void
    {
        $this->perform($sqlId, $values, $fetchMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getRow(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC): array
    {
        $rowList = $this->perform($sqlId, $values, $fetchMode);
        /** @var array<string, mixed> $row */
        $row = (array) array_pop($rowList);

        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function getRowList(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC): array
    {
        /** @var array<array<mixed>> $list */
        $list =  $this->perform($sqlId, $values, $fetchMode);

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function getCount(string $sqlId, array $values): int
    {
        return (new ExtendedPdoAdapter($this->pdo, $this->getSql($sqlId), $values))->getNbResults();
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<mixed>
     */
    private function perform(string $sqlId, array $values, int $fetchModode): array
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $sqls = $this->getSqls($sqlFile);
        if (count($sqls) === 0) {
            return [];
        }

        $this->logger->start();
        $this->convertDateTime($values);
        foreach ($sqls as $sql) {
            $pdoStatement = $this->pdo->perform($sql, $values);
        }

        assert(isset($pdoStatement)); // @phpstan-ignore-line
        assert($pdoStatement instanceof PDOStatement);
        $lastQuery = trim((string) $pdoStatement->queryString);
        $isSelect = stripos($lastQuery, 'select') === 0;
        $result = $isSelect ? (array) $pdoStatement->fetchAll($fetchModode) : [];
        $this->logger->log($sqlId, $values);

        return $result;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function convertDateTime(array &$values): void
    {
        /** @psalm-suppress MixedAssignment $value */
        foreach ($values as &$value) {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format(self::MYSQL_DATETIME);
            }
        }
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
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}'): Pages
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $pager = $this->pagerFactory->newInstance($this->pdo, $this->getSql($sqlId), $values, $perPage, $queryTemplate);

        return new Pages($pager, $this->pdo, $this->getSql($sqlId), $values);
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
