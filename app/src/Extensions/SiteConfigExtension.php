<?php
namespace App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Control\Director;
use App\Models\RibbonCta;

class SiteConfigExtension extends Extension
{
    private static $db = [
        'HeadScripts'   => 'Text',    // for <head>
        'BodyScripts'   => 'Text',
        'FooterScripts'   => 'Text',     // top of <body>, e.g. GTM noscript
        // Global social URLs (used for sameAs and header/footer)
        'Facebook'      => 'Varchar(512)',
        'LinkedIn'      => 'Varchar(512)',
        'Instagram'     => 'Varchar(512)',
        'Youtube'       => 'Varchar(512)',
        'Twitter'       => 'Varchar(128)',
    ];

    private static $has_one = [
        'SiteLogo'       => Image::class,   // header + Organization schema
        'FooterRibbon'   => RibbonCta::class, // site-wide default; set from the Ribbon CTAs admin
    ];

    public function updateCMSFields(FieldList $fields): void
    {   
        $fields->addFieldsToTab('Root.Main', [
            UploadField::create('SiteLogo', 'Site logo')
            ->setFolderName('Uploads/SiteLogo'),
        ]);

        // FooterRibbon (has_one) is edited on the ribbon itself: Ribbon CTAs admin,
        // "Show above the footer on every page". Keep it off this form.
        $fields->removeByName(['FooterRibbon', 'FooterRibbonID']);

        $fields->addFieldsToTab('Root.Social', [
            TextField::create('Facebook', 'Facebook URL'),
            TextField::create('LinkedIn', 'LinkedIn URL'),
            TextField::create('Instagram', 'Instagram URL'),
            TextField::create('Youtube', 'YouTube URL'),
            TextField::create('Twitter', 'Twitter URL/handle'),
            // MultiLinkField::create('SocialLinks', 'Additional Social Links'),
        ]);
        $fields->addFieldsToTab('Root.Scripts', [
            TextAreaField::create('HeadScripts', 'Head scripts'),
            TextAreaField::create('BodyScripts', 'Body scripts'),
            TextAreaField::create('FooterScripts', 'Footer scripts'),
        ]);
    }

    /**
     * schema.org Organization node for the site-wide JSON-LD graph (consumed
     * by PageSeoExtension::SchemaJSON()). Its @id ("{base}#org") is what the
     * WebSite node's "publisher" points at.
     */
    public function getOrganizationSchemaNode(): array
    {
        $config = $this->getOwner();
        $base   = rtrim(Director::absoluteBaseURL(), '/') . '/';

        if (!$config->Title) {
            return [];
        }

        $node = [
            '@type' => 'Organization',
            '@id'   => $base . '#org',
            'name'  => $config->Title,
            'url'   => $base,
        ];

        $logo = $config->SiteLogo();
        if ($logo && $logo->exists()) {
            $node['logo'] = $logo->getAbsoluteURL();
        }

        $sameAs = array_values(array_filter([
            $config->Facebook,
            $config->LinkedIn,
            $config->Instagram,
            $config->Youtube,
            $this->twitterURL((string) $config->Twitter),
        ]));
        if ($sameAs) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    /**
     * The Twitter field accepts either a URL or a handle ("@acme" / "acme").
     */
    private function twitterURL(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return 'https://x.com/' . ltrim($value, '@');
    }

}
