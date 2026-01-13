<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\Command;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elasticsearch\Bundle\Command\Utils\BooleanOptionResolverTrait;
use Elasticsearch\Bundle\Command\Utils\IndexSelectQuestionTrait;
use Elasticsearch\Connection\Connection;
use Elasticsearch\Mapping\Index;
use Elasticsearch\Mapping\MappingMetadataProvider;
use Elasticsearch\Mapping\Request\MetadataRequestFactory;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'elasticsearch:create-index',
    description: 'Create Elasticsearch index by mapping',
)]
final class CreateIndexCommand extends Command
{
    use BooleanOptionResolverTrait;
    use IndexSelectQuestionTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly MappingMetadataProvider $metadataProvider,
        private readonly MetadataRequestFactory $metadataRequestFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('re-create-indexes', '', InputOption::VALUE_REQUIRED, 'Re-create existing indexes (delete exists data)', false),
                new InputOption('if-not-exists', '', InputOption::VALUE_REQUIRED, 'Don\'t trigger an error, when the index already exists', false),
                new InputOption('select', '', InputOption::VALUE_REQUIRED, 'Select indexes for create', false),
                new InputOption('byName', '', InputOption::VALUE_REQUIRED, 'Index name without prefix for create'),
                new InputOption('byClassName', '', InputOption::VALUE_REQUIRED, 'Index class name for create'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command re-create/create elasticsearch indexes.


EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rowsFromProgress = $rows = [];
        $io = new SymfonyStyle($input, $output);
        try {
            $reCreateIndex = $this->resolveBoolOption($input, 're-create-indexes');
            $ifNotExists = $this->resolveBoolOption($input, 'if-not-exists');
            $select = $this->resolveBoolOption($input, 'select');
            $byName = $input->getOption('byName');
            $byClassName = $input->getOption('byClassName');
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($output->isVerbose()) {
            $rows = [
                ['<info>Class</>', '<info>Index name</info>', '<info>Result</info>'],
                new TableSeparator(),
            ];
        }

        try {
            switch (true) {
                case $byName !== null:
                    $rowsFromProgress[] = $this->processByName($ifNotExists, $reCreateIndex, $byName, $io, $output);
                    break;
                case $byClassName !== null:
                    $rowsFromProgress[] = $this->processByClassName($ifNotExists, $reCreateIndex, $byClassName, $io, $output);
                    break;
                case $select:
                    $rowsFromProgress = $this->processBySelect($ifNotExists, $reCreateIndex, $input, $io, $output);
                    break;
                default:
                    $rowsFromProgress = $this->processByDefault($ifNotExists, $reCreateIndex, $output);
                    break;
            }
        } catch (Exception $exception) {
            if ($output->isVerbose()) {
                $io->error($exception->getMessage());
            }
            return Command::FAILURE;
        }

        if ($output->isVerbose()) {
            $io->table([], array_merge($rows, ...$rowsFromProgress));
        }

        return Command::SUCCESS;
    }

    private function processByName(
        bool $ifNotExists,
        bool $reCreateIndex,
        string $byName,
        SymfonyStyle $io,
        OutputInterface $output
    ): array
    {
        foreach ($this->metadataProvider->getMappingMetadata()->getMetadata() as $index) {
            if ($index->getName() === $byName) {
                return $this->process($ifNotExists, $reCreateIndex, $index, $output);
            }
        }

        $io->error(sprintf('Index name "%s" not found.', $byName));
        throw new RuntimeException('Index name not found.');
    }

    private function processByClassName(
        bool $ifNotExists,
        bool $reCreateIndex,
        string $byClassName,
        SymfonyStyle $io,
        OutputInterface $output
    ): array
    {
        $index = $this->metadataProvider->getMappingMetadata()->getIndexByClasss($byClassName);
        if (!$index) {
            $io->error(sprintf('Index in class "%s" not found.', $byClassName));
            throw new RuntimeException('Index in class not found.');
        }

        return $this->process($ifNotExists, $reCreateIndex, $index, $output);
    }

    private function processBySelect(
        bool $ifNotExists,
        bool $reCreateIndex,
        InputInterface $input,
        SymfonyStyle $io,
        OutputInterface $output
    ): array
    {
        $rowsFromProgress = [];
        $helper = $this->getHelper('question');
        $classes = $this->createIndexSelectQuestion($this->metadataProvider, $helper, $input, $output);
        if (!is_array($classes)) {
            $io->error('Wrong selected classes. Please select at least one value.');
            throw new RuntimeException('Wrong selected classes. Please select at least one value.');
        }

        foreach ($classes as $class) {
            $index = $this->metadataProvider->getMappingMetadata()->getIndexByClasss($class);
            if (null === $index) {
                $io->error(sprintf('Index for class "%s" not found.', $class));
                throw new RuntimeException('Index in class not found.');
            }
            $rowsFromProgress[] = $this->process($ifNotExists, $reCreateIndex, $index, $output);
        }

        return $rowsFromProgress;
    }

    private function processByDefault(bool $ifNotExists, bool $reCreateIndex, OutputInterface $output): array
    {
        $rowsFromProgress = [];
        foreach ($this->metadataProvider->getMappingMetadata()->getMetadata() as $index) {
            $rowsFromProgress[] = $this->process($ifNotExists, $reCreateIndex, $index, $output);
        }

        return $rowsFromProgress;
    }

    private function process(bool $ifNotExists, bool $reCreateIndex, Index $index, OutputInterface $output): array
    {
        $indexPrefix = $this->connection->getIndexPrefix();
        $rows = [];
        if ($reCreateIndex) {
            if ($this->connection->hasIndex($index)) {
                $this->connection->deleteIndex($index);
            }
        }
        $request = $this->metadataRequestFactory->create($index);
        try {
            $this->connection->createIndex($request);
            if ($output->isVerbose()) {
                $rows[] = [
                    $index->getEntityClass(),
                    $index->getNameWithPrefix($indexPrefix),
                    new TableCell(
                        "created \xE2\x9C\x94",
                        [
                            'style' => new TableCellStyle([
                                'align' => 'center',
                            ])
                        ]
                    ),
                ];
            }
        } catch (ClientResponseException $exception) {
            if (false === $ifNotExists || !$this->connection->hasIndex($index)) {
                throw $exception;
            }
            if ($output->isVerbose()) {
                $rows[] = [
                    $index->getEntityClass(),
                    $index->getNameWithPrefix($indexPrefix),
                    new TableCell(
                        "skipped \xE2\x9C\x94",
                        [
                            'style' => new TableCellStyle([
                                'align' => 'center',
                            ])
                        ]
                    ),
                ];
            }
        }

        return $rows;
    }
}
