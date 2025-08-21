<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use Illuminate\Support\Facades\Log;
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
            ->whereHas('customer', function($query) use ($cpf) {
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
     *
     * @param CreditOfferEntity[] $offers
     * @return void
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

    public function markRequestAsFailed(string $errorMessage): void
    {
        Log::error('Request marcado como falho', [
            'error' => $errorMessage,
        ]);
    }

    /**
     * @param CreditOfferEntity[] $offers
     */
    public function markRequestAsCompleted(array $offers): void
    {
        Log::info('Request concluído com sucesso', [
            'offers_count' => count($offers),
        ]);
    }
}
