<?php

namespace Tests\Unit\Services;

use App\Services\ResponseTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class ResponseTransformerTest
 *
 * Tests for the ResponseTransformer class.
 */
class ResponseTransformerTest extends TestCase
{
    use RefreshDatabase;

    protected ResponseTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = ResponseTransformer::make();
    }

    public function test_success_response(): void
    {
        $response = $this->transformer->success(['key' => 'value'], 'Success message');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Success message', $data['message']);
        $this->assertEquals(['key' => 'value'], $data['data']);
        $this->assertNull($data['errors']);
    }

    public function test_created_response(): void
    {
        $response = $this->transformer->created(['id' => 1], 'Resource created');

        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Resource created', $data['message']);
    }

    public function test_paginated_response(): void
    {
        $paginator = new LengthAwarePaginator(
            collect([['id' => 1], ['id' => 2]]),
            100,
            2,
            1
        );

        $response = $this->transformer->paginated($paginator);

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertEquals(100, $data['meta']['pagination']['total']);
        $this->assertEquals(2, $data['meta']['pagination']['per_page']);
    }

    public function test_validation_error_response(): void
    {
        $errors = [
            'email' => ['The email field is required.'],
            'password' => ['The password must be at least 8 characters.'],
        ];

        $response = $this->transformer->validationError($errors);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('VALIDATION_ERROR', $data['error_code']);
        $this->assertArrayHasKey('errors', $data['errors']);
    }

    public function test_unauthorized_response(): void
    {
        $response = $this->transformer->unauthorized('Please log in');

        $this->assertEquals(401, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('UNAUTHORIZED', $data['error_code']);
    }

    public function test_forbidden_response(): void
    {
        $response = $this->transformer->forbidden('Insufficient permissions');

        $this->assertEquals(403, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('FORBIDDEN', $data['error_code']);
    }

    public function test_not_found_response(): void
    {
        $response = $this->transformer->notFound('Post not found');

        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('NOT_FOUND', $data['error_code']);
    }

    public function test_too_many_requests_response(): void
    {
        $response = $this->transformer->tooManyRequests('Rate limit exceeded', 60);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('60', $response->headers->get('Retry-After'));
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $data['error_code']);
    }

    public function test_server_error_response(): void
    {
        $response = $this->transformer->serverError('Something went wrong');

        $this->assertEquals(500, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('INTERNAL_SERVER_ERROR', $data['error_code']);
    }

    public function test_no_content_response(): void
    {
        $response = $this->transformer->noContent('Deleted successfully');

        $this->assertEquals(204, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertNull($data['data']);
    }

    public function test_with_meta(): void
    {
        $response = $this->transformer
            ->withMeta('custom_key', 'custom_value')
            ->withMetaArray(['another_key' => 'another_value'])
            ->success(['data' => 'test']);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('custom_value', $data['meta']['custom_key']);
        $this->assertEquals('another_value', $data['meta']['another_key']);
    }

    public function test_with_headers(): void
    {
        $response = $this->transformer
            ->withHeader('X-Custom-Header', 'custom-value')
            ->withHeaders(['X-Another-Header' => 'another-value'])
            ->success(['data' => 'test']);

        $this->assertEquals('custom-value', $response->headers->get('X-Custom-Header'));
        $this->assertEquals('another-value', $response->headers->get('X-Another-Header'));
    }

    public function test_api_version_header(): void
    {
        $response = $this->transformer->success(['data' => 'test']);

        $this->assertEquals('v1', $response->headers->get('X-API-Version'));
    }

    public function test_cache_headers(): void
    {
        $response = $this->transformer
            ->cache(3600, true)
            ->success(['data' => 'test']);

        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control'));
    }

    public function test_deprecation_headers(): void
    {
        $response = $this->transformer
            ->deprecate('This endpoint is deprecated', 'https://docs.example.com/migration')
            ->success(['data' => 'test']);

        $this->assertEquals('true', $response->headers->get('Deprecation'));
        $this->assertNotNull($response->headers->get('Sunset'));
    }

    public function test_error_code_mapping(): void
    {
        $codes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_SERVER_ERROR',
        ];

        foreach ($codes as $status => $expectedCode) {
            $response = $this->transformer->error('Error', $status);
            $data = json_decode($response->getContent(), true);
            $this->assertEquals($expectedCode, $data['error_code'], "Failed for status {$status}");
        }
    }

    public function test_request_id_in_error_response(): void
    {
        $response = $this->transformer->error('Error', 500);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('request_id', $data);
        $this->assertStringStartsWith('req_', $data['request_id']);
    }

    public function test_timestamp_in_response(): void
    {
        $response = $this->transformer->success(['data' => 'test']);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('timestamp', $data['meta']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['meta']['timestamp']);
    }
}
