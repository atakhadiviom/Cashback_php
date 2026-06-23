<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CrmReportRepository;
use App\Repositories\UserRepository;
use App\Repositories\TierRepository;

final class CrmReportService
{
    private CrmReportRepository $reports;
    private UserRepository $users;
    private TierRepository $tiers;

    public function __construct()
    {
        $this->reports = new CrmReportRepository();
        $this->users = new UserRepository();
        $this->tiers = new TierRepository();
    }

    public function getData(array $filters): array
    {
        return [
            'summary' => $this->reports->summary($filters),
            'followups' => $this->reports->followups($filters),
            'byOperator' => $this->reports->byOperator($filters),
            'byStatus' => $this->reports->byStatus($filters),
            'operators' => $this->users->activeOperatorsAndAdmins(),
            'tiers' => $this->tiers->all(),
        ];
    }
}
