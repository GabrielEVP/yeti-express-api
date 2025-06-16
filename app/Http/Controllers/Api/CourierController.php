<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourierRequest;
use App\Models\Courier;
use App\Models\CourierEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourierController extends Controller
{
    private array $relations = [
        'events',
    ];

    public function index(): JsonResponse
    {
        $couriers = Auth::user()->couriers()->get();

        $result = $couriers->map(function ($courier) {
            $courierData = $courier->toArray();
            $courierData['can_delete'] = $this->canDeleteCourier($courier);
            return $courierData;
        });

        return response()->json($result, 200);
    }

    public function show(Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);

        $courierData = $courier->load($this->relations)->toArray();
        $courierData['can_delete'] = $this->canDeleteCourier($courier);

        return response()->json($courierData, 200);
    }

    public function store(CourierRequest $request): JsonResponse
    {
        $courier = Auth::user()->couriers()->create($request->merge(['user_id' => Auth::id()])->all());
        return response()->json($courier, 201);
    }

    public function update(CourierRequest $request, Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);
        $courier->update($request->merge(['user_id' => Auth::id()])->all());

        CourierEvent::create([
            'event' => 'update_courier',
            'section' => 'couriers',
            'reference_table' => null,
            'reference_id' => null,
            'courier_id' => $courier->id,
        ]);

        return response()->json($courier, 200);
    }

    public function destroy(Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);

        // Verificar si el courier se puede eliminar
        if (!$this->canDeleteCourier($courier)) {
            return response()->json([
                'message' => 'No se puede eliminar el courier porque tiene deliveries asociados',
                'error' => 'courier_has_deliveries'
            ], 422);
        }

        $courier->delete();

        return response()->json([
            'message' => "Courier with ID {$courier->id} has been deleted",
        ], 200);
    }

    private function authorizeOwner(Courier $courier): void
    {
        abort_if(
            $courier->user_id !== Auth::id(),
            403,
            'You do not have permission to access this courier.'
        );
    }

    public function search(string $query): JsonResponse
    {
        $couriers = Courier::with($this->relations)
            ->where('user_id', Auth::id()) // Agregado filtro por usuario
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%");
            })
            ->get();

        $result = $couriers->map(function ($courier) {
            $courierData = $courier->toArray();
            $courierData['can_delete'] = $this->canDeleteCourier($courier);
            return $courierData;
        });

        return response()->json($result, 200);
    }

    private function canDeleteCourier(Courier $courier): bool
    {
        return !$courier->deliveries()->exists();
    }
}