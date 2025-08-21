<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\CreditRequestEntity;
use App\Domain\Credit\Repositories\CreditRequestRepositoryInterface;
use App\Domain\Customer\Entities\CustomerEntity;
use App\Infrastructure\Persistence\Eloquent\Models\CreditRequestModel;

final class EloquentCreditRequestRepository implements CreditRequestRepositoryInterface
{
    public function findById(string $id): ?CreditRequestEntity
    {
        $model = CreditRequestModel::with('customer')->find($id);

        return $model ? CreditRequestEntity::fromModel($model) : null;
    }

    /**
     * @return CreditRequestEntity[]
     */
    public function findByCustomer(CustomerEntity $customer): array
    {
        $models = CreditRequestModel::with('customer')
            ->byCustomer($customer->id)
            ->get();

        return $models->map(fn ($model) => CreditRequestEntity::fromModel($model))->toArray();
    }

    /**
     * @return CreditRequestEntity[]
     */
    public function findValidRequests(): array
    {
        $models = CreditRequestModel::with('customer')
            ->valid()
            ->get();

        return $models->map(fn ($model) => CreditRequestEntity::fromModel($model))->toArray();
    }

    /**
     * @return CreditRequestEntity[]
     */
    public function findExpiredRequests(): array
    {
        $models = CreditRequestModel::with('customer')
            ->expired()
            ->get();

        return $models->map(fn ($model) => CreditRequestEntity::fromModel($model))->toArray();
    }

    public function save(CreditRequestEntity $request): void
    {
        $model = CreditRequestModel::find($request->id);

        if ($model) {
            $request->updateModel($model);
            $model->save();
        } else {
            $model = $request->toModel();
            $model->save();
        }
    }

    public function delete(string $id): void
    {
        CreditRequestModel::destroy($id);
    }
}
