<?php

namespace OpenFiber\Dto;

use OpenFiber\Interfaces\DynamicDtoInterface;

/**
 * Abstract base class for Data Transfer Objects (DTOs) in OpenFiber.
 * 
 * This class consolidates the repetitive anonymous DTO classes found across
 * the codebase (examples/01-04, AsyncRetryWorker, etc.) into a single,
 * reusable implementation that follows the AGENTS.md principles of
 * abstraction, atomicity, and DRY (Don't Repeat Yourself).
 * 
 * @package OpenFiber\Dto
 * @author OpenFiber Team
 * @version 1.0.0
 * @since PHP 8.4
 */
abstract class BaseDto implements DynamicDtoInterface
{
    /**
     * The raw data array that this DTO encapsulates.
     * 
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * BaseDto constructor.
     * 
     * @param array<string, mixed> $data The data to encapsulate
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get a value from the encapsulated data by key.
     * 
     * Provides a safe way to access DTO properties with optional default values.
     * 
     * @param string $key The data key to retrieve
     * @param mixed $default The default value if key doesn't exist
     * @return mixed The value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if the DTO contains a specific key.
     * 
     * @param string $key The key to check for
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Convert the DTO to a SOAP payload array.
     * 
     * This method must be implemented by concrete DTO classes to provide
     * the specific SOAP structure required by the OpenFiber API.
     * 
     * @return array<string, mixed> The SOAP payload
     * @throws \InvalidArgumentException If the DTO data is invalid
     */
    abstract public function toSoapPayload(): array;

    /**
     * Validate the DTO data.
     * 
     * This method must be implemented by concrete DTO classes to perform
     * validation specific to the data structure and business rules.
     * 
     * @return bool True if valid, false otherwise
     */
    abstract public function validate(): bool;

    /**
     * Get the raw data array.
     * 
     * @return array<string, mixed> The raw data
     */
    public function getRawData(): array
    {
        return $this->data;
    }

    /**
     * Set the raw data array.
     * 
     * @param array<string, mixed> $data The raw data to set
     * @return self For method chaining
     */
    public function setRawData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
}
