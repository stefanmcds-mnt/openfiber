<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use OpenFiber\Storage\InMemoryWsdlStorage;

/**
 * Test per InMemoryWsdlStorage
 * 
 * Fase 3: Testabilità e Mockabilità ✓
 */
class InMemoryWsdlStorageTest extends TestCase
{
    private InMemoryWsdlStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryWsdlStorage();
    }

    public function testGetWsdlReturnsNullWhenNotFound(): void
    {
        $result = $this->storage->getWsdl('non_existent_service');
        $this->assertNull($result);
    }

    public function testSaveAndGetWsdl(): void
    {
        $serviceName = 'test_service';
        $url = 'http://example.com/service.wsdl';
        $content = '<definitions>Test WSDL</definitions>';

        $saved = $this->storage->saveWsdl($serviceName, $url, $content);
        $this->assertTrue($saved);

        $retrieved = $this->storage->getWsdl($serviceName);
        $this->assertEquals($content, $retrieved);
    }

    public function testSaveWsdlOverwritesExisting(): void
    {
        $serviceName = 'test_service';
        $url = 'http://example.com/service.wsdl';
        $oldContent = '<definitions>Old WSDL</definitions>';
        $newContent = '<definitions>New WSDL</definitions>';

        $this->storage->saveWsdl($serviceName, $url, $oldContent);
        $this->storage->saveWsdl($serviceName, $url, $newContent);

        $retrieved = $this->storage->getWsdl($serviceName);
        $this->assertEquals($newContent, $retrieved);
    }

    public function testMultipleServicesStoredIndependently(): void
    {
        $service1 = 'service_one';
        $content1 = '<definitions>Service 1</definitions>';
        
        $service2 = 'service_two';
        $content2 = '<definitions>Service 2</definitions>';

        $this->storage->saveWsdl($service1, 'http://url1.com', $content1);
        $this->storage->saveWsdl($service2, 'http://url2.com', $content2);

        $this->assertEquals($content1, $this->storage->getWsdl($service1));
        $this->assertEquals($content2, $this->storage->getWsdl($service2));
    }

    public function testSaveWsdlWithVariablesJson(): void
    {
        $serviceName = 'test_service';
        $url = 'http://example.com/service.wsdl';
        $content = '<definitions>Test WSDL</definitions>';
        $variables = '{"var1": "value1"}';

        $saved = $this->storage->saveWsdl($serviceName, $url, $content, $variables);
        $this->assertTrue($saved);

        // In memory storage non gestisce variablesJson, ma deve accettarlo
        $retrieved = $this->storage->getWsdl($serviceName);
        $this->assertEquals($content, $retrieved);
    }
}
