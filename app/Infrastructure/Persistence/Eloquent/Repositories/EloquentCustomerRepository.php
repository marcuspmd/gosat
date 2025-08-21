<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findById(string $id): ?CustomerEntity
    {
        $model = CustomerModel::find($id);

        return $model ? CustomerEntity::fromModel($model) : null;
    }

    public function findByCpf(CPF $cpf): ?CustomerEntity
    {
        $model = CustomerModel::byCpf($cpf->value)->first();

        return $model ? CustomerEntity::fromModel($model) : null;
    }

    public function save(CustomerEntity $customer): void
    {
        $model = CustomerModel::find($customer->id);

        if ($model) {
            $customer->updateModel($model);
            $model->save();
        } else {
            $model = $customer->toModel();
            $model->save();
        }
    }
}
