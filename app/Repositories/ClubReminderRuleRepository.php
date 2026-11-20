<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ClubReminderRuleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        try {
            return $this->pdo->query(
                'SELECT * FROM club_reminder_rules ORDER BY sort_order ASC, id ASC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        try {
            return $this->pdo->query(
                'SELECT * FROM club_reminder_rules WHERE enabled = 1 ORDER BY sort_order ASC, id ASC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM club_reminder_rules WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO club_reminder_rules
                (kind, label, enabled, direction, offset_days, time_of_day, template, conditions, sort_order, created_at, updated_at)
             VALUES
                (:kind, :label, :enabled, :direction, :offset_days, :time_of_day, :template, :conditions, :sort_order, :created_at, :updated_at)'
        );
        $now = \current_datetime();
        $stmt->execute([
            'kind' => $data['kind'],
            'label' => $data['label'],
            'enabled' => $data['enabled'],
            'direction' => $data['direction'],
            'offset_days' => $data['offset_days'],
            'time_of_day' => $data['time_of_day'],
            'template' => $data['template'],
            'conditions' => $data['conditions'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE club_reminder_rules SET
                label = :label,
                enabled = :enabled,
                direction = :direction,
                offset_days = :offset_days,
                time_of_day = :time_of_day,
                template = :template,
                conditions = :conditions,
                sort_order = :sort_order,
                updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'label' => $data['label'],
            'enabled' => $data['enabled'],
            'direction' => $data['direction'],
            'offset_days' => $data['offset_days'],
            'time_of_day' => $data['time_of_day'],
            'template' => $data['template'],
            'conditions' => $data['conditions'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'updated_at' => \current_datetime(),
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM club_reminder_rules WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function toggle(int $id, bool $enabled): void
    {
        $stmt = $this->pdo->prepare('UPDATE club_reminder_rules SET enabled = :enabled, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['enabled' => $enabled ? 1 : 0, 'updated_at' => \current_datetime(), 'id' => $id]);
    }
}
