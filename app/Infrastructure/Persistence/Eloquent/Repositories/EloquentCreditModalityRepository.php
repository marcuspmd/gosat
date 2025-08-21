<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;

final class EloquentCreditModalityRepository implements CreditModalityRepositoryInterface
{
    public function findById(string $id): ?CreditModalityEntity
    {
        $model = CreditModalityModel::find($id);

        return $model ? CreditModalityEntity::fromModel($model) : null;
    }

    public function findBySlug(string $slug): ?CreditModalityEntity
    {
        $model = CreditModalityModel::byStandardCode($slug)->first();

        return $model ? CreditModalityEntity::fromModel($model) : null;
    }

    public function save(CreditModalityEntity $modality): void
    {
        $model = CreditModalityModel::find($modality->id);

        if ($model) {
            $modality->updateModel($model);
            $model->save();
        } else {
            $model = $modality->toModel();
            $model->save();
        }
    }

    public function delete(string $id): void
    {
        CreditModalityModel::destroy($id);
    }
}
