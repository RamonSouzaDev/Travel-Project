<?php

namespace App\Repositories;

use App\Models\TravelRequest;
use App\Repositories\Interfaces\TravelRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TravelRequestRepository implements TravelRequestRepositoryInterface
{
    /**
     * Obtém todos os pedidos de viagem com filtros aplicados
     *
     * @param array $filters
     * @param int|null $userId
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = [], ?int $userId = null): LengthAwarePaginator
    {
        $query = $userId ? TravelRequest::where('user_id', $userId) : TravelRequest::query();
        
        // Filtrar por status
        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }
        
        // Filtrar por destino
        if (isset($filters['destination'])) {
            $query->destination($filters['destination']);
        }
        
        // Filtrar por período
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }
        
        return $query->latest()->paginate(15);
    }
    
    /**
     * Cria um novo pedido de viagem
     *
     * @param array $data
     * @return TravelRequest
     */
    public function create(array $data): TravelRequest
    {
        return TravelRequest::create($data);
    }
    
    /**
     * Encontra um pedido de viagem pelo ID
     *
     * @param string $id
     * @return TravelRequest|null
     */
    public function findById(string $id): ?TravelRequest
    {
        return TravelRequest::find($id);
    }
    
    /**
     * Atualiza o status de um pedido de viagem
     *
     * @param TravelRequest $travelRequest
     * @param string $status
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     */
    public function updateStatus(TravelRequest $travelRequest, string $status, ?string $reasonForCancellation = null): TravelRequest
    {
        $travelRequest->updateStatus($status, $reasonForCancellation);
        return $travelRequest;
    }
}