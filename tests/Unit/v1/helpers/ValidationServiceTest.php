<?php

namespace Tests\Unit\v1\helpers;

use App\Exceptions\UnprocessableException;
use App\Helpers\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Validator;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class ValidationServiceTest extends TestCase
{
    private ValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationService = new ValidationService;
    }

    public function test_is_invalid_date_range(): void
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        // Create a reflection of the repository
        $validationServiceReflection = new ReflectionClass($this->validationService);

        $method = $validationServiceReflection->getMethod('isInValidDateRange');

        $method->setAccessible(true); // Make the method accessible

        // Call the private method with test data
        $result = $method->invoke($this->validationService, $start_date, $end_date);

        // Assert the result
        $this->assertFalse($result);

        // Ensure Validator was not updated.
        $this->assertNull($this->validationService->getValidator());
    }

    public function test_isinvaliddaterange_returns_true_with_invalid_date_range()
    {
        // Define an invalid date range
        $start_date = 'invalid-date';
        $end_date = 'invalid-date';

        // Create a reflection of the user repository
        $validationServiceReflection = new ReflectionClass($this->validationService);

        $method = $validationServiceReflection->getMethod('isInValidDateRange');

        $method->setAccessible(true); // Make the method accessible

        // Call the private method with test data
        $result = $method->invoke($this->validationService, $start_date, $end_date);

        // Assert the result
        $this->assertTrue($result);

        // Ensure Validator was updated.
        $this->assertNotNull($this->validationService->getValidator());

        // Assert a validator instance is set
        $this->assertInstanceOf(Validator::class, $this->validationService->getValidator());
    }

    public function test_apply_date_filters_valid_range(): void
    {
        // Mock the query builder
        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('whereBetween')
            ->once()
            ->with('created_at', Mockery::on(function ($argument) {
                return $argument[0]->eq(Carbon::parse('2024-01-01')->startOfDay()) &&
                    $argument[1]->eq(Carbon::parse('2024-03-20')->endOfDay());
            }));

        // Create the filter array with valid dates
        $filter = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-20',
        ];

        // Create a reflection of the service
        $validationServiceReflection = new ReflectionClass($this->validationService);

        // Access the protected method
        $method = $validationServiceReflection->getMethod('applyDateFilters');
        $method->setAccessible(true);

        // Call the protected method with test data
        $method->invokeArgs($this->validationService, [&$queryMock, &$filter]);

        // Assert the filter array has been modified
        $this->assertArrayNotHasKey('start_date', $filter);
        $this->assertArrayNotHasKey('end_date', $filter);
    }

    public function test_apply_date_filters_invalid_range(): void
    {
        // Mock the query builder
        $queryMock = Mockery::mock(Builder::class);

        // Create the filter array with invalid dates
        $filter = [
            'start_date' => 'invalid-date',
            'end_date' => 'another-invalid-date',
        ];

        // Create a reflection of the service
        $validationServiceReflection = new ReflectionClass($this->validationService);

        // Access the protected method
        $method = $validationServiceReflection->getMethod('applyDateFilters');
        $method->setAccessible(true);

        // Expect an UnprocessableException to be thrown
        $this->expectException(UnprocessableException::class);

        // Call the protected method with test data
        $method->invokeArgs($this->validationService, [&$queryMock, &$filter]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
