<?php
declare(strict_types=1);

namespace OpenFiber;

interface DynamicDtoInterface 
{
    public function toSoapPayload(): array;
}

