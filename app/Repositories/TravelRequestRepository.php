<?php

namespace App\Repositories;

use App\Models\TravelRequest;
use App\Repositories\Interfaces\TravelRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            $query->where('status', $filters['status']);
        }
        
        // Filtrar por destino
        if (isset($filters['destination'])) {
            $query->where('destination', 'like', "%{$filters['destination']}%");
        }
        
        // Filtrar por período
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereDate('departure_date', '>=', $filters['start_date'])
                  ->whereDate('return_date', '<=', $filters['end_date']);
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
        $travelRequest->status = $status;
        
        if ($status === 'cancelado' && $reasonForCancellation) {
            $travelRequest->reason_for_cancellation = $reasonForCancellation;
        }
        
        $travelRequest->save();
        return $travelRequest;
    }
    
    /**
     * Obtém todos os pedidos de viagem de um usuário específico
     *
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getAllByUser(int $userId): LengthAwarePaginator
    {
        return TravelRequest::where('user_id', $userId)
                            ->latest()
                            ->paginate(15);
    }
    
    /**
     * Obtém todos os pedidos de viagem com um status específico
     *
     * @param string $status
     * @return LengthAwarePaginator
     */
    public function getAllByStatus(string $status): LengthAwarePaginator
    {
        return TravelRequest::where('status', $status)
                            ->latest()
                            ->paginate(15);
    }
    
    /**
     * Obtém todos os pedidos de viagem para um destino específico
     *
     * @param string $destination
     * @return LengthAwarePaginator
     */
    public function getAllByDestination(string $destination): LengthAwarePaginator
    {
        return TravelRequest::where('destination', 'like', "%{$destination}%")
                            ->latest()
                            ->paginate(15);
    }
    
    /**
     * Obtém todos os pedidos de viagem em um período específico
     *
     * @param string $startDate
     * @param string $endDate
     * @return LengthAwarePaginator
     */
    public function getAllBetweenDates(string $startDate, string $endDate): LengthAwarePaginator
    {
        return TravelRequest::whereDate('departure_date', '>=', $startDate)
                            ->whereDate('return_date', '<=', $endDate)
                            ->latest()
                            ->paginate(15);
    }
    
    /**
     * Exclui um pedido de viagem
     *
     * @param TravelRequest $travelRequest
     * @return bool
     */
    public function delete(TravelRequest $travelRequest): bool
    {
        return $travelRequest->delete();
    }
    
    /**
     * Obtém a contagem de pedidos de viagem por status
     *
     * @return array
     */
    public function getCountByStatus(): array
    {
        return TravelRequest::select('status', DB::raw('count(*) as total'))
                            ->groupBy('status')
                            ->pluck('total', 'status')
                            ->toArray();
    }
    
    /**
     * Atualiza um pedido de viagem
     *
     * @param TravelRequest $travelRequest
     * @param array $data
     * @return TravelRequest
     */
    public function update(TravelRequest $travelRequest, array $data): TravelRequest
    {
        $travelRequest->fill($data);
        $travelRequest->save();
        
        return $travelRequest;
    }
}