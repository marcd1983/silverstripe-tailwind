<?php

namespace Elements;

use DNADesign\Elemental\Models\BaseElement;
use App\Models\Feature;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class ElementFeatures extends BaseElement
{
    private static $icon = 'font-icon-block-award';
    private static $table_name = 'ElementFeatures';

    private static $db = [
        'Content' => 'HTMLText',
    ];

    private static $many_many = [
    'Features' => Feature::class,
    ];

    private static $many_many_extraFields = [
        'Features' => [
            'LinkSort' => 'Int',
        ],
    ];

    private static $owns = [
        // IMPORTANT: do NOT "own" Features if you want them reusable.
        // Keep only things ElementFeatures actually owns (usually none here).
    ];

    private static $inline_editable = false;

    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Content'] = _t(__CLASS__ . '.ContentLabel', 'Intro');
        $labels['Features'] = _t(__CLASS__ . '.FeaturesLabel', 'Features');
        return $labels;
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {

            // Make sure Content exists then tweak it
            $content = $fields->dataFieldByName('Content');
            if ($content) {
                $content->setDescription(_t(
                    __CLASS__ . '.ContentDescription',
                    'Optional. Add introductory copy to your features.'
                ))->setRows(5);
            }

            if ($this->ID) {
                /** @var GridField $grid */
                $grid = $fields->dataFieldByName('Features');
                if ($grid) {
                    $grid->setTitle($this->fieldLabel('Features'));

                    $config = $grid->getConfig();

                    // Per-element drag sort
                    $config->addComponent(new GridFieldOrderableRows('LinkSort'));

                    // Keep Add Existing enabled (so you can select from library)
                    $auto = $config->getComponentByType(GridFieldAddExistingAutocompleter::class);
                    if ($auto) {
                        $auto->setPlaceholderText('Search existing features to link…');
                        // Optional: restrict search fields if you want
                        // $auto->setSearchFields(['Title']);
                    }

                    /**
                     * Important UX choice:
                     * - Keeping GridFieldDeleteAction lets users UNLINK from this element.
                     *   It does NOT delete the Feature record; it removes the relation row.
                     * - Removing it would force them to open each Feature and un-assign elsewhere.
                     */
                    // Do NOT remove GridFieldDeleteAction (keep unlink)
                    // $config->removeComponentsByType(GridFieldDeleteAction::class);

                    // Move the grid where you want it
                    $fields->removeByName('Features');
                    $fields->addFieldToTab('Root.Main', $grid);
                }
            }
        });

        return parent::getCMSFields();
    }


    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Features');
    }
}
