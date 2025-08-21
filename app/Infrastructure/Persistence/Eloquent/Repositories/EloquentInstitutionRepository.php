<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;

final class EloquentInstitutionRepository implements InstitutionRepositoryInterface
{
    public function findById(string $id): ?InstitutionEntity
    {
        $model = InstitutionModel::find($id);
        
        return $model ? InstitutionEntity::fromModel($model) : null;
    }

    public function findBySlug(string $slug): ?InstitutionEntity
    {
        $model = InstitutionModel::where('slug', $slug)->first();
        
        return $model ? InstitutionEntity::fromModel($model) : null;
    }

    public function save(InstitutionEntity $institution): void
    {
        $model = InstitutionModel::find($institution->id);
        
        if ($model) {
            $institution->updateModel($model);
            $model->save();
        } else {
            $model = $institution->toModel();
            $model->save();
        }
    }

    public function delete(string $id): void
    {
        InstitutionModel::destroy($id);
    }
}
