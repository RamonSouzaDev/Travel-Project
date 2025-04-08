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
    
    /**
     * Obtém todos os pedidos de viagem de um usuário específico
     *
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getAllByUser(int $userId): LengthAwarePaginator;
    
    /**
     * Obtém todos os pedidos de viagem com um status específico
     *
     * @param string $status
     * @return LengthAwarePaginator
     */
    public function getAllByStatus(string $status): LengthAwarePaginator;
    
    /**
     * Obtém todos os pedidos de viagem para um destino específico
     *
     * @param string $destination
     * @return LengthAwarePaginator
     */
    public function getAllByDestination(string $destination): LengthAwarePaginator;
    
    /**
     * Obtém todos os pedidos de viagem em um período específico
     *
     * @param string $startDate
     * @param string $endDate
     * @return LengthAwarePaginator
     */
    public function getAllBetweenDates(string $startDate, string $endDate): LengthAwarePaginator;
    
    /**
     * Exclui um pedido de viagem
     *
     * @param TravelRequest $travelRequest
     * @return bool
     */
    public function delete(TravelRequest $travelRequest): bool;
    
    /**
     * Obtém a contagem de pedidos de viagem por status
     *
     * @return array
     */
    public function getCountByStatus(): array;
    
    /**
     * Atualiza um pedido de viagem
     *
     * @param TravelRequest $travelRequest
     * @param array $data
     * @return TravelRequest
     */
    public function update(TravelRequest $travelRequest, array $data): TravelRequest;
}