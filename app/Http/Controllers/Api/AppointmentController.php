<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    /**
     * Agenda diaria o semanal para el panel de gestión.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'] ?? $validated['date'] ?? 'today')->startOfDay();
        $to   = Carbon::parse($validated['to'] ?? $validated['date'] ?? 'today')->addDay()->startOfDay();

        $appointments = Appointment::with(['client', 'service'])
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<', $to)
            ->orderBy('starts_at')
            ->get();

        return response()->json($appointments);
    }

    /**
     * Registra una nueva cita verificando solapamiento.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->schedule(
            clientId: $request->validated('client_id'),
            serviceId: $request->validated('service_id'),
            startsAtStr: $request->validated('starts_at'),
            notes: $request->validated('notes')
        );

        return response()->json([
            'message' => 'Cita agendada correctamente.',
            'data' => $appointment->load(['client', 'service']),
        ], 201);
    }

    /**
     * Cambiar estado de la cita (completada, cancelada, confirmada).
     */
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed,no_show'],
        ]);

        // Si se reactiva una cita cancelada/no_show, comprobar que el hueco sigue libre
        $reactivating = in_array($appointment->status, ['cancelled', 'no_show'])
            && !in_array($validated['status'], ['cancelled', 'no_show']);

        if ($reactivating) {
            $this->appointmentService->ensureNoOverlap(
                $appointment->starts_at, $appointment->ends_at, $appointment->id
            );
        }

        $appointment->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Estado de la cita actualizado.',
            'data' => $appointment->load(['client', 'service']),
        ]);
    }
}