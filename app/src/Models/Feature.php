<?php

namespace App\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\AssetAdmin\Forms\UploadField;

class Feature extends DataObject
{
    private static $table_name = 'Feature';

    private static $singular_name = 'feature';
    private static $plural_name   = 'features';
    private static $description   = 'feature';

    private static $db = [
        'Title'   => 'Varchar(255)',
        'Content' => 'HTMLText',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $has_many = [
        'Links' => Link::class . '.Owner',
    ];

    private static $owns = [
        'Image',
        'Links',
    ];

    // default sort can be Title now (since Sort is no longer on Feature)
    private static $default_sort = 'Title ASC';

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        // Remove scaffolding you don't want
        $fields->removeByName(['Title', 'Content', 'Image', 'Links']);

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title', 'Title')->setMaxLength(255),
            HTMLEditorField::create('Content', 'Content')->setRows(8),
            UploadField::create('Image', 'Image')
                ->setFolderName('Uploads/Features')
                ->setAllowedFileCategories('image/supported'),
            MultiLinkField::create('Links', 'Button Links'),
        ]);

        return $fields;
    }
}
