<?php

/*
 * This file is part of the jascha030/composer-scaffolder package.
 *
 * (c) Jascha van Aalst <contact@jaschavanaalst.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Jascha030\Scaffolder\Core\Rendering;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Exception\RenderingException;

use function is_string;
use function json_encode;
use function preg_replace_callback;
use function substr;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class TemplateRenderer
{
    private const TOKEN_PATTERN = '~\{\{\s*([a-zA-Z0-9_.\-]+)\s*\}\}~';

    public function __construct(private readonly AnswerBag $answers)
    {
    }

    public function render(string $content): string
    {
        $rendered = preg_replace_callback(self::TOKEN_PATTERN, fn (array $matches): string => $this->valueFor($matches[1]), $content);

        if (null === $rendered) {
            throw RenderingException::renderFailed();
        }

        return $rendered;
    }

    public function renderJson(string $content): string
    {
        $rendered = preg_replace_callback(self::TOKEN_PATTERN, fn (array $matches): string => $this->jsonEncodedValueFor($matches[1]), $content);

        if (null === $rendered) {
            throw RenderingException::renderFailed();
        }

        return $rendered;
    }

    private function valueFor(string $key): string
    {
        if (! $this->answers->has($key) || null === $this->answers->get($key)) {
            throw RenderingException::missingToken($key);
        }

        $value = $this->answers->get($key);

        if (! is_string($value)) {
            throw RenderingException::missingToken($key);
        }

        return $value;
    }

    private function jsonEncodedValueFor(string $key): string
    {
        $encoded = json_encode($this->valueFor($key), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (false === $encoded) {
            throw RenderingException::renderFailed();
        }

        return substr($encoded, 1, -1);
    }
}
