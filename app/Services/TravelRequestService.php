<?php

namespace App\Services;

use App\Models\TravelRequest;
use App\Repositories\Interfaces\TravelRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TravelRequestService
{
    protected $travelRequestRepository;

    public function __construct(TravelRequestRepositoryInterface $travelRequestRepository)
    {
        $this->travelRequestRepository = $travelRequestRepository;
    }

    /**
     * Obtém uma lista de pedidos de viagem com filtros aplicados
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllTravelRequests(array $filters = []): LengthAwarePaginator
    {
        $user = Auth::user();
        $userId = $user->isAdmin() ? null : $user->id;
        
        return $this->travelRequestRepository->getAllWithFilters($filters, $userId);
    }

    /**
     * Cria um novo pedido de viagem
     *
     * @param array $data
     * @return TravelRequest
     */
    public function createTravelRequest(array $data): TravelRequest
    {
        $data['user_id'] = Auth::id();
        return $this->travelRequestRepository->create($data);
    }

    /**
     * Obtém um pedido de viagem específico
     *
     * @param string $id
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getTravelRequest(string $id): TravelRequest
    {
        $user = Auth::user();
        $travelRequest = $this->travelRequestRepository->findById($id);
        
        if (!$travelRequest) {
            throw new ModelNotFoundException('Pedido de viagem não encontrado');
        }
        
        // Verifica se o usuário tem permissão para ver este pedido
        if (!$user->isAdmin() && $travelRequest->user_id !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Não autorizado a ver este pedido de viagem');
        }
        
        return $travelRequest;
    }

    /**
     * Atualiza o status de um pedido de viagem por um administrador
     *
     * @param string $id
     * @param string $status
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateTravelRequestStatus(string $id, string $status, ?string $reasonForCancellation = null): TravelRequest
    {
        $user = Auth::user();
        $travelRequest = $this->travelRequestRepository->findById($id);
        
        if (!$travelRequest) {
            throw new ModelNotFoundException('Pedido de viagem não encontrado');
        }
        
        // Apenas administradores podem atualizar o status
        if (!$user->isAdmin()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Apenas administradores podem atualizar o status');
        }
        
        // Verificar se o status pode ser alterado para 'cancelado'
        if ($status === 'cancelado' && !$travelRequest->canBeCancelled()) {
            throw new \Exception('Este pedido não pode ser cancelado. Verifique as regras de cancelamento.');
        }
        
        return $this->travelRequestRepository->updateStatus($travelRequest, $status, $reasonForCancellation);
    }

    /**
     * Cancela um pedido de viagem pelo próprio solicitante
     *
     * @param string $id
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function cancelTravelRequest(string $id, ?string $reasonForCancellation = null): TravelRequest
    {
        $user = Auth::user();
        $travelRequest = $this->travelRequestRepository->findById($id);
        
        if (!$travelRequest) {
            throw new ModelNotFoundException('Pedido de viagem não encontrado');
        }
        
        // Verificar se o usuário é o proprietário do pedido
        if ($travelRequest->user_id !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Você só pode cancelar seus próprios pedidos');
        }
        
        // Verificar se o pedido pode ser cancelado
        if (!$travelRequest->canBeCancelled()) {
            throw new \Exception('Este pedido não pode ser cancelado. Verifique as regras de cancelamento.');
        }
        
        return $this->travelRequestRepository->updateStatus($travelRequest, 'cancelado', $reasonForCancellation);
    }
}