<?php

declare(strict_types=1);

namespace Library;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

$id = ['id' => '[1-9][0-9]*'];

$route = static function (string $path, string $controller, array $defaults = [], array $constraints = []): array {
    return [
        'type'    => str_contains($path, ':') || str_contains($path, '[') ? Segment::class : Literal::class,
        'options' => [
            'route'       => $path,
            'constraints' => $constraints,
            'defaults'    => ['controller' => $controller] + $defaults,
        ],
    ];
};

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => ['controller' => Controller\HealthController::class],
                ],
            ],
            'api' => [
                'type'          => Literal::class,
                'options'       => ['route' => '/api'],
                'may_terminate' => false,
                'child_routes'  => [
                    'health'        => $route('/health', Controller\HealthController::class, ['public' => true]),

                    'login'         => $route('/auth/login', Controller\AuthController::class, ['action' => 'login', 'public' => true]),
                    'me'            => $route('/auth/me', Controller\AuthController::class, ['action' => 'me']),

                    'books'         => $route('/books[/:id]', Controller\BookController::class, [], $id),
                    'categories'    => $route('/books/categories', Controller\BookController::class, ['action' => 'categories']),

                    'loans'         => $route('/loans[/:id]', Controller\LoanController::class, [], $id),
                    'loan-return'   => $route('/loans/:id/return', Controller\LoanController::class, ['action' => 'return'], $id),

                    'penalties'     => $route('/penalties[/:id]', Controller\PenaltyController::class, [], $id),
                    'penalty-pay'   => $route('/penalties/:id/pay', Controller\PenaltyController::class, ['action' => 'pay'], $id),

                    'users'         => $route('/users[/:id]', Controller\UserController::class, [], $id),
                    'user-history'  => $route('/users/:id/history', Controller\UserController::class, ['action' => 'history'], $id),

                    'settings'      => $route('/settings', Controller\SettingsController::class),
                    'logs'          => $route('/logs', Controller\LogController::class),

                    'notifications'      => $route('/notifications', Controller\NotificationController::class),
                    'notification-read'  => $route('/notifications/:id/read', Controller\NotificationController::class, ['action' => 'read'], $id),
                    'notifications-read' => $route('/notifications/read-all', Controller\NotificationController::class, ['action' => 'read-all']),
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\HealthController::class       => ReflectionBasedAbstractFactory::class,
            Controller\AuthController::class         => ReflectionBasedAbstractFactory::class,
            Controller\BookController::class         => ReflectionBasedAbstractFactory::class,
            Controller\LoanController::class         => ReflectionBasedAbstractFactory::class,
            Controller\PenaltyController::class      => ReflectionBasedAbstractFactory::class,
            Controller\UserController::class         => ReflectionBasedAbstractFactory::class,
            Controller\SettingsController::class     => ReflectionBasedAbstractFactory::class,
            Controller\LogController::class          => ReflectionBasedAbstractFactory::class,
            Controller\NotificationController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],

    'service_manager' => [
        'aliases' => [
            EntityManagerInterface::class   => 'doctrine.entitymanager.orm_default',
            Service\ClockInterface::class   => Service\SystemClock::class,
        ],
        'factories' => [
            Command\SeedCommand::class          => ReflectionBasedAbstractFactory::class,

            Listener\AuthListener::class        => ReflectionBasedAbstractFactory::class,
            Listener\CorsListener::class        => Factory\CorsListenerFactory::class,
            Listener\ErrorListener::class       => Factory\ErrorListenerFactory::class,

            Service\AuditLogger::class          => ReflectionBasedAbstractFactory::class,
            Service\AuthContext::class          => InvokableFactory::class,
            Service\AuthService::class          => ReflectionBasedAbstractFactory::class,
            Service\BookService::class          => ReflectionBasedAbstractFactory::class,
            Service\BorrowPolicy::class         => InvokableFactory::class,
            Service\JwtService::class           => Factory\JwtServiceFactory::class,
            Service\LoanService::class          => ReflectionBasedAbstractFactory::class,
            Service\NotificationService::class  => ReflectionBasedAbstractFactory::class,
            Service\PasswordHasher::class       => InvokableFactory::class,
            Service\PenaltyCalculator::class    => InvokableFactory::class,
            Service\PenaltyService::class       => ReflectionBasedAbstractFactory::class,
            Service\Presenter::class            => ReflectionBasedAbstractFactory::class,
            Service\SettingsService::class      => ReflectionBasedAbstractFactory::class,
            Service\SystemClock::class          => Factory\SystemClockFactory::class,
            Service\UserService::class          => ReflectionBasedAbstractFactory::class,
        ],
    ],

    'view_manager' => [
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
    ],

    'laminas-cli' => [
        'commands' => [
            'library:seed' => Command\SeedCommand::class,
        ],
    ],

    'doctrine' => [
        'driver' => [
            'library_entities' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AttributeDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Library\Entity' => 'library_entities',
                ],
            ],
        ],
        'migrations_configuration' => [
            'orm_default' => [
                'table_storage'    => ['table_name' => 'doctrine_migration_versions'],
                'migrations_paths' => ['Migrations' => 'data/Migrations'],
                'all_or_nothing'   => true,
            ],
        ],
    ],

    'library' => [
        'timezone' => 'Europe/Belgrade',
        'jwt'      => [
            'issuer' => 'library-api',
            'ttl'    => 8 * 3600,
        ],
        'cors' => [
            'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
        ],
    ],
];
