<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CashbackSettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function settings(): array
    {
        $row = $this->pdo->query('SELECT * FROM cashback_settings WHERE id = 1')->fetch();
        if (!$row) {
            return [];
        }

        // Safely read enabled_menus even if the column doesn't exist yet (pre-migration 017)
        $enabledRaw = null;
        if (array_key_exists('enabled_menus', $row)) {
            $enabledRaw = $row['enabled_menus'];
        } else {
            // Column missing → try a safe separate query
            try {
                $stmt = $this->pdo->prepare('SELECT enabled_menus FROM cashback_settings WHERE id = 1');
                $stmt->execute();
                $enabledRaw = $stmt->fetchColumn();
            } catch (\Throwable $e) {
                $enabledRaw = null;
            }
        }

        if (!empty($enabledRaw)) {
            $decoded = json_decode((string)$enabledRaw, true);
            $row['enabled_menus'] = is_array($decoded) ? $decoded : null;
        } else {
            $row['enabled_menus'] = null; // null = show all menus
        }

        return $row;
    }

    public function update(array $data): void
    {
        // Note: enabled_menus is handled separately in the controller for safety
        // (in case migration 017 has not been run yet on existing databases).
        $stmt = $this->pdo->prepare(
            'UPDATE cashback_settings SET
                cashback_percent = :cashback_percent,
                min_purchase_amount = :min_purchase_amount,
                max_cashback_per_purchase = :max_cashback_per_purchase,
                min_redemption_amount = :min_redemption_amount,
                max_redemption_percent_of_purchase = :max_redemption_percent_of_purchase,
                large_redemption_threshold = :large_redemption_threshold,
                birthday_bonus_amount = :birthday_bonus_amount,
                referral_bonus_amount = :referral_bonus_amount,
                duplicate_purchase_window_minutes = :duplicate_purchase_window_minutes,
                updated_at = :updated_at
            WHERE id = 1'
        );
        $stmt->execute($data);
    }

    /**
     * Update the cashback expiry settings separately, so saving the rest of the form still
     * works on databases where migration 019 has not been run yet.
     */
    public function updateExpirySettings(int $months, int $warningDays): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE cashback_settings SET
                    cashback_expiry_months = :months,
                    cashback_expiry_warning_days = :warning_days,
                    updated_at = :updated_at
                WHERE id = 1'
            );
            $stmt->execute([
                'months' => $months,
                'warning_days' => $warningDays,
                'updated_at' => \current_datetime(),
            ]);

            return true;
        } catch (\Throwable) {
            // Columns do not exist yet (migration 019 not run).
            return false;
        }
    }

    /**
     * Safely update only the enabled_menus column.
     * Returns true if the update was applied, false if the column doesn't exist yet or on error.
     */
    public function updateEnabledMenus(?string $json): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE cashback_settings SET enabled_menus = :enabled_menus, updated_at = :updated_at WHERE id = 1'
            );
            $stmt->execute([
                'enabled_menus' => $json,
                'updated_at' => \current_datetime(),
            ]);
            return true;
        } catch (\Throwable $e) {
            // Column does not exist yet (migration 017 not run) or other DB error.
            return false;
        }
    }

    /**
     * Defaults for the customer club settings (column club_options, migration 023).
     *
     * @return array<string, mixed>
     */
    public static function clubDefaults(): array
    {
        return [
            'enabled' => true,
            'tier_basis' => 'lifetime',
            'tier_window_months' => '12',
            'tier_downgrade_grace_days' => '30',
            'tier_change_sms' => true,
            'tier_change_template' => '{full_name} عزیز، سطح باشگاه شما به {tier_name} تغییر کرد.',
            'rfm' => [
                'r' => [15, 45, 90, 180],
                'f' => [1, 2, 4, 8],
                'm' => [1000000, 5000000, 20000000, 50000000],
                'refresh_hours' => '24',
            ],
            'campaign_daily_limit' => '200',
            'campaign_min_interval_days' => '7',
        ];
    }

    /**
     * Club settings merged over the defaults; works without the migration.
     *
     * @return array<string, mixed>
     */
    public function clubOptions(): array
    {
        $defaults = self::clubDefaults();
        try {
            $stmt = $this->pdo->prepare('SELECT club_enabled, club_options FROM cashback_settings WHERE id = 1');
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return $defaults;
        }
        if (!is_array($row)) {
            return $defaults;
        }

        $options = $defaults;
        if (array_key_exists('club_enabled', $row) && $row['club_enabled'] !== null) {
            $options['enabled'] = (int) $row['club_enabled'] === 1;
        }
        $raw = $row['club_options'] ?? null;
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $options = self::mergeClubOptions($options, $decoded);
            }
        }

        return $options;
    }

    public function clubEnabled(): bool
    {
        return !empty($this->clubOptions()['enabled']);
    }

    /** @param array<string, mixed> $options */
    public function updateClubOptions(array $options): bool
    {
        $payload = self::mergeClubOptions(self::clubDefaults(), $options);
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE cashback_settings SET club_enabled = :club_enabled, club_options = :club_options, updated_at = :updated_at WHERE id = 1'
            );
            $stmt->execute([
                'club_enabled' => !empty($payload['enabled']) ? 1 : 0,
                'club_options' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'updated_at' => \current_datetime(),
            ]);

            return true;
        } catch (\Throwable) {
            // Columns do not exist yet (migration 023 not run).
            return false;
        }
    }

    /**
     * Merge raw club options over the given defaults and sanitise every value.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function mergeClubOptions(array $defaults, array $raw): array
    {
        $options = $defaults;
        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            if (is_bool($default)) {
                $options[$key] = (bool) $raw[$key];
            } elseif ($key === 'rfm' && is_array($raw[$key])) {
                $rfm = $default;
                $rfm['r'] = self::cleanRfmBand($raw[$key]['r'] ?? null, $default['r']);
                $rfm['f'] = self::cleanRfmBand($raw[$key]['f'] ?? null, $default['f']);
                $rfm['m'] = self::cleanRfmBand($raw[$key]['m'] ?? null, $default['m']);
                if (isset($raw[$key]['refresh_hours'])) {
                    $rfm['refresh_hours'] = (string) max(1, min(168, (int) $raw[$key]['refresh_hours']));
                }
                $options[$key] = $rfm;
            } else {
                $options[$key] = (string) $raw[$key];
            }
        }

        if (!in_array($options['tier_basis'], ['lifetime', 'rolling'], true)) {
            $options['tier_basis'] = 'lifetime';
        }
        $options['tier_window_months'] = (string) max(1, min(120, (int) $options['tier_window_months']));
        $options['tier_downgrade_grace_days'] = (string) max(0, min(365, (int) $options['tier_downgrade_grace_days']));
        $options['campaign_daily_limit'] = (string) max(0, min(5000, (int) $options['campaign_daily_limit']));
        $options['campaign_min_interval_days'] = (string) max(0, min(365, (int) $options['campaign_min_interval_days']));
        $options['tier_change_template'] = mb_substr(trim((string) $options['tier_change_template']), 0, 500);

        return $options;
    }

    /**
     * @param mixed $value
     * @param list<int|float> $default
     * @return list<float>
     */
    private static function cleanRfmBand(mixed $value, array $default): array
    {
        if (!is_array($value)) {
            return array_map(static fn ($v): float => (float) $v, $default);
        }
        $numbers = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $numbers[] = (float) $item;
            }
        }
        if (count($numbers) !== 4) {
            return array_map(static fn ($v): float => (float) $v, $default);
        }
        sort($numbers);

        return $numbers;
    }
}
