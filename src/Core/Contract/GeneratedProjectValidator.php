<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Contract;

interface GeneratedProjectValidator
{
    public function validate(string $directory): void;
}
