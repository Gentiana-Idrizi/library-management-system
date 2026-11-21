<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119130600 extends AbstractMigration
{
    private const DEFAULTS = [
        'borrow_fee'          => '2.00',
        'penalty_rate'        => '1.00',
        'borrow_period_days'  => '14',
        'borrow_limit'        => '3',
        'low_stock_threshold' => '2',
    ];

    public function getDescription(): string
    {
        return 'Insert default system settings';
    }

    public function up(Schema $schema): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            $this->addSql(
                'INSERT INTO system_settings (setting_key, value, updated_at) VALUES (:key, :value, NOW()) ON CONFLICT (setting_key) DO NOTHING',
                ['key' => $key, 'value' => $value]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM system_settings WHERE setting_key IN (:keys)', ['keys' => array_keys(self::DEFAULTS)], ['keys' => \Doctrine\DBAL\ArrayParameterType::STRING]);
    }
}
