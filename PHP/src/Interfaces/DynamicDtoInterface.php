<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface DynamicDtoInterface 
{
    public function toSoapPayload(): array;
}

