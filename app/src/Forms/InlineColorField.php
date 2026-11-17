<?php

namespace App\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use TractorCow\Colorpicker\Forms\ColorField;

/**
 * A TractorCow ColorField variant that works in SS5 React inline element editing.
 *
 * TractorCow's ColorField adds the 'colorfield' CSS class inside Field(), which is
 * never called in the React/FormSchema AJAX context. This subclass adds the class in
 * the constructor so it appears in the schema's extraClass and lands on the field
 * holder div — enabling the '.colorfield input' entwine selector used by
 * LeftAndMainColorFieldExtension to initialise the jQuery colour picker.
 */
class InlineColorField extends ColorField
{
    public function __construct($name, $title = null, $value = '')
    {
        parent::__construct($name, $title, $value);
        $this->addExtraClass('colorfield');
    }

    public function Field($properties = [])
    {
        // Requirements are loaded globally via LeftAndMainColorFieldExtension.
        // Only load the CSS here (idempotent — Requirements deduplicates).
        Requirements::css('tractorcow/silverstripe-colorpicker: client/css/colorpicker.css');

        $this->setAttribute('style', sprintf(
            'background-image: none; background-color: %s; %s',
            ($this->value ? '#' . $this->value : '#ffffff'),
            $this->getTextColorStyle()
        ));

        // Skip ColorField::Field() to avoid loading colorfield.js (handled globally).
        // TextField does not override Field(), so this goes straight to the base renderer.
        return FormField::Field($properties);
    }
}
