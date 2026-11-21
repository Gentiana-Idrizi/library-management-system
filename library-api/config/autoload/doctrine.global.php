<?php

declare(strict_types=1);

use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PgSQLDriver;

return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => PgSQLDriver::class,
                'params'      => [
                    'host'    => '127.0.0.1',
                    'port'    => 5432,
                    'dbname'  => 'library_db',
                    'charset' => 'utf8',
                ],
            ],
        ],
    ],
];
