<?php

declare(strict_types=1);

namespace Jascha030\Scaffolder\Composer\Validation;

use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Jascha030\Scaffolder\Core\Contract\GeneratedProjectValidator;
use Jascha030\Scaffolder\Core\Exception\GeneratedProjectValidationException;
use Throwable;

final class ComposerProjectValidator implements GeneratedProjectValidator
{
    public function validate(string $directory): void
    {
        $path = $directory . '/composer.json';

        if (! is_file($path)) {
            throw GeneratedProjectValidationException::invalid($path, ['The file does not exist.']);
        }

        try {
            (new JsonFile($path))->validateSchema(JsonFile::LAX_SCHEMA);
        } catch (JsonValidationException $exception) {
            throw GeneratedProjectValidationException::invalid($path, $exception->getErrors());
        } catch (Throwable $exception) {
            throw GeneratedProjectValidationException::invalid($path, [$exception->getMessage()]);
        }
    }
}
