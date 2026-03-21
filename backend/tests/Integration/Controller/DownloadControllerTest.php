<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for DownloadController.
 *
 * These tests require the Docker services (Redis) to be running,
 * or a test environment with the appropriate services available.
 *
 * Run with: php bin/phpunit --testsuite Integration
 */
class DownloadControllerTest extends WebTestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('service', $data);
    }

    public function test_download_returns_400_when_url_is_missing(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/download',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['format' => 'mp3'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function test_download_returns_400_when_format_is_missing(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/download',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['url' => 'https://www.youtube.com/watch?v=abc'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function test_status_returns_404_for_unknown_job(): void
    {
        $client = static::createClient();
        $client->request('GET', '/status/nonexistent-job-id-that-does-not-exist');

        $this->assertResponseStatusCodeSame(404);
    }

    public function test_fetch_returns_404_for_unknown_job(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fetch/nonexistent-job-id-that-does-not-exist');

        $this->assertResponseStatusCodeSame(404);
    }
}
