<?php

namespace App\Tasks;

use Antlion\ElementCard\Elements\ElementCard;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Repoints LinkField_Link rows that belong to ElementCard records from the old
 * ElementLink has_one relation to the new Links has_many relation.
 *
 * IMPORTANT: run this BEFORE dev/build is run against the updated ElementCard config
 * (the one that removes the ElementLink has_one). This task reads ElementCard.ElementLinkID
 * as the source of truth, and dev/build will drop that column once the has_one is removed.
 *
 * Also note: existing data had Link.OwnerID values that did not match the ElementCard
 * actually pointing at them via ElementLinkID (likely from duplicating cards in the CMS
 * before this migration). This task re-derives OwnerID/OwnerClass from ElementLinkID
 * rather than trusting the Link row's existing Owner* columns.
 */
class MigrateElementCardLinksTask extends BuildTask
{
    protected static string $commandName = 'MigrateElementCardLinksTask';

    protected string $title = 'Migrate ElementCard links from has_one ElementLink to has_many Links';

    protected static string $description = 'Repoints LinkField_Link rows owned by ElementCard from the old ElementLink '
        . 'has_one relation to the new Links has_many relation, correcting OwnerID along the way. '
        . 'Must be run before dev/build removes the ElementCard.ElementLinkID column.';

    // Stage/live pairs of [ElementCard table, LinkField_Link table].
    private array $stages = [
        ['ElementCard', 'LinkField_Link'],
        ['ElementCard_Live', 'LinkField_Link_Live'],
    ];

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $ownerClass = ElementCard::class;

        foreach ($this->stages as [$cardTable, $linkTable]) {
            if (!DB::get_schema()->hasTable($cardTable) || !DB::get_schema()->hasTable($linkTable)) {
                echo "Table {$cardTable} or {$linkTable} does not exist — skipping.<br>" . PHP_EOL;
                continue;
            }

            if (!DB::get_schema()->hasField($cardTable, 'ElementLinkID')) {
                echo "{$cardTable}.ElementLinkID no longer exists — has dev/build already run? Skipping.<br>"
                    . PHP_EOL;
                continue;
            }

            // OwnerClass is a DBClassName column (backed by an ENUM), and its value contains
            // backslashes — building it into the raw SQL text is unsafe because MySQL's
            // backslash-escape parsing strips them, so the value silently fails to match any
            // enum member and gets stored as ''. Bind it as a prepared statement parameter instead.
            $sql = sprintf(
                'UPDATE "%1$s" AS "Link"
                INNER JOIN "%2$s" AS "Card" ON "Card"."ElementLinkID" = "Link"."ID"
                SET "Link"."OwnerID" = "Card"."ID",
                    "Link"."OwnerClass" = ?,
                    "Link"."OwnerRelation" = ?
                WHERE "Card"."ElementLinkID" > 0',
                $linkTable,
                $cardTable
            );
            DB::prepared_query($sql, [$ownerClass, 'Links']);
            $affected = DB::affected_rows();
            echo "[{$linkTable}] repointed to Links relation via {$cardTable}.ElementLinkID: "
                . "{$affected} row(s) updated.<br>" . PHP_EOL;
        }

        echo '<strong>Done.</strong> Note: LinkField_Link_Versions was intentionally left untouched — '
            . 'reverting an ElementCard to a version prior to this migration will not restore its old link. '
            . 'Run dev/build now if you have not already.<br>' . PHP_EOL;

        return Command::SUCCESS;
    }
}
