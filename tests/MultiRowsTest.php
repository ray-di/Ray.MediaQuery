<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;

use function file_get_contents;

class MultiRowsTest extends TestCase
{

    private SqlQuery $sqlQuery;

    private MediaQueryLogger $log;

    private ExtendedPdo $pdo;

    private array $userInsertData = [
        'userId' => 'user_1',
        'userName' => 'User1',
    ];

    private array $permissionInsertData = [
        [
            'permissionId' => 'permission_1',
            'permissionName' => 'Permission1',
        ],
        [
            'permissionId' => 'permission_2',
            'permissionName' => 'Permission2',
        ],
        [
            'permissionId' => 'permission_3',
            'permissionName' => 'Permission3',
        ],
    ];

    private array $userPermissionInsertData = [
        [
            'userId' => 'user_1',
            'permissionId' => 'permission_1',
        ],
        [
            'userId' => 'user_1',
            'permissionId' => 'permission_2',
        ],
        [
            'userId' => 'user_1',
            'permissionId' => 'permission_3',
        ],

    ];

    protected function setUp(): void
    {
        $sqlDir = __DIR__ . '/sql';
        $pdo = new ExtendedPdo('sqlite::memory', '', '', [PDO::ATTR_STRINGIFY_FETCHES => true]);
        $pdo->query((string) file_get_contents($sqlDir . '/create_user.sql'));
        $pdo->query((string) file_get_contents($sqlDir . '/create_permission.sql'));
        $pdo->query((string) file_get_contents($sqlDir . '/create_user_permission.sql'));
        $pdo->perform(
            (string) file_get_contents($sqlDir . '/user_add.sql'),
            $this->userInsertData
        );

        foreach($this->permissionInsertData as $insertData) {
            $pdo->perform(
                (string) file_get_contents($sqlDir . '/permission_add.sql'),
                $insertData,
            );
        }
        foreach($this->userPermissionInsertData as $insertData) {
            $pdo->perform(
                (string) file_get_contents($sqlDir . '/user_permission_add.sql'),
                $insertData,
            );
        }

        $this->log = new MediaQueryLogger();
        $this->sqlQuery = new SqlQuery(
            $pdo,
            __DIR__ . '/sql',
            $this->log,
            new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), [])),
            new ParamConverter(),
        );
        $this->pdo = $pdo;
    }

    public function testMultiRow(): void
    {
    }
}
