<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\Service;
use App\Http\Requests\ServiceRequest;

class ServiceController extends Controller
{
    private array $relations = ['bills', 'events'];

    public function index(): JsonResponse
    {
        $services = Auth::user()->services()->get();

        $result = $services->map(function ($service) {
            $totalBillAmount = $service->bills->sum('amount');
            $totalExpense = $totalBillAmount + $service->commission;
            $profit = $service->amount - $totalExpense;

            $canDelete = $this->canDeleteService($service);

            return [
                'id' => $service->id,
                'name' => $service->name,
                'amount' => $service->amount,
                'commission' => $service->commission,
                'total_expense' => $totalExpense,
                'total_earning' => $profit,
                'can_delete' => $canDelete,
            ];
        });

        return response()->json($result, 200);
    }

    public function show(string $id): JsonResponse
    {
        $service = Auth::user()->services()->with($this->relations)->findOrFail($id);

        $totalBillAmount = $service->bills->sum('amount');
        $totalExpense = $totalBillAmount + $service->commission;
        $profit = $service->amount - $totalExpense;

        $data = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'amount' => $service->amount,
            'commission' => $service->commission,
            'bills' => $service->bills,
            'events' => $service->events,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
            'total_expense' => $totalExpense,
            'total_earning' => $profit,
            'can_delete' => $this->canDeleteService($service),
        ];

        return response()->json($data, 200);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Auth::user()->services()->create($request->only(['name', 'description', 'amount', 'comision', 'user_id']));

        foreach ($request->input('bills', []) as $bill) {
            $service->bills()->create($bill);
        }

        return response()->json($service->load($this->relations), 201);
    }

    public function update(ServiceRequest $request, string $id): JsonResponse
    {
        $service = Auth::user()->services()->findOrFail($id);

        $service->update($request->only(['name', 'description', 'amount', 'comision', 'user_id']));

        $service->bills()->delete();
        foreach ($request->input('bills', []) as $bill) {
            $service->bills()->create($bill);
        }

        ServiceEvent::create([
            "event" => "update_service",
            "section" => "services",
            "reference_table" => null,
            "reference_id" => null,
            "service_id" => $service->id,
        ]);

        return response()->json($service->load($this->relations), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = Auth::user()->services()->findOrFail($id);

        if (!$this->canDeleteService($service)) {
            return response()->json([
                'message' => 'No se puede eliminar el servicio porque está relacionado con un delivery',
                'error' => 'service_has_delivery_relation'
            ], 422);
        }

        $service->bills()->delete();
        $service->delete();

        return response()->json([
            'message' => "Service with ID {$id} has been deleted"
        ], 200);
    }

    public function getByDelivery(string $delivery_id): JsonResponse
    {
        $services = Auth::user()->services()->with($this->relations)->where('courier_id', $delivery_id)->get();
        return response()->json($services, 200);
    }

    public function search(string $query): JsonResponse
    {
        $services = Auth::user()->services()->with($this->relations)->where('name', 'LIKE', "%{$query}%")->get();

        $result = $services->map(function ($service) {
            $totalBillAmount = $service->bills->sum('amount');
            $totalExpense = $totalBillAmount + $service->commission;
            $profit = $service->amount - $totalExpense;

            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'amount' => $service->amount,
                'commission' => $service->commission,
                'bills' => $service->bills,
                'events' => $service->events,
                'total_expense' => $totalExpense,
                'total_earning' => $profit,
                'can_delete' => $this->canDeleteService($service),
            ];
        });

        return response()->json($result, 200);
    }

    private function canDeleteService(Service $service): bool
    {
        return !$service->deliveries()->exists();
    }
}