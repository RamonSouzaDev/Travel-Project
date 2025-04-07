<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelRequest extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'destination',
        'departure_date',
        'return_date',
        'status',
        'reason_for_cancellation',
    ];

    /**
     * Os atributos que devem ser convertidos em tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * Verifica se o pedido de viagem pode ser cancelado.
     * 
     * Regras de negócio para cancelamento:
     * - Só pode cancelar pedidos no status 'solicitado' ou 'aprovado'
     * - Se aprovado, só pode cancelar se a data de partida for pelo menos 7 dias a partir de hoje
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        // Se o status não for 'solicitado' ou 'aprovado', não pode cancelar
        if (!in_array($this->status, ['solicitado', 'aprovado'])) {
            return false;
        }
        
        // Se for 'aprovado', verifica o prazo de 7 dias antes da partida
        if ($this->status === 'aprovado') {
            return $this->departure_date->diffInDays(now()) >= 7;
        }
        
        // Pedidos 'solicitados' sempre podem ser cancelados
        return true;
    }

    /**
     * Atualiza o status do pedido de viagem.
     *
     * @param string $status
     * @param string|null $reasonForCancellation
     * @return void
     */
    public function updateStatus(string $status, ?string $reasonForCancellation = null): void
    {
        $this->status = $status;
        
        if ($status === 'cancelado' && $reasonForCancellation) {
            $this->reason_for_cancellation = $reasonForCancellation;
        }
        
        $this->save();
    }

    /**
     * Obtém o usuário relacionado a este pedido de viagem.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Escopo de consulta para filtrar por status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Escopo de consulta para filtrar por destino.
     */
    public function scopeDestination($query, $destination)
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    /**
     * Escopo de consulta para filtrar por período de data.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereDate('departure_date', '>=', $startDate)
                     ->whereDate('return_date', '<=', $endDate);
    }
}