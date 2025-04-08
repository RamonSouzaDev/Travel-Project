<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestStatusRequest;
use App\Http\Resources\TravelRequestResource;
use App\Services\Interfaces\TravelRequestServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TravelRequestController extends Controller
{
    protected $travelRequestService;
    
    public function __construct(TravelRequestServiceInterface $travelRequestService)
    {
        $this->travelRequestService = $travelRequestService;
    }

    /**
     * Exibe uma lista com todos os pedidos de viagem do usuário atual ou todos para admins.
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'status' => 'nullable|string|in:solicitado,aprovado,cancelado',
                'destination' => 'nullable|string|max:255',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $filters = $request->only(['status', 'destination', 'start_date', 'end_date']);
            $travelRequests = $this->travelRequestService->getAllTravelRequests($filters);
            
            return TravelRequestResource::collection($travelRequests);
        } catch (\Exception $e) {
            Log::error('Erro ao listar pedidos de viagem: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao listar pedidos de viagem: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Armazena um novo pedido de viagem.
     */
    public function store(StoreTravelRequestRequest $request)
    {
        try {
            $travelRequest = $this->travelRequestService->createTravelRequest($request->validated());
            return new TravelRequestResource($travelRequest);
        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido de viagem: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao criar pedido de viagem: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exibe um pedido de viagem específico.
     */
    public function show(string $id)
    {
        try {
            $travelRequest = $this->travelRequestService->getTravelRequest($id);
            return new TravelRequestResource($travelRequest);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido de viagem não encontrado'], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            Log::error('Erro ao visualizar pedido de viagem: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao visualizar pedido de viagem: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualiza o status de um pedido de viagem.
     */
    public function updateStatus(UpdateTravelRequestStatusRequest $request, string $id)
    {
        try {
            $travelRequest = $this->travelRequestService->updateTravelRequestStatus(
                $id,
                $request->status,
                $request->reason_for_cancellation
            );
            return new TravelRequestResource($travelRequest);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido de viagem não encontrado'], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status do pedido: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao atualizar status: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Cancela um pedido de viagem pelo solicitante.
     */
    public function cancel(Request $request, string $id)
    {
        try {
            $request->validate([
                'reason_for_cancellation' => 'nullable|string|max:500',
            ]);
            
            $travelRequest = $this->travelRequestService->cancelTravelRequest(
                $id,
                $request->reason_for_cancellation
            );
            return new TravelRequestResource($travelRequest);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido de viagem não encontrado'], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar pedido: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao cancelar pedido: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}