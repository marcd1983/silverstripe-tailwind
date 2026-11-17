<?php
    use SilverStripe\CMS\Model\SiteTree;
    use Silverstripe\AssetAdmin\Forms\UploadField;
	use Silverstripe\Assets\Image;
    use SilverStripe\Forms\ToggleCompositeField;
    use SilverStripe\Forms\CheckboxField;
    use SilverStripe\Forms\DropdownField;
    use SilverStripe\Forms\TextField;
    class Page extends SiteTree
    {
        private static $db = [
            'HideChildren' => 'Boolean',
            'HidePageTitle' => 'Boolean',
            'ChildGridColumns' => 'Enum("3,4,5,6", "4")',
            'Description' => 'Varchar(255)',
            'OpenInNew' => 'Boolean',
        ];

        private static $has_one = [
            'Photo' => Image::class,
        ];
        private static $owns = [
            'Photo',
        ];

        public function getCMSFields()
        {
            $fields = parent::getCMSFields();
            $fields->removeByName([
                'Photo',
                'ChildGridColumns',
                'HideChildren',
                'HidePageTitle',
                'Description',
                'OpenInNew',
            ]);
            $pageOptions = ToggleCompositeField::create('PageOptions', 'Page Options', [
                UploadField::create("Photo", "Page Image")
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('Uploads/Page Photos'),
                TextField::create('Description', 'Description'),
                DropdownField::create('ChildGridColumns', 'Child Grid Columns', [
                        '3' => '3 columns',
                        '4' => '4 columns',
                        '5' => '5 columns',
                        '6' => '6 columns',
                    ]),
                CheckboxField::create("HideChildren", "Hide child grid on this page"),
               	CheckboxField::create("HidePageTitle", "Hide Title Bar"),
                CheckboxField::create("OpenInNew", "Open this page's link in a new tab"),

            ])->setHeadingLevel(4);

            // Reorder the toggle sections added by core/extensions so they read:
            // Page Options, Mega Menu Options, Page SEO Settings, Metadata
            if ($metadata = $fields->fieldByName('Root.Main.Metadata')) {
                $fields->removeByName('Metadata');
                $fields->insertAfter('SEO', $metadata);
            }
            if ($fields->fieldByName('Root.Main.MegaMenuOptions')) {
                $fields->insertBefore('MegaMenuOptions', $pageOptions);
            } else {
                $fields->addFieldToTab('Root.Main', $pageOptions);
            }

            return $fields;
            
        }

    }    
