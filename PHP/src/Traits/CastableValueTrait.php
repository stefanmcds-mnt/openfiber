<?php

namespace OpenFiber\Traits;

trait CastableValueTrait
{
    protected function castValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            return (int) $value;
        }
        
        if (is_string($value) && preg_match('/^\d+\.\d+$/', $value)) {
            return (float) $value;
        }
        
        if (is_string($value) && strtolower($value) === 'true') {
            return true;
        }
        
        if (is_string($value) && strtolower($value) === 'false') {
            return false;
        }
        
        return $value;
    }
}