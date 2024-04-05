<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\Command;

use Elasticsearch\Connection\Connection;
use Elasticsearch\Mapping\Index;
use Elasticsearch\Mapping\MappingMetadataProvider;
use Elasticsearch\Mapping\Request\MetadataRequestFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'elasticsearch:create-index',
    description: 'Create Elasticsearch index by mapping',
)]
final class CreateIndexCommand extends Command
{
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
                new InputOption('select', '', InputOption::VALUE_REQUIRED, 'Select indexes for create', false),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command re-create/create elasticsearch indexes.


EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rowsFromProgress = [];
        $io = new SymfonyStyle($input, $output);
        try {
            $reCreateIndex = $this->resolveBoolOption($input, 're-create-indexes');
            $select = $this->resolveBoolOption($input, 'select');
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

        if ($select) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'please select the class for which the index should be created (all by default)',
                $this->metadataProvider->getMappingMetadata()->getMetadata()->getKeys(),
                implode(',', $this->metadataProvider->getMappingMetadata()->getMetadata()->getKeys()),
            );
            $question->setMultiselect(true);

            $classes = $helper->ask($input, $output, $question);
            if (!is_array($classes)) {
                $io->error('Wrong selected classes. Please select at least one value');

                return Command::FAILURE;
            }
            foreach ($classes as $class) {
                $index = $this->metadataProvider->getMappingMetadata()->getIndexByClasss($class);
                if (null === $index) {
                    $io->error(sprintf('Index for class "%s" not found.', $class));

                    return Command::FAILURE;
                }
                $rowsFromProgress[] = $this->process($reCreateIndex, $index, $output);
            }
        } else {
            foreach ($this->metadataProvider->getMappingMetadata()->getMetadata() as $index) {
                $rowsFromProgress[] = $this->process($reCreateIndex, $index, $output);
            }
        }

        if ($output->isVerbose()) {
            $io->table([], array_merge($rows, ...$rowsFromProgress));
        }

        return 0;
    }

    private function resolveBoolOption(InputInterface $input, string $name): bool
    {
        $value = $input->getOption($name);
        if (!in_array($value, ['0', '1', 'true', 'false', false, true], true)) {
            throw new InvalidArgumentException(sprintf('Parameter %s has wrong value. Please enter 0 or 1.', $name));
        }

        return (bool)$value;
    }

    private function process(bool $reCreateIndex, Index $index, OutputInterface $output): array
    {
        $rows = [];
        if ($reCreateIndex) {
            if ($this->connection->hasIndex($index)) {
                $this->connection->deleteIndex($index);
            }
        }
        $request = $this->metadataRequestFactory->create($index);
        $this->connection->createIndex($request);
        if ($output->isVerbose()) {
            $rows[] = [
                $index->getEntityClass(),
                $index->getName(),
                new TableCell(
                    "\xE2\x9C\x94",
                    [
                        'style' => new TableCellStyle([
                            'align' => 'center',
                        ])
                    ]
                ),
            ];
        }

        return $rows;
    }
}
