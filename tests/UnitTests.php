<?php

namespace RCConsulting\FileMakerApi;

use PHPUnit\Framework\TestCase;
use RCConsulting\FileMakerApi\Exception\Exception;
use ReflectionClass;
use Dotenv\Dotenv;

final class UnitTests extends TestCase
{
    private static ?DataApi $dataApi = null;
    private static bool $envLoaded = false;

    public static function setUpBeforeClass(): void
    {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
            self::$envLoaded = true;
        }
    }

    private function hasRequiredEnvVars(): bool
    {
        return !empty($_ENV['FM_SERVER_URL']) &&
            !empty($_ENV['FM_DATABASE']) &&
            !empty($_ENV['FM_USERNAME']) &&
            !empty($_ENV['FM_PASSWORD']);
    }

    private function getDataApi(): DataApi
    {
        if (self::$dataApi === null) {
            self::$dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE']);
        }
        return self::$dataApi;
    }

    // HttpClientType enum tests
    public function testHttpClientEnumValues(): void
    {
        $this->assertEquals('curl', HttpClientType::CURL->getValue());
        $this->assertEquals('guzzle', HttpClientType::GUZZLE->getValue());
        $this->assertEquals('curl', HttpClientType::CURL->value);
        $this->assertEquals('guzzle', HttpClientType::GUZZLE->value);
    }

    public function testHttpClientFromString(): void
    {
        $this->assertEquals(HttpClientType::CURL, HttpClientType::fromString('curl'));
        $this->assertEquals(HttpClientType::GUZZLE, HttpClientType::fromString('guzzle'));
        $this->assertEquals(HttpClientType::CURL, HttpClientType::fromString('CURL'));
        $this->assertEquals(HttpClientType::GUZZLE, HttpClientType::fromString('GUZZLE'));
        $this->assertEquals(HttpClientType::CURL, HttpClientType::fromString('CuRl'));
        $this->assertEquals(HttpClientType::GUZZLE, HttpClientType::fromString('GuZzLe'));
    }

    public function testHttpClientFromInvalidString(): void
    {
        $this->expectException(\ValueError::class);
        HttpClientType::fromString('invalid');
    }

    public function testHttpClientFromEmptyString(): void
    {
        $this->expectException(\ValueError::class);
        HttpClientType::fromString('');
    }

    public function testHttpClientFromNumericString(): void
    {
        $this->expectException(\ValueError::class);
        HttpClientType::fromString('123');
    }

    // Constructor tests (without login)
    public function testConstructorWithDefaults(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $this->assertInstanceOf(DataApi::class, $dataApi);
    }

    public function testConstructorWithSpecialCharactersInDatabase(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'Test DB With Spaces');
        $this->assertInstanceOf(DataApi::class, $dataApi);
    }

    public function testConstructorWithInvalidHttpClient(): void
    {
        $this->expectException(\ValueError::class);
        new DataApi('https://test.com/fmi/data', 'TestDB', null, null, true, false, false, 'invalid');
    }

    // Token management edge cases
    public function testTokenManagementWithNullToken(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $this->assertNull($dataApi->getApiToken());
        $this->assertFalse($dataApi->getApiTokenDate());
        $this->assertTrue($dataApi->isApiTokenExpired());
    }

    public function testTokenManagementWithEmptyToken(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $this->assertFalse($dataApi->setApiToken(''));
    }

    public function testTokenManagementWithCustomDate(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $customDate = time() - 300; // 5 minutes ago
        $dataApi->setApiToken('test-token', $customDate);
        $this->assertEquals($customDate, $dataApi->getApiTokenDate());
        $this->assertFalse($dataApi->isApiTokenExpired());
    }

    public function testTokenExpirationBoundary(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');

        // Exactly at expiration boundary (14 minutes)
        $dataApi->setApiToken('test-token', time() - (14 * 60));
        $this->assertFalse($dataApi->isApiTokenExpired());

        // Just over expiration boundary
        $dataApi->setApiToken('test-token', time() - (14 * 60 + 1));
        $this->assertTrue($dataApi->isApiTokenExpired());
    }

    // URL preparation edge cases
    public function testPrepareURLpartWithSpecialCharacters(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('prepareURLpart');
        $method->setAccessible(true);

        $this->assertEquals('test%20value', $method->invoke($dataApi, 'test value'));
        $this->assertEquals('test%2Bvalue', $method->invoke($dataApi, 'test+value'));
        $this->assertEquals('test%26value', $method->invoke($dataApi, 'test&value'));
        $this->assertEquals('test%2Fvalue', $method->invoke($dataApi, 'test/value'));
        $this->assertEquals('test%3Fvalue', $method->invoke($dataApi, 'test?value'));
        $this->assertEquals('test%23value', $method->invoke($dataApi, 'test#value'));
    }

    public function testPrepareURLpartWithEmptyString(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('prepareURLpart');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($dataApi, ''));
    }

    public function testPrepareURLpartWithWhitespace(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('prepareURLpart');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($dataApi, '   '));
        $this->assertEquals('test', $method->invoke($dataApi, '  test  '));
    }

    // Script options edge cases
    public function testPrepareScriptOptionsEmpty(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('prepareScriptOptions');
        $method->setAccessible(true);

        $result = $method->invoke($dataApi, []);
        $this->assertEquals([], $result);
    }

    public function testPrepareScriptOptionsWithEmptyParam(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('prepareScriptOptions');
        $method->setAccessible(true);

        $scripts = [
            [
                'name' => 'TestScript',
                'param' => '',
                'type' => DataApi::SCRIPT_POSTREQUEST
            ]
        ];

        $result = $method->invoke($dataApi, $scripts);
        $this->assertEquals('TestScript', $result['script']);
        $this->assertArrayNotHasKey('script.param', $result);
    }

    // Portal options edge cases
    public function testPreparePortalsOptionsEmpty(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('preparePortalsOptions');
        $method->setAccessible(true);

        $result = $method->invoke($dataApi, []);
        $this->assertEquals([], $result);
    }

    // Date format edge cases
    public function testPrepareDateFormatAllValues(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('prepareDateFormat');
        $method->setAccessible(true);

        $result = $method->invoke($dataApi, DataApi::DATE_DEFAULT);
        $this->assertEquals(0, $result['dateformats']);

        $result = $method->invoke($dataApi, DataApi::DATE_FILELOCALE);
        $this->assertEquals(1, $result['dateformats']);

        $result = $method->invoke($dataApi, DataApi::DATE_ISO8601);
        $this->assertEquals(2, $result['dateformats']);

        // Test invalid value defaults to 0
        $result = $method->invoke($dataApi, 999);
        $this->assertEquals(0, $result['dateformats']);
    }

    // Credential storage edge cases
    public function testStoreCredentialsWithEmptyValues(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('storeCredentials');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($dataApi, '', 'password'));
        $this->assertFalse($method->invoke($dataApi, 'user', ''));
        $this->assertFalse($method->invoke($dataApi, '', ''));
    }

    public function testStoreOAuthWithEmptyValues(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('storeOAuth');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($dataApi, '', 'identifier'));
        $this->assertFalse($method->invoke($dataApi, 'request-id', ''));
        $this->assertFalse($method->invoke($dataApi, '', ''));
    }

    // Constants validation
    public function testAllConstants(): void
    {
        // FileMaker constants
        $this->assertEquals(401, DataApi::FILEMAKER_NO_RECORDS);
        $this->assertEquals(952, DataApi::FILEMAKER_API_TOKEN_EXPIRED);

        // Script constants
        $this->assertEquals('prerequest', DataApi::SCRIPT_PREREQUEST);
        $this->assertEquals('presort', DataApi::SCRIPT_PRESORT);
        $this->assertEquals('postrequest', DataApi::SCRIPT_POSTREQUEST);

        // Date constants
        $this->assertEquals(0, DataApi::DATE_DEFAULT);
        $this->assertEquals(1, DataApi::DATE_FILELOCALE);
        $this->assertEquals(2, DataApi::DATE_ISO8601);
    }

    // HTTP client creation edge cases
    public function testCreateHttpClientWithAllCombinations(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('createHttpClient');
        $method->setAccessible(true);

        // Test all SSL and HTTP combinations
        $combinations = [
            [HttpClientType::CURL, true, true],
            [HttpClientType::CURL, true, false],
            [HttpClientType::CURL, false, true],
            [HttpClientType::CURL, false, false],
            [HttpClientType::GUZZLE, true, true],
            [HttpClientType::GUZZLE, true, false],
            [HttpClientType::GUZZLE, false, true],
            [HttpClientType::GUZZLE, false, false],
        ];

        foreach ($combinations as [$client, $ssl, $legacy]) {
            $result = $method->invoke($dataApi, $client, 'https://test.com', $ssl, $legacy);
            $this->assertInstanceOf(HttpClientInterface::class, $result);
        }
    }

    public function testCreateHttpClientWithCaseInsensitiveStrings(): void
    {
        $dataApi = new DataApi('https://test.com/fmi/data', 'TestDB');
        $reflection = new ReflectionClass($dataApi);
        $method = $reflection->getMethod('createHttpClient');
        $method->setAccessible(true);

        $strings = ['curl', 'CURL', 'Curl', 'cURL', 'guzzle', 'GUZZLE', 'Guzzle', 'GuZzLe'];

        foreach ($strings as $string) {
            $result = $method->invoke($dataApi, $string, 'https://test.com', true, false);
            $this->assertInstanceOf(HttpClientInterface::class, $result);
        }
    }

    // INTEGRATION TESTS - These require .env file with FileMaker credentials

    public function testIntegrationLogin(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars()) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = $this->getDataApi();
        $result = $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        $this->assertInstanceOf(DataApi::class, $result);
        $this->assertNotNull($dataApi->getApiToken());
    }

    public function testIntegrationLoginWithCurl(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars()) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE'], null, null, true, false, false, HttpClientType::CURL);
        $result = $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        $this->assertInstanceOf(DataApi::class, $result);
        $this->assertNotNull($dataApi->getApiToken());
    }

    public function testIntegrationLoginWithGuzzle(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars()) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE'], null, null, true, false, false, HttpClientType::GUZZLE);
        $result = $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        $this->assertInstanceOf(DataApi::class, $result);
        $this->assertNotNull($dataApi->getApiToken());
    }

    public function testIntegrationGetRecords(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars() || empty($_ENV['FM_LAYOUT'])) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = $this->getDataApi();
        $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        $records = $dataApi->getRecords($_ENV['FM_LAYOUT'], null, null, 1);
        $this->assertIsArray($records);
    }

    public function testIntegrationCreateAndDeleteRecord(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars() || empty($_ENV['FM_LAYOUT'])) {
            $this->markTestSkipped('Environment variables not configured');
        }

        // Create DataApi with returnResponseObject enabled for debugging
        $dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE'], null, null, true, false, true);
        $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        // Create test record with minimal data
        $testData = ['Account' => 'TEST_' . time()];
        $response = $dataApi->createRecord($_ENV['FM_LAYOUT'], $testData);

        // Debug: Dump the raw response
        echo "\n=== RAW RESPONSE DEBUG ===\n";
        echo "Response object type: " . get_class($response) . "\n";
        echo "Raw response body: " . json_encode($response->getBody(), JSON_PRETTY_PRINT) . "\n";
        echo "Raw response data: " . json_encode($response->getRawResponse(), JSON_PRETTY_PRINT) . "\n";
        echo "Records: " . json_encode($response->getRecords(), JSON_PRETTY_PRINT) . "\n";
        echo "Record ID: " . get_debug_type($response->getRawResponse()['recordId'] ) . "\n";
        echo "=== END DEBUG ===\n";

        $this->assertInstanceOf(Response::class, $response);

        // Extract record ID from response
        $recordId = $response->getBody()['response']['recordId'];
        $this->assertIsString($recordId);
        $this->assertNotEmpty($recordId);

        // Clean up - delete the record
        $dataApi->deleteRecord($_ENV['FM_LAYOUT'], $recordId);
        $this->assertTrue(true); // No exception thrown
    }


    public function testIntegrationLogout(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars()) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE']);
        $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        $result = $dataApi->logout();
        $this->assertInstanceOf(DataApi::class, $result);
        $this->assertNull($dataApi->getApiToken());
    }

    public function testIntegrationTokenRefresh(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars()) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE']);
        $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        $originalToken = $dataApi->getApiToken();
        $this->assertTrue($dataApi->refreshToken());

        // Token should be refreshed (might be same or different)
        $this->assertNotNull($dataApi->getApiToken());
    }

    public function testIntegrationValidateTokenWithServer(): void
    {
        if (!self::$envLoaded || !$this->hasRequiredEnvVars()) {
            $this->markTestSkipped('Environment variables not configured');
        }

        $dataApi = new DataApi($_ENV['FM_SERVER_URL'], $_ENV['FM_DATABASE']);
        $dataApi->login($_ENV['FM_USERNAME'], $_ENV['FM_PASSWORD']);

        // Use reflection to access the protected ClientRequest property
        $reflection = new ReflectionClass($dataApi);
        $clientProperty = $reflection->getProperty('ClientRequest');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($dataApi);

        $headersMethod = $reflection->getMethod('getDefaultHeaders');
        $headersMethod->setAccessible(true);
        $headers = $headersMethod->invoke($dataApi);

        // Make the request directly to debug
        $response = $client->request(
            'GET',
            "/vLatest/validateSession",
            [
                'headers' => $headers
            ]
        );

        echo "\n=== VALIDATE TOKEN DEBUG ===\n";
        echo "Response body: " . json_encode($response->getBody(), JSON_PRETTY_PRINT) . "\n";
        if (isset($response->getBody()['messages'])) {
            echo "Messages array: " . json_encode($response->getBody()['messages'], JSON_PRETTY_PRINT) . "\n";
            echo "First message code: " . var_export($response->getBody()['messages'][0]['code'], true) . "\n";
            echo "Code type: " . gettype($response->getBody()['messages'][0]['code']) . "\n";
            echo "Strict comparison (=== 0): " . var_export($response->getBody()['messages'][0]['code'] === 0, true) . "\n";
            echo "Loose comparison (== 0): " . var_export($response->getBody()['messages'][0]['code'] == 0, true) . "\n";
        }
        echo "=== END DEBUG ===\n";

        $this->assertTrue($dataApi->validateTokenWithServer());
    }


}
