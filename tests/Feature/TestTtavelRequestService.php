<?php

namespace App\Services;

use App\Models\TravelRequest;
use App\Services\Interfaces\TravelRequestServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TestTravelRequestService implements TravelRequestServiceInterface
{
    /**
     * Obtém uma lista de pedidos de viagem com filtros aplicados
     * Versão simplificada para testes
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllTravelRequests(array $filters = []): LengthAwarePaginator
    {
        $user = Auth::user();
        $query = $user->isAdmin() ? TravelRequest::query() : TravelRequest::where('user_id', $user->id);
        
        // Aplicação mínima de filtros para testes
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['destination'])) {
            $query->where('destination', 'like', "%{$filters['destination']}%");
        }
        
        return $query->latest()->paginate(15);
    }

    /**
     * Cria um novo pedido de viagem
     * Versão simplificada para testes
     *
     * @param array $data
     * @return TravelRequest
     */
    public function createTravelRequest(array $data): TravelRequest
    {
        $travelRequest = new TravelRequest($data);
        $travelRequest->user_id = Auth::id();
        $travelRequest->status = 'solicitado';
        $travelRequest->save();
        
        return $travelRequest;
    }

    /**
     * Obtém um pedido de viagem específico
     * Versão simplificada para testes
     *
     * @param string $id
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getTravelRequest(string $id): TravelRequest
    {
        $user = Auth::user();
        $travelRequest = TravelRequest::find($id);
        
        if (!$travelRequest) {
            throw new ModelNotFoundException('Pedido de viagem não encontrado');
        }
        
        // Verifica se o usuário tem permissão para ver este pedido
        if (!$user->isAdmin() && $travelRequest->user_id !== $user->id) {
            throw new AuthorizationException('Não autorizado a ver este pedido de viagem');
        }
        
        return $travelRequest;
    }

    /**
     * Atualiza o status de um pedido de viagem por um administrador
     * Versão simplificada para testes
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
        $travelRequest = TravelRequest::find($id);
        
        if (!$travelRequest) {
            throw new ModelNotFoundException('Pedido de viagem não encontrado');
        }
        
        // Apenas administradores podem atualizar o status
        if (!$user->isAdmin()) {
            throw new AuthorizationException('Apenas administradores podem atualizar o status');
        }
        
        // Atualizar o status (sem verificar canBeCancelled para simplificar testes)
        $travelRequest->status = $status;
        
        if ($status === 'cancelado' && $reasonForCancellation) {
            $travelRequest->reason_for_cancellation = $reasonForCancellation;
        }
        
        $travelRequest->save();
        return $travelRequest;
    }

    /**
     * Cancela um pedido de viagem pelo próprio solicitante
     * Versão simplificada para testes
     *
     * @param string $id
     * @param string|null $reasonForCancellation
     * @return TravelRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function cancelTravelRequest(string $id, ?string $reasonForCancellation = null): TravelRequest
    {
        $user = Auth::user();
        $travelRequest = TravelRequest::find($id);
        
        if (!$travelRequest) {
            throw new ModelNotFoundException('Pedido de viagem não encontrado');
        }
        
        // Verificar se o usuário é o proprietário do pedido
        if ($travelRequest->user_id !== $user->id) {
            throw new AuthorizationException('Você só pode cancelar seus próprios pedidos');
        }
        
        // Atualizar o status (sem verificar canBeCancelled para simplificar testes)
        $travelRequest->status = 'cancelado';
        $travelRequest->reason_for_cancellation = $reasonForCancellation;
        $travelRequest->save();
        
        return $travelRequest;
    }
}