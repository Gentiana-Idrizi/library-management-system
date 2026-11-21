<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\SystemSetting;
use Library\Entity\User;
use Library\Validator\Input;

class SettingsService
{
    public const BORROW_FEE          = 'borrow_fee';
    public const PENALTY_RATE        = 'penalty_rate';
    public const BORROW_PERIOD_DAYS  = 'borrow_period_days';
    public const BORROW_LIMIT        = 'borrow_limit';
    public const LOW_STOCK_THRESHOLD = 'low_stock_threshold';

    public const DEFAULTS = [
        self::BORROW_FEE          => '2.00',
        self::PENALTY_RATE        => '1.00',
        self::BORROW_PERIOD_DAYS  => '14',
        self::BORROW_LIMIT        => '3',
        self::LOW_STOCK_THRESHOLD => '2',
    ];

    private const API_FIELDS = [
        'borrowFee'         => self::BORROW_FEE,
        'penaltyRate'       => self::PENALTY_RATE,
        'borrowPeriodDays'  => self::BORROW_PERIOD_DAYS,
        'borrowLimit'       => self::BORROW_LIMIT,
        'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
    ];

    private ?array $cache = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly AuditLogger $audit
    ) {
    }

    public function borrowFeeCents(): int
    {
        return Money::toCents($this->get(self::BORROW_FEE));
    }

    public function penaltyRateCents(): int
    {
        return Money::toCents($this->get(self::PENALTY_RATE));
    }

    public function borrowPeriodDays(): int
    {
        return (int) $this->get(self::BORROW_PERIOD_DAYS);
    }

    public function borrowLimit(): int
    {
        return (int) $this->get(self::BORROW_LIMIT);
    }

    public function lowStockThreshold(): int
    {
        return (int) $this->get(self::LOW_STOCK_THRESHOLD);
    }

    public function toArray(): array
    {
        return [
            'borrowFee'         => Money::fromCents($this->borrowFeeCents()),
            'penaltyRate'       => Money::fromCents($this->penaltyRateCents()),
            'borrowPeriodDays'  => $this->borrowPeriodDays(),
            'borrowLimit'       => $this->borrowLimit(),
            'lowStockThreshold' => $this->lowStockThreshold(),
            'currency'          => 'EUR',
        ];
    }

    public function update(User $actor, array $data): array
    {
        $input  = new Input($data, partial: true);
        $values = array_filter([
            self::BORROW_FEE          => $input->money('borrowFee', required: false),
            self::PENALTY_RATE        => $input->money('penaltyRate', required: false),
            self::BORROW_PERIOD_DAYS  => $input->int('borrowPeriodDays', 1, 365, required: false),
            self::BORROW_LIMIT        => $input->int('borrowLimit', 1, 50, required: false),
            self::LOW_STOCK_THRESHOLD => $input->int('lowStockThreshold', 0, 1000, required: false),
        ], static fn($v) => $v !== null);

        if ($values === [] && array_intersect(array_keys($data), array_keys(self::API_FIELDS)) === []) {
            $input->addError('settings', 'Provide at least one of: ' . implode(', ', array_keys(self::API_FIELDS)) . '.');
        }
        $input->assertValid();

        $changes = [];
        foreach ($values as $key => $value) {
            $value = (string) $value;
            $old   = $this->get($key);
            if ($old === $value) {
                continue;
            }
            $setting = $this->em->find(SystemSetting::class, $key);
            if ($setting === null) {
                $this->em->persist(new SystemSetting($key, $value, $this->clock->now()));
            } else {
                $setting->setValue($value, $this->clock->now());
            }
            $changes[$key] = ['old' => $old, 'new' => $value];
        }

        $this->cache = null;
        if ($changes !== []) {
            $this->audit->log($actor, AuditLogger::SETTINGS_UPDATED, 'system_setting', null, ['changes' => $changes]);
            $this->em->flush();
        }

        return $changes;
    }

    public function ensureDefaults(): array
    {
        $created = [];
        foreach (self::DEFAULTS as $key => $value) {
            if ($this->em->find(SystemSetting::class, $key) === null) {
                $this->em->persist(new SystemSetting($key, $value, $this->clock->now()));
                $created[] = $key;
            }
        }
        $this->em->flush();
        $this->cache = null;

        return $created;
    }

    private function get(string $key): string
    {
        if ($this->cache === null) {
            $this->cache = [];

            foreach ($this->em->getRepository(SystemSetting::class)->findAll() as $setting) {
                $this->cache[$setting->getKey()] = $setting->getValue();
            }
        }

        return $this->cache[$key] ?? self::DEFAULTS[$key];
    }
}
