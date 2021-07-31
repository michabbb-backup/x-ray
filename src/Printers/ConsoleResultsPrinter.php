<?php

namespace Permafrost\RayScan\Printers;

use Permafrost\PhpCodeSearch\Results\FileSearchResults;
use Permafrost\RayScan\Printers\Highlighters\ConsoleColor;
use Symfony\Component\Console\Helper\Table;

class ConsoleResultsPrinter extends ResultsPrinter
{
    /** @var ConsoleColor|null */
    public $consoleColor = null;

    public function print(array $results): void
    {
        $this->printer()->consoleColor = $this->consoleColor;

        $this->output->writeln(" <fg=#3B82F6>❱</> scan complete.");

        if (count($results)) {
            $this->output->writeln('');
        }

        if (! $this->config->showSummary) {
            foreach ($results as $scanResult) {
                foreach ($scanResult->results as $result) {
                    $this->printer()->print($this->output, $result);
                }
            }
        }

        $this->printSummary($results);
    }

    public function printSummary(array $results): void
    {
        [$files, $functions] = $this->summarizeCalls($results);

        $totalCalls = array_sum(array_values($functions));
        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $this->output->writeln(" <fg=#169b3c>✔</> No references to ray were found.");
        }

        if ($totalFiles > 0) {
            if ($this->config->showSummary) {
                $this->renderSummaryTable($files);
                $this->output->writeln('');
            }

            if ($this->config->compactMode) {
                $this->output->writeln('');
            }

            $this->output->writeln(" <fg=#ef4444>❗</>Found {$totalCalls} references in {$totalFiles} files.");
        }
    }

    protected function summarizeCalls(array $results): array
    {
        $files = [];
        $functions = [];

        // count number of files and functions found
        /** @var FileSearchResults $scanResult */
        foreach($results as $scanResult) {
            foreach ($scanResult->results as $result) {
                if (!isset($files[$result->file()->filename])) {
                    $files[$result->file()->filename] = 0;
                }

                $files[$result->file()->filename]++;

                if (!isset($functions[$result->node->name()])) {
                    $functions[$result->node->name()] = 0;
                }

                $functions[$result->node->name()]++;
            }
        }

        return [$files, $functions];
    }

    protected function printer(): ResultPrinter
    {
        return $this->printer ?? new ConsoleResultPrinter($this->config);
    }

    protected function renderSummaryTable(array $fileCounts): void
    {
        $rows = [];

        foreach($fileCounts as $filename => $count) {
            $rows[] = [$this->makeFilenameRelative($filename), $count];
        }

        $table = new Table($this->output);

        $table
            ->setHeaders(['Filename ', 'Call Count '])
            ->setRows($rows);

        $table->render();
    }

    protected function makeFilenameRelative(string $filename): string
    {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, './', $filename);
    }
}
