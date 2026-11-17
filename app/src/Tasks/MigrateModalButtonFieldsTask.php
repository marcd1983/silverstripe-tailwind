<?php

namespace App\Tasks;

use Antlion\ElementModal\Model\ModalButton;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Migrates element-modal button data from the old scalar fields
 * (Element.ModalButtonLabel / Element.ModalButtonColor, added by
 * ModalButtonFieldsExtension) to the new ModalButton has_one relation, which
 * points at a ModalButton (a silverstripe/linkfield Link subclass).
 *
 * ORDER OF OPERATIONS:
 *   1. Deploy the code change (has_one ModalButton => ModalButton).
 *   2. Run dev/build. This creates Element.ModalButtonID, the
 *      ModalButton table and the ModalButton rows in
 *      LinkField_Link. dev/build does NOT drop the old ModalButtonLabel /
 *      ModalButtonColor columns, so they are still readable here.
 *   3. Run this task (dev/tasks/MigrateModalButtonFieldsTask).
 *   4. Once verified, drop the obsolete columns manually.
 *
 * Idempotent: rows that already have a ModalButtonID are skipped.
 *
 * The old ModalButtonColor vocabulary is mapped onto the project's shared
 * button-style vocabulary (App\Extensions\LinkFieldExtension::ClassToken):
 *   primary/secondary/alert/warning/success  -> same token
 *   accent/info/light/dark/default/(blank)    -> no token (default styling)
 */
class MigrateModalButtonFieldsTask extends BuildTask
{
    protected static string $commandName = 'MigrateModalButtonFieldsTask';

    protected string $title = 'Migrate element-modal button fields to a ModalButton relation';

    protected static string $description = 'Ports Element.ModalButtonLabel / ModalButtonColor into the new '
        . 'ModalButton has_one (ModalButton). Run after dev/build, before the obsolete columns are dropped.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $schema = DB::get_schema();

        if (!$schema->hasTable('Element')) {
            $output->writeln('<error>Element table does not exist — nothing to do.</error>');
            return Command::SUCCESS;
        }

        if (!$schema->hasField('Element', 'ModalButtonLabel') && !$schema->hasField('Element', 'ModalButtonColor')) {
            $output->writeln('Element.ModalButtonLabel / ModalButtonColor no longer exist — '
                . 'migration already done (or columns dropped). Skipping.');
            return Command::SUCCESS;
        }

        if (!$schema->hasField('Element', 'ModalButtonID')) {
            $output->writeln('<error>Element.ModalButtonID does not exist — run dev/build first.</error>');
            return Command::FAILURE;
        }

        $hasClassToken = $schema->hasField('LinkField_Link', 'ClassToken');
        if (!$hasClassToken) {
            $output->writeln('<comment>LinkField_Link.ClassToken not found — button colours will not be migrated '
                . '(labels still will).</comment>');
        }

        $rows = DB::query(
            'SELECT "ID", "ClassName", "ModalButtonLabel", "ModalButtonColor"
             FROM "Element"
             WHERE ("ModalButtonID" IS NULL OR "ModalButtonID" = 0)
               AND (
                   ("ModalButtonLabel" IS NOT NULL AND "ModalButtonLabel" != \'\')
                   OR ("ModalButtonColor" IS NOT NULL AND "ModalButtonColor" != \'\' AND "ModalButtonColor" != \'default\')
               )'
        );

        $migrated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $elementId = (int) $row['ID'];
            $ownerClass = (string) $row['ClassName'];
            $label = trim((string) ($row['ModalButtonLabel'] ?? ''));
            $token = $this->mapColor((string) ($row['ModalButtonColor'] ?? ''));

            if (!class_exists($ownerClass)) {
                $output->writeln("<comment>Element #{$elementId}: unknown ClassName '{$ownerClass}' — skipped.</comment>");
                $skipped++;
                continue;
            }

            $link = ModalButton::create();
            $link->LinkText = $label;
            $link->OwnerID = $elementId;
            $link->OwnerClass = $ownerClass;
            $link->OwnerRelation = 'ModalButton';

            if ($hasClassToken && $token !== '') {
                $link->ClassToken = $token;
            }

            $link->write();
            if ($link->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $link->publishSingle();
            }

            // Point the element at the new link on both stages. Versions tables are
            // intentionally left untouched (reverting to an older version will not
            // restore the button), matching MigrateElementCardLinksTask.
            DB::prepared_query('UPDATE "Element" SET "ModalButtonID" = ? WHERE "ID" = ?', [$link->ID, $elementId]);
            if ($schema->hasTable('Element_Live') && $schema->hasField('Element_Live', 'ModalButtonID')) {
                DB::prepared_query('UPDATE "Element_Live" SET "ModalButtonID" = ? WHERE "ID" = ?', [$link->ID, $elementId]);
            }

            $output->writeln(sprintf(
                'Element #%d (%s): ModalButton #%d created — label "%s", token "%s".',
                $elementId,
                $ownerClass,
                $link->ID,
                $label,
                $token ?: '(none)'
            ));
            $migrated++;
        }

        $output->writeln("<info>Done. {$migrated} migrated, {$skipped} skipped.</info>");
        $output->writeln('Verify in the CMS, then drop the obsolete Element.ModalButtonLabel / '
            . 'Element.ModalButtonColor columns.');

        return Command::SUCCESS;
    }

    private function mapColor(string $old): string
    {
        return match ($old) {
            'primary', 'secondary', 'alert', 'warning', 'success' => $old,
            default => '',
        };
    }
}
