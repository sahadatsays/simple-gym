<?php

namespace App\Services;

use App\Contracts\Repositories\MembershipPlanRepositoryInterface;
use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use InvalidArgumentException;

class MembershipPlanService extends BaseService
{
    public function __construct(
        private MembershipPlanRepositoryInterface $plans,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MembershipPlan
    {
        return $this->transaction(function () use ($data): MembershipPlan {
            $plan = $this->plans->create($data);

            $this->activityLogger->log('membership_plan.created', $plan, 'Membership plan created', [
                'name' => $plan->name,
            ]);

            return $plan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MembershipPlan $plan, array $data): MembershipPlan
    {
        return $this->transaction(function () use ($plan, $data): MembershipPlan {
            $updatedPlan = $this->plans->update($plan, $data);

            $this->activityLogger->log('membership_plan.updated', $updatedPlan, 'Membership plan updated');

            return $updatedPlan;
        });
    }

    public function delete(MembershipPlan $plan): void
    {
        if ($this->plans->hasMembers($plan)) {
            throw new InvalidArgumentException('This plan cannot be deleted because it is assigned to members.');
        }

        $this->transaction(function () use ($plan): void {
            $this->activityLogger->log('membership_plan.deleted', $plan, 'Membership plan deleted', [
                'name' => $plan->name,
            ]);

            $this->plans->delete($plan);
        });
    }

    /**
     * @param  list<string>|null  $features
     * @return list<string>
     */
    public function normalizeFeatures(?array $features): array
    {
        if ($features === null) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $feature): string => trim($feature),
            $features
        )));
    }
}
