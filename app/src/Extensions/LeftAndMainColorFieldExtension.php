<?php

namespace App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Loads TractorCow colour-picker assets globally so they are available when
 * element edit forms are loaded via AJAX/React FormSchema (inline editing).
 *
 * The standard colorfield.js targets 'input.colorfield', but in a React-rendered
 * form the 'colorfield' class is placed on the *holder* div (via schema extraClass),
 * not the input itself. This extension loads a custom init script that uses the
 * descendant selector '.colorfield input' instead.
 */
class LeftAndMainColorFieldExtension extends Extension
{
    public function onAfterInit(): void
    {
        Requirements::javascript('tractorcow/silverstripe-colorpicker: client/javascript/colorpicker.js');
        Requirements::css('tractorcow/silverstripe-colorpicker: client/css/colorpicker.css');
        Requirements::customScript($this->colorFieldInitScript(), 'colorfield-holder-init');
    }

    private function colorFieldInitScript(): string
    {
        return <<<'JS'
(function ($) {
    $.entwine('ss', function ($) {
        // Target the input inside a holder that carries the 'colorfield' class.
        // This works in both contexts:
        //   - React/AJAX form: holder gets 'colorfield' from schema extraClass
        //   - Traditional PHP form: holder gets it from extraClass AND input gets it
        //     from Field(); we guard against double-init with the data flag below.
        $('.colorfield input').entwine({
            onmatch: function () {
                if ($(this).data('colorpicker-init')) {
                    return;
                }
                $(this).data('colorpicker-init', true);

                var self = $(this);

                var calcLuminance = function (rgb) {
                    return Math.min(1, Math.max(0,
                        0.2126 * (rgb.r / 255) +
                        0.7152 * (rgb.g / 255) +
                        0.0722 * (rgb.b / 255)
                    ));
                };

                // Set initial background from saved value
                var hex = (self.val() || '').replace('#', '');
                if (hex.length === 6) {
                    var lum = calcLuminance({
                        r: parseInt(hex.substr(0, 2), 16),
                        g: parseInt(hex.substr(2, 2), 16),
                        b: parseInt(hex.substr(4, 2), 16)
                    });
                    self.css({
                        backgroundImage: 'none',
                        backgroundColor: '#' + hex,
                        color: lum > 0.5 ? '#000000' : '#ffffff'
                    });
                } else {
                    self.css({ backgroundImage: 'none', backgroundColor: '#ffffff', color: '#000000' });
                }

                self.ColorPicker({
                    onSubmit: function (hsb, hex, rgb) {
                        var col = calcLuminance(rgb) > 0.5 ? '#000000' : '#ffffff';
                        self.val(hex).css({ color: col, backgroundColor: '#' + hex });
                    },
                    onBeforeShow: function () {
                        $(this).ColorPickerSetColor(this.value);
                    },
                    onChange: function (hsb, hex, rgb) {
                        var col = calcLuminance(rgb) > 0.5 ? '#000000' : '#ffffff';
                        self.val(hex).css({ color: col, backgroundColor: '#' + hex });
                    }
                });
            },
            onkeyup: function () {
                $(this).ColorPickerSetColor($(this).val());
            }
        });
    });
}(jQuery));
JS;
    }
}
