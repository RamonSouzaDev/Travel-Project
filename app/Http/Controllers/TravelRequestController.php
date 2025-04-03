<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestStatusRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class TravelRequestController extends Controller
{
    /**
     * Exibe uma lista com todos os pedidos de viagem do usuário atual ou todos para admins.
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:solicitado,aprovado,cancelado',
            'destination' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();
        $query = $user->isAdmin() ? TravelRequest::query() : $user->travelRequests();

        // Filtrar por status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        // Filtrar por destino
        if ($request->has('destination')) {
            $query->destination($request->destination);
        }

        // Filtrar por período
        if ($request->has(['start_date', 'end_date'])) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $travelRequests = $query->latest()->paginate(15);

        return TravelRequestResource::collection($travelRequests);
    }

    /**
     * Armazena um novo pedido de viagem.
     */
    public function store(StoreTravelRequestRequest $request)
    {
        $travelRequest = new TravelRequest($request->validated());
        $travelRequest->user_id = Auth::id();
        $travelRequest->save();

        return new TravelRequestResource($travelRequest);
    }

    /**
     * Exibe um pedido de viagem específico.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $travelRequest = TravelRequest::findOrFail($id);
        
        // Verifica se o usuário tem permissão para ver este pedido
        if (!$user->isAdmin() && $travelRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Não autorizado a ver este pedido de viagem'], Response::HTTP_FORBIDDEN);
        }

        return new TravelRequestResource($travelRequest);
    }

    /**
     * Atualiza o status de um pedido de viagem.
     */
    public function updateStatus(UpdateTravelRequestStatusRequest $request, string $id)
    {
        $user = Auth::user();
        $travelRequest = TravelRequest::findOrFail($id);
        
        // Apenas administradores podem atualizar o status
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Apenas administradores podem atualizar o status'], Response::HTTP_FORBIDDEN);
        }
        
        // Verificar se o status pode ser alterado para 'cancelado'
        if ($request->status === 'cancelado' && !$travelRequest->canBeCancelled()) {
            return response()->json([
                'message' => 'Este pedido não pode ser cancelado. Verifique as regras de cancelamento.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $travelRequest->updateStatus($request->status, $request->reason_for_cancellation);
        
        return new TravelRequestResource($travelRequest);
    }

    /**
     * Cancela um pedido de viagem pelo solicitante.
     */
    public function cancel(Request $request, string $id)
    {
        $request->validate([
            'reason_for_cancellation' => 'nullable|string|max:500',
        ]);
        
        $user = Auth::user();
        $travelRequest = TravelRequest::findOrFail($id);
        
        // Verificar se o usuário é o proprietário do pedido
        if ($travelRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Você só pode cancelar seus próprios pedidos'], Response::HTTP_FORBIDDEN);
        }
        
        // Verificar se o pedido pode ser cancelado
        if (!$travelRequest->canBeCancelled()) {
            return response()->json([
                'message' => 'Este pedido não pode ser cancelado. Verifique as regras de cancelamento.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $travelRequest->updateStatus('cancelado', $request->reason_for_cancellation);
        
        return new TravelRequestResource($travelRequest);
    }
}
