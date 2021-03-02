<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PDOStatement;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactoryInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\Di\Di\Named;
use Ray\MediaQuery\Exception\InvalidSqlException;

use function array_pop;
use function assert;
use function explode;
use function file;
use function file_exists;
use function file_get_contents;
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
    private $sqlDir;

    /**
     * @var ?PDOStatement
     * @psalm-readonly
     */
    private $pdoStatement;

    /** @var AuraSqlPagerFactoryInterface */
    private $pagerFactory;

    /** @var ParamConverterInterface  */
    private $paramConverter;

    /**
     * @Named("sqlDir=Ray\MediaQuery\Annotation\SqlDir")
     */
    #[Named('sqlDir=Ray\MediaQuery\Annotation\SqlDir')]
    public function __construct(
        ExtendedPdoInterface $pdo,
        string $sqlDir,
        MediaQueryLoggerInterface $logger,
        AuraSqlPagerFactoryInterface $pagerFactory,
        ParamConverterInterface $paramConverter
    ) {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->sqlDir = $sqlDir;
        $this->pagerFactory = $pagerFactory;
        $this->paramConverter = $paramConverter;
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
        $this->logger->start();
        ($this->paramConverter)($values);
        foreach ($sqls as $sql) {
            /** @psalm-suppress InaccessibleProperty */
            $this->pdoStatement = $this->pdo->perform($sql, $values);
        }

        assert($this->pdoStatement instanceof PDOStatement);
        $lastQuery = trim((string) $this->pdoStatement->queryString);
        $isSelect = stripos($lastQuery, 'select') === 0;
        $result = $isSelect ? (array) $this->pdoStatement->fetchAll($fetchModode) : [];
        $this->logger->log($sqlId, $values);

        return $result;
    }

    /**
     * @return array<string>
     */
    private function getSqls(string $sqlFile): array
    {
        if (! file_exists($sqlFile)) {
            throw new InvalidSqlException($sqlFile);
        }

        $sqls = (string) file_get_contents($sqlFile);
        if (! strpos($sqls, ';')) {
            $sqls .= ';';
        }

        $sqls = explode(';', trim($sqls, "\\ \t\n\r\0\x0B"));
        array_pop($sqls);
        if ($sqls[0] === '') {
            throw new InvalidSqlException($sqlFile);
        }

        return $sqls;
    }

    public function getStatement(): PDOStatement
    {
        assert($this->pdoStatement instanceof PDOStatement);

        return $this->pdoStatement;
    }

    /**
     * {@inheritDoc}
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}'): PagesInterface
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $pager = $this->pagerFactory->newInstance($this->pdo, $this->getSql($sqlId), $values, $perPage, $queryTemplate);

        return new Pages($pager, $this->pdo, $this->getSql($sqlId), $values);
    }

    private function getSql(string $sqlId): string
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $file = (array) file($sqlFile);
        $firstRow = (string) $file[0];

        return trim($firstRow, "; \n\r\t\v\0");
    }
}
