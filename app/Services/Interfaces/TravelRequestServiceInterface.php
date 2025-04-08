<?php

namespace App\Services\Interfaces;

use App\Models\TravelRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TravelRequestServiceInterface
{
    /**
     * Obtém uma lista de pedidos de viagem com filtros aplicados
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllTravelRequests(array $filters = []): LengthAwarePaginator;
    
    /**
     * Cria um novo pedido de viagem
     *
     * @param array $data
     * @return TravelRequest
     */
    public function createTravelRequest(array $data): TravelRequest;
    
    /**
     * Obtém um pedido de viagem específico
     *
     * @param string $id
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getTravelRequest(string $id): TravelRequest;
    
    /**
     * Atualiza o status de um pedido de viagem por um administrador
     *
     * @param string $id
     * @param string $status
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateTravelRequestStatus(string $id, string $status, ?string $reasonForCancellation = null): TravelRequest;
    
    /**
     * Cancela um pedido de viagem pelo próprio solicitante
     *
     * @param string $id
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function cancelTravelRequest(string $id, ?string $reasonForCancellation = null): TravelRequest;
}