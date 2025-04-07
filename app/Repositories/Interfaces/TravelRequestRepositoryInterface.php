<?php

namespace App\Repositories\Interfaces;

use App\Models\TravelRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TravelRequestRepositoryInterface
{
    /**
     * Obtém todos os pedidos de viagem com filtros aplicados
     *
     * @param array $filters
     * @param int|null $userId
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = [], ?int $userId = null): LengthAwarePaginator;
    
    /**
     * Cria um novo pedido de viagem
     *
     * @param array $data
     * @return TravelRequest
     */
    public function create(array $data): TravelRequest;
    
    /**
     * Encontra um pedido de viagem pelo ID
     *
     * @param string $id
     * @return TravelRequest|null
     */
    public function findById(string $id): ?TravelRequest;
    
    /**
     * Atualiza o status de um pedido de viagem
     *
     * @param TravelRequest $travelRequest
     * @param string $status
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     */
    public function updateStatus(TravelRequest $travelRequest, string $status, ?string $reasonForCancellation = null): TravelRequest;
}