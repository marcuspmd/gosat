<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use Illuminate\Support\Facades\DB;

final class EloquentCreditOfferRepository implements CreditOfferRepositoryInterface
{
    /**
     * @return CreditOfferEntity[]|null
     */
    public function findById(string $id): ?array
    {
        $model = CreditOfferModel::with(['customer', 'institution', 'modality'])->find($id);

        return $model ? [CreditOfferEntity::fromModel($model)] : null;
    }

    /**
     * @return CreditOfferEntity[]
     */
    public function findByCpf(CPF $cpf): array
    {
        $models = CreditOfferModel::with(['customer', 'institution', 'modality'])
            ->whereHas('customer', function ($query) use ($cpf) {
                $query->where('cpf', $cpf->value);
            })
            ->get();

        return $models->map(fn ($model) => CreditOfferEntity::fromModel($model))->toArray();
    }

    public function save(CreditOfferEntity $offer): void
    {
        DB::transaction(function () use ($offer) {
            $model = CreditOfferModel::find($offer->id);

            if ($model) {
                $offer->updateModel($model);
                $model->save();
            } else {
                $model = $offer->toModel();
                $model->save();
            }
        });
    }

    /**
     * @param  CreditOfferEntity[]  $offers
     */
    public function saveAll(array $offers): void
    {
        DB::transaction(function () use ($offers) {
            foreach ($offers as $offer) {
                if ($offer instanceof CreditOfferEntity) {
                    $this->save($offer);
                }
            }
        });
    }

    public function delete(string $id): void
    {
        CreditOfferModel::destroy($id);
    }

    public function findByRequestId(string $requestId): array
    {
        // For now, return all offers - in real implementation, you'd store requestId in offers table
        $models = CreditOfferModel::with(['customer', 'institution', 'modality'])->get();

        return $models->map(fn ($model) => CreditOfferEntity::fromModel($model))->toArray();
    }

    public function softDeleteByCpf(CPF $cpf): void
    {
        DB::transaction(function () use ($cpf) {
            CreditOfferModel::whereHas('customer', function ($query) use ($cpf) {
                $query->where('cpf', $cpf->value);
            })->delete(); // This will soft delete due to SoftDeletes trait
        });
    }
}
