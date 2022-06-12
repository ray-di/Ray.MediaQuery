<?php

declare(strict_types=1);

namespace Ray\MediaQuery\DbQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PDOException;
use PDOStatement;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactoryInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\Di\Di\Named;
use Ray\MediaQuery\Exception\InvalidSqlException;
use Ray\MediaQuery\Exception\PdoPerformException;
use Ray\MediaQuery\MediaQueryLoggerInterface;
use Ray\MediaQuery\ParamConverterInterface;

use function array_pop;
use function assert;
use function class_exists;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_object;
use function is_string;
use function json_encode;
use function preg_replace;
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
    private const C_STYLE_COMMENT = '/\/\*(.*?)\*\//u';

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
    public function exec(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC, $fetchArg = ''): void
    {
        $this->perform($sqlId, $values, $fetchMode, $fetchArg);
    }

    /**
     * {@inheritDoc}
     */
    public function getRow(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC, $fetchArg = '')
    {
        $rowList = $this->perform($sqlId, $values, $fetchMode, $fetchArg);
        if (! count($rowList)) {
            return null;
        }

        $item = $rowList[0];
        assert(is_array($item) || is_object($item));

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    public function getRowList(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC, $fetchArg = ''): array
    {
        /** @var array<array<mixed>> $list */
        $list =  $this->perform($sqlId, $values, $fetchMode, $fetchArg);

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
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchModode
     * @param array<string, mixed>                              $values
     * @param callable|int|string                               $fetchArg
     *
     * @return array<mixed>
     */
    private function perform(string $sqlId, array $values, int $fetchModode, $fetchArg = ''): array
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $sqls = $this->getSqls($sqlFile);
        $this->logger->start();
        ($this->paramConverter)($values);
        foreach ($sqls as $sql) {
            /** @psalm-suppress InaccessibleProperty */
            try {
                $this->pdoStatement = $this->pdo->perform($sql, $values);
            } catch (PDOException $e) {
                $msg = sprintf('%s in %s.sql with values %s', $e->getMessage(), $sqlId, json_encode($values));

                throw new PdoPerformException($msg);
            }
        }

        assert($this->pdoStatement instanceof PDOStatement);
        $lastQuery = (string) $this->pdoStatement->queryString;
        $query = trim((string) preg_replace(self::C_STYLE_COMMENT, '', $lastQuery));
        $isSelect = stripos($query, 'select') === 0 || stripos($query, 'with') === 0;
        $result = $isSelect ? $this->fetchAll($fetchModode, $fetchArg) : [];
        $this->logger->log($sqlId, $values);

        return $result;
    }

    /**
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchModode
     * @param callable|int|string                               $fetchArg
     *
     * @return array<mixed>
     */
    private function fetchAll(int $fetchModode, $fetchArg): array
    {
        assert($this->pdoStatement instanceof PDOStatement);
        if ($fetchModode === PDO::FETCH_ASSOC) {
            return $this->pdoStatement->fetchAll($fetchModode);
        }

        if ($fetchModode === PDO::FETCH_CLASS) {
            return $this->pdoStatement->fetchAll($fetchModode, $fetchArg);
        }

        // PDO::FETCH_FUNC
        return $this->pdoStatement->fetchAll(PDO::FETCH_FUNC, /** @param list<mixed> $args */static function (...$args) use ($fetchArg) {
            assert(is_string($fetchArg) && class_exists($fetchArg));

            /** @psalm-suppress MixedMethodCall */
            return new $fetchArg(...$args);
        });
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
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', ?string $entity = null): PagesInterface
    {
        /** @var array<array<array-key, int|string>|int|string> $values */
        $pager = $this->pagerFactory->newInstance($this->pdo, $this->getSql($sqlId), $values, $perPage, $queryTemplate, $entity);

        /** @var array<string, mixed> $values */
        return new Pages($pager, $this->pdo, $this->getSql($sqlId), $values);
    }

    private function getSql(string $sqlId): string
    {
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $sqlId);
        $sql = (string) file_get_contents($sqlFile);

        return trim($sql, "; \n\r\t\v\0");
    }
}
