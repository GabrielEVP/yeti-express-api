<?php

namespace App\CompanyBill\test\Unit\Controllers;

use App\CompanyBill\Controllers\CompanyBillController;
use App\CompanyBill\Services\CompanyBillService;
use App\Http\Requests\CompanyBillRequest;
use App\Models\CompanyBill;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class CompanyBillControllerTest extends TestCase
{
    protected $serviceMock;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceMock = Mockery::mock(CompanyBillService::class);
        $this->controller = new CompanyBillController($this->serviceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createMockBill($id = 1): CompanyBill
    {
        $bill = Mockery::mock(CompanyBill::class);
        $bill->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($id) {
            return match($key) {
                'id' => $id,
                'name' => 'Test Bill',
                'description' => 'Test Description',
                'date' => now(),
                'method' => 'cash',
                'amount' => '100.00',
                'user_id' => 1,
                default => null,
            };
        });
        return $bill;
    }

    public function testIndexReturnsJsonResponseWithList()
    {
        $bills = Collection::make([$this->createMockBill()]);

        $this->serviceMock->shouldReceive('all')
            ->once()
            ->andReturn($bills);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testStoreReturnsCreatedBill()
    {
        $validatedData = [
            'name' => 'Test Bill',
            'description' => 'Test Description',
            'date' => '2025-06-24',
            'method' => 'cash',
            'amount' => 100.00,
        ];

        // Crear mock del usuario
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        // Crear mock del request
        $request = Mockery::mock(CompanyBillRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($validatedData);
        $request->shouldReceive('user')->once()->andReturn($userMock);

        $bill = $this->createMockBill();

        $this->serviceMock->shouldReceive('create')
            ->once()
            ->with([...$validatedData, 'user_id' => 1])
            ->andReturn($bill);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testShowReturnsBill()
    {
        $bill = $this->createMockBill();

        $this->serviceMock->shouldReceive('find')
            ->once()
            ->with('1')
            ->andReturn($bill);

        $response = $this->controller->show('1');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUpdateReturnsUpdatedBill()
    {
        $validatedData = [
            'name' => 'Updated Bill',
            'description' => 'Updated Description',
            'date' => '2025-06-24',
            'method' => 'cash',
            'amount' => 200.00,
        ];

        $request = Mockery::mock(CompanyBillRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($validatedData);

        $bill = $this->createMockBill();

        $this->serviceMock->shouldReceive('update')
            ->once()
            ->with('1', $validatedData)
            ->andReturn($bill);

        $response = $this->controller->update($request, '1');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDestroyReturnsSuccessMessage()
    {
        $this->serviceMock->shouldReceive('delete')
            ->once()
            ->with('1');

        $response = $this->controller->destroy('1');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            ['message' => 'CompanyBill with ID 1 has been deleted'],
            $response->getData(true)
        );
    }

    public function testSearchReturnsResults()
    {
        $bills = Collection::make([$this->createMockBill()]);

        $this->serviceMock->shouldReceive('search')
            ->once()
            ->with('test')
            ->andReturn($bills);

        $response = $this->controller->search('test');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
