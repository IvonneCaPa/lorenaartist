<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    /**
     * Valida y crea una nueva cita garantizando que no existan colisiones.
     */
    public function schedule(int $clientId, int $serviceId, string $startsAtStr, ?string $notes = null, string $status = 'confirmed'): Appointment
    {
        $service = Service::findOrFail($serviceId);
        $startsAt = Carbon::parse($startsAtStr);
        $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

        $this->validateBusinessHours($startsAt, $endsAt);

        // Serializa la creación para evitar dobles reservas simultáneas
        return Cache::lock('appointments:schedule', 10)->block(5, function () use (
            $clientId, $serviceId, $startsAt, $endsAt, $notes, $status
        ) {
            return DB::transaction(function () use ($clientId, $serviceId, $startsAt, $endsAt, $notes, $status) {
                $this->ensureNoOverlap($startsAt, $endsAt);

                return Appointment::create([
                    'client_id'  => $clientId,
                    'service_id' => $serviceId,
                    'starts_at'  => $startsAt,
                    'ends_at'    => $endsAt,
                    'status'     => $status,
                    'notes'      => $notes,
                ]);
            });
        });
    }

    /**
     * Comprueba si el centro está abierto en esa franja.
     */
    private function validateBusinessHours(Carbon $start, Carbon $end): void
    {
        $fail = fn (string $msg) => throw ValidationException::withMessages(['starts_at' => [$msg]]);

        if ($start->isSunday()) {
            $fail('El centro permanece cerrado los domingos.');
        }

        $opening = $start->copy()->setTime(9, 0);
        $closing = $start->copy()->setTime(20, 0);

        if ($start->lt($opening) || $end->gt($closing)) {
            $fail('La cita debe quedar dentro del horario de atención (09:00 a 20:00).');
        }
    }

    /**
     * Valida que ningún otro cliente tenga cita asignada en ese tramo.
     */
    public function ensureNoOverlap(Carbon $start, Carbon $end, ?int $ignoreId = null): void
    {
        $hasOverlap = Appointment::overlapping($start, $end, $ignoreId)->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'starts_at' => ['El horario solicitado ya se encuentra ocupado por otra cita.'],
            ]);
        }
    }
}