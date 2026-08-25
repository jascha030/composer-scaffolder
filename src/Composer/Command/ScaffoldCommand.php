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

namespace Jascha030\Scaffolder\Composer\Command;

use Composer\Command\BaseCommand;
use Jascha030\Scaffolder\Composer\Console\SymfonyAnswerProvider;
use Jascha030\Scaffolder\Composer\Template\LocalTemplateSource;
use Jascha030\Scaffolder\Composer\Validation\ComposerProjectInspector;
use Jascha030\Scaffolder\Core\Answer\AnswerBag;
use Jascha030\Scaffolder\Core\Exception\InvalidAnswerException;
use Jascha030\Scaffolder\Core\Manifest\ManifestLoader;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlan;
use Jascha030\Scaffolder\Core\Planning\ScaffoldPlanner;
use Jascha030\Scaffolder\Core\Scaffolder;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_key_exists;
use function is_array;
use function is_string;
use function sprintf;
use function substr;

final class ScaffoldCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scaffold')
            ->setDescription('Generate a Composer project from a local scaffold-template package.')
            ->addArgument('template', InputArgument::REQUIRED, 'Path to a local scaffold-template package')
            ->addArgument('directory', InputArgument::REQUIRED, 'Destination directory for the generated project')
            ->addOption('set', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Predefined answer (key=value)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate and print the operation plan without writing files')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Allow replacing an existing empty destination');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $templatePath = $this->stringArgument($input, 'template');
        $destination  = $this->stringArgument($input, 'directory');
        $predefined   = $this->parsePredefinedAnswers($this->stringArrayOption($input, 'set'));

        $template = (new LocalTemplateSource())->load($templatePath);
        $manifest = (new ManifestLoader())->load($template->manifestPath);
        $provider = new SymfonyAnswerProvider($input, $output, $this->getQuestionHelper());
        $engine   = new Scaffolder($provider, new ScaffoldPlanner(), new ComposerProjectInspector());
        $dryRun   = (bool) $input->getOption('dry-run');

        $plan = $engine->scaffold(
            $template,
            $manifest,
            $destination,
            $predefined,
            $dryRun,
            (bool) $input->getOption('force'),
        );

        if ($dryRun) {
            $this->renderPlan($plan, $output);
        } else {
            $output->writeln(sprintf('<info>Generated project in %s</info>', $plan->destination));
        }

        return self::SUCCESS;
    }

    /** @param list<string> $values */
    private function parsePredefinedAnswers(array $values): AnswerBag
    {
        $answers = [];

        foreach ($values as $value) {
            $position = strpos($value, '=');

            if (false === $position || 0 === $position) {
                throw InvalidAnswerException::malformedSet($value);
            }

            $key         = substr($value, 0, $position);
            $answerValue = substr($value, $position + 1);

            if (array_key_exists($key, $answers)) {
                throw InvalidAnswerException::duplicateKey($key);
            }

            $answers[$key] = $answerValue;
        }

        return new AnswerBag($answers);
    }

    private function getQuestionHelper(): QuestionHelper
    {
        $helper = $this->getHelper('question');

        if (! $helper instanceof QuestionHelper) {
            throw new InvalidArgumentException('The "question" helper is not available.');
        }

        return $helper;
    }

    private function stringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);

        if (! is_string($value) || '' === $value) {
            throw new InvalidArgumentException(sprintf('Argument "%s" must be a non-empty string.', $name));
        }

        return $value;
    }

    /** @return list<string> */
    private function stringArrayOption(InputInterface $input, string $name): array
    {
        $value = $input->getOption($name);

        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf('Option "%s" must be an array.', $name));
        }

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new InvalidArgumentException(sprintf('Each value of option "%s" must be a string.', $name));
            }
        }

        /** @var list<string> $value */
        return $value;
    }

    private function renderPlan(ScaffoldPlan $plan, OutputInterface $output): void
    {
        $output->writeln('<info>Planned operations:</info>');

        foreach ($plan->operations as $operation) {
            $output->writeln(sprintf('  [%s] %s -> %s', $operation->mode->value, $operation->source, $operation->target));
        }
    }
}
