<?php

namespace App\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class MigrateElementClassNamesTask extends BuildTask
{
    protected static string $commandName = 'MigrateElementClassNamesTask';

    protected string $title = 'Migrate Element ClassName values after namespace rename';

    protected static string $description = 'Updates ClassName in element tables after renaming ElementalContainer and ElementalList namespaces to ElementContainer and ElementList.';

    private array $mappings = [
        'Antlion\\ElementalContainer\\Model\\ElementContainer' => 'Antlion\\ElementContainer\\Model\\ElementContainer',
        'Antlion\\ElementalList\\Model\\ElementList'           => 'Antlion\\ElementList\\Model\\ElementList',
    ];

    // BaseElement uses table_name = 'Element'; versioned extension adds _Live and _Versions
    private array $tables = [
        'Element',
        'Element_Live',
        'Element_Versions',
    ];

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        foreach ($this->tables as $table) {
            if (!DB::get_schema()->hasTable($table)) {
                echo "Table {$table} does not exist — skipping.<br>" . PHP_EOL;
                continue;
            }

            foreach ($this->mappings as $old => $new) {
                $sql = sprintf(
                    'UPDATE "%s" SET "ClassName" = \'%s\' WHERE "ClassName" = \'%s\'',
                    $table,
                    $new,
                    $old
                );
                DB::query($sql);
                $affected = DB::affected_rows();
                echo "[{$table}] {$old} → {$new}: {$affected} row(s) updated.<br>" . PHP_EOL;
            }
        }

        echo "<strong>Done.</strong><br>" . PHP_EOL;

        return Command::SUCCESS;
    }
}
