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

namespace Jascha030\Scaffolder\Tests\Unit\Core;

use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Exception\RenderingException;
use Jascha030\Scaffolder\Core\Rendering\TemplateRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TemplateRenderer::class)]
final class TemplateRendererTest extends TestCase
{
    #[Test]
    public function itReplacesTokens(): void
    {
        $renderer = new TemplateRenderer(new AnswerBag([
            'package.name'      => 'acme/demo',
            'project.namespace' => 'Acme\Demo',
        ]));

        $result = $renderer->render('Name: {{ package.name }}, Namespace: {{ project.namespace }}');

        self::assertSame('Name: acme/demo, Namespace: Acme\Demo', $result);
    }

    #[Test]
    public function itRejectsMissingTokens(): void
    {
        $renderer = new TemplateRenderer(new AnswerBag([]));

        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('missing answer "package.name"');

        $renderer->render('{{ package.name }}');
    }

    #[Test]
    public function itRejectsNullAnswers(): void
    {
        $renderer = new TemplateRenderer(new AnswerBag(['package.name' => null]));

        $this->expectException(RenderingException::class);

        $renderer->render('{{ package.name }}');
    }
}
