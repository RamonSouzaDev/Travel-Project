<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\TravelRequestStatusUpdated;

class TravelRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include travel requests with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('departure_date', [$startDate, $endDate])
                  ->orWhereBetween('return_date', [$startDate, $endDate])
                  ->orWhere(function ($query) use ($startDate, $endDate) {
                      $query->where('departure_date', '<=', $startDate)
                            ->where('return_date', '>=', $endDate);
                  });
        });
    }

    /**
     * Scope a query to filter by destination
     */
    public function scopeDestination($query, $destination)
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    /**
     * Check if travel request can be cancelled
     */
    public function canBeCancelled(): bool
    {
        if ($this->status === 'cancelado') {
            return false;
        }
    
        if ($this->status === 'aprovado') {
            // Previne erro se a data estiver nula
            if (is_null($this->departure_date)) {
                return false;
            }
    
            return $this->departure_date->diffInDays(now()) >= 3;
        }
    
        return true;
    }

    /**
     * Update status and notify user
     */
    public function updateStatus(string $status, ?string $reasonForCancellation = null): void
    {
        $oldStatus = $this->status;
        
        $this->status = $status;
        
        if ($status === 'cancelado' && $reasonForCancellation) {
            $this->reason_for_cancellation = $reasonForCancellation;
        }
        
        $this->save();
        
        // Notificar o usuário apenas se o status foi alterado
        if ($oldStatus !== $status) {
            $this->user->notify(new TravelRequestStatusUpdated($this));
        }
    }
}
