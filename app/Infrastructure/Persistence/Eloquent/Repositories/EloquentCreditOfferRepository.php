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
                $this->save($offer);
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

    public function getAllCustomersWithOffers(): array
    {
        $customers = DB::table('customers')
            ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
            ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
            ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
            ->select(
                'customers.cpf',
                'institutions.name as institution_name',
                'credit_modalities.name as modality_name',
                DB::raw('MAX(credit_offers.max_amount_cents) as max_amount_cents'),
                DB::raw('MIN(credit_offers.min_amount_cents) as min_amount_cents'),
                DB::raw('MAX(credit_offers.max_installments) as max_installments'),
                DB::raw('MIN(credit_offers.min_installments) as min_installments'),
                DB::raw('MIN(credit_offers.monthly_interest_rate) as monthly_interest_rate'),
                DB::raw('MAX(credit_offers.created_at) as created_at')
            )
            ->where('customers.is_active', true)
            ->whereNull('credit_offers.deleted_at')
            ->groupBy('customers.cpf', 'institutions.name', 'credit_modalities.name')
            ->orderBy('customers.cpf')
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedCustomers = $customers->groupBy('cpf')->map(function ($offers, $cpf) {
            $minAmount = $offers->min('min_amount_cents');
            $maxAmount = $offers->max('max_amount_cents');
            $minInstallments = $offers->min('min_installments');
            $maxInstallments = $offers->max('max_installments');

            return [
                'cpf' => $cpf,
                'offers_count' => $offers->count(),
                'offers' => $offers->toArray(),
                'available_ranges' => [
                    'min_amount_cents' => $minAmount,
                    'max_amount_cents' => $maxAmount,
                    'min_installments' => $minInstallments,
                    'max_installments' => $maxInstallments,
                ],
            ];
        })->values();

        return $groupedCustomers->toArray();
    }

    public function getOffersForCpf(CPF $cpf, int $limit = 10): array
    {
        $offers = DB::table('customers')
            ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
            ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
            ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
            ->select(
                'institutions.name as institution_name',
                'credit_modalities.name as modality_name',
                'credit_offers.max_amount_cents',
                'credit_offers.min_amount_cents',
                'credit_offers.max_installments',
                'credit_offers.min_installments',
                'credit_offers.monthly_interest_rate',
                'credit_offers.created_at'
            )
            ->where('customers.cpf', $cpf->value)
            ->where('customers.is_active', true)
            ->whereNull('credit_offers.deleted_at')
            ->orderBy('credit_offers.created_at', 'desc')
            ->limit($limit)
            ->get();

        return $offers->toArray();
    }

    public function getSimulationOffers(CPF $cpf, int $amountCents, int $installments, ?string $modality = null): array
    {
        $query = DB::table('customers')
            ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
            ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
            ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
            ->select(
                'institutions.name as financial_institution',
                'credit_modalities.name as credit_modality',
                DB::raw('MAX(credit_offers.max_amount_cents) as max_amount_cents'),
                DB::raw('MIN(credit_offers.min_amount_cents) as min_amount_cents'),
                DB::raw('MAX(credit_offers.max_installments) as max_installments'),
                DB::raw('MIN(credit_offers.min_installments) as min_installments'),
                DB::raw('MIN(credit_offers.monthly_interest_rate) as monthly_interest_rate')
            )
            ->where('customers.cpf', $cpf->value)
            ->where('customers.is_active', true)
            ->whereNull('credit_offers.deleted_at')
            ->where('credit_offers.min_amount_cents', '<=', $amountCents)
            ->where('credit_offers.max_amount_cents', '>=', $amountCents)
            ->where('credit_offers.min_installments', '<=', $installments)
            ->where('credit_offers.max_installments', '>=', $installments);

        if ($modality) {
            $query->where('credit_modalities.name', $modality);
        }

        $offers = $query->groupBy('institutions.name', 'credit_modalities.name')
            ->get();

        return $offers->toArray();
    }

    public function countOffersByRequestId(string $requestId): int
    {
        return DB::table('credit_offers')
            ->where('request_id', $requestId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function findPendingJobByRequestId(string $requestId): ?object
    {
        return DB::table('jobs')
            ->where('payload', 'like', '%"creditRequestId":"' . $requestId . '"%')
            ->first();
    }

    public function findFailedJobByRequestId(string $requestId): ?object
    {
        return DB::table('failed_jobs')
            ->where('payload', 'like', '%"creditRequestId":"' . $requestId . '"%')
            ->first();
    }
}
