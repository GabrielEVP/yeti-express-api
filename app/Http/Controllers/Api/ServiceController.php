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
        $services = Service::with($this->relations)
            ->where('user_id', Auth::id())
            ->get();

        $result = $services->map(function ($service) {
            $totalBillAmount = $service->bills->sum('amount');
            $totalExpense = $totalBillAmount + $service->commission;
            $profit = $service->amount - $totalExpense;

            return [
                'id' => $service->id,
                'name' => $service->name,
                'amount' => $service->amount,
                'commission' => $service->commission,
                'total_expense' => $totalExpense,
                'total_earning' => $profit,
            ];
        });

        return response()->json($result, 200);
    }

    public function show(string $id): JsonResponse
    {
        $service = Service::with($this->relations)
            ->where('user_id', Auth::id())
            ->findOrFail($id);

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
        ];

        return response()->json($data, 200);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Service::create(
            $request->merge(['user_id' => $request->user()->id])
                ->only(['name', 'description', 'amount', 'comision', 'user_id'])
        );

        foreach ($request->input('bills', []) as $bill) {
            $service->bills()->create($bill);
        }

        return response()->json($service->load($this->relations), 201);
    }

    public function update(ServiceRequest $request, string $id): JsonResponse
    {
        $service = Service::where('user_id', Auth::id())->findOrFail($id);

        $service->update(
            $request->merge(['user_id' => $request->user()->id])
                ->only(['name', 'description', 'amount', 'comision', 'user_id'])
        );

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
        $service = Service::where('user_id', Auth::id())->findOrFail($id);

        $service->bills()->delete();
        $service->delete();

        return response()->json([
            'message' => "Service with ID {$id} has been deleted"
        ], 200);
    }

    public function getByDelivery(string $delivery_id): JsonResponse
    {
        $services = Service::with($this->relations)
            ->where('courier_id', $delivery_id)
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($services, 200);
    }

    public function search(string $query): JsonResponse
    {
        $services = Service::with($this->relations)
            ->where('user_id', Auth::id())
            ->where('name', 'LIKE', "%{$query}%")
            ->get();

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
            ];
        });

        return response()->json($result, 200);
    }
}
