<?php

use SilverStripe\Core\Environment;
use SilverStripe\ORM\Search\FulltextSearchable;
use SilverStripe\TinyMCE\TinyMCEConfig;

// The business's timezone, for every date and time the site shows or stores
// (store hours "Open now", ribbon schedules, LastEdited...). Set here rather
// than in php.ini so it holds on any server; override with SS_TIMEZONE.
date_default_timezone_set(Environment::getEnv('SS_TIMEZONE') ?: 'America/Chicago');

// Enables SiteTree/File MySQL FULLTEXT search and wires up $SearchForm on
// ContentController -- must run here rather than via YAML, since it also
// sets the static searchable-classes list SearchForm reads at runtime.
FulltextSearchable::enable();


/** @var TinyMCEConfig $cfg */
$cfg = TinyMCEConfig::get('cms');

// Every open-source plugin bundled with Silverstripe's TinyMCE (6.8) that
// suits CMS content. Silverstripe already enables anchor, table, emoticons,
// code, image, importcss, lists, autolink, searchreplace, visualblocks,
// wordcount and help, plus its own sslink/ssmedia/ssembed. Left out on
// purpose: link and media (sslink/ssembed replace them), save (the CMS has
// its own), autoresize (fights the CMS editor height), autosave (the CMS
// tracks unsaved changes and versions), template (removed in TinyMCE 7) and
// pagebreak (a print feature; inserts a comment that means nothing on the web).
$cfg->enablePlugins([
    'accordion', 'advlist', 'charmap', 'codesample', 'directionality', 'fullscreen',
    'insertdatetime', 'nonbreaking', 'preview', 'quickbars', 'visualchars',
]);

// Full menu bar: everything, including what isn't on the toolbars
$cfg->setOption('menubar', 'edit view insert format tools table help');

// One group per row: separators ('|') split the toolbar into button groups
$cfg->setButtonsForLine(1, [
    'styles', '|', // one dropdown: block types + the custom styles below
    'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'backcolor', 'removeformat', '|',
    'bullist', 'numlist', 'outdent', 'indent', '|',
    'alignleft', 'aligncenter', 'alignright', 'alignjustify',
]);

$cfg->setButtonsForLine(2, [
    'sslink', 'unlink', 'anchor', '|',
    'ssmedia', 'ssembed', 'table', 'accordion', 'codesample', '|',
    'charmap', 'emoticons', 'nonbreaking', 'insertdatetime', '|',
    'ltr', 'rtl', '|',
    'searchreplace', 'visualblocks', 'visualchars', 'code', 'preview', 'fullscreen', 'help',
]);

// Quickbars (contextual toolbars on selection / at caret)
$cfg->setOption('quickbars_selection_toolbar', 'bold italic underline | forecolor backcolor | sslink | styles | removeformat');
$cfg->setOption('quickbars_insert_toolbar', 'quicktable ssmedia ssembed accordion codesample hr');
$cfg->setOption('quickbars_image_toolbar', false); // ssmedia has its own image dialog

// Plugin settings
$cfg->setOption('codesample_languages', [
    ['text' => 'HTML/XML', 'value' => 'markup'],
    ['text' => 'CSS', 'value' => 'css'],
    ['text' => 'JavaScript', 'value' => 'javascript'],
    ['text' => 'PHP', 'value' => 'php'],
    ['text' => 'Shell', 'value' => 'bash'],
    ['text' => 'JSON', 'value' => 'json'],
    ['text' => 'Plain text', 'value' => 'none'],
]);
$cfg->setOption('insertdatetime_formats', ['%B %e, %Y', '%m/%d/%Y', '%I:%M %p', '%Y-%m-%d']);
$cfg->setOption('insertdatetime_element', true); // wraps dates in <time datetime="...">
$cfg->setOption('nonbreaking_wrap', false);      // plain &nbsp;, no editor-only wrapper span
$cfg->setOption('advlist_bullet_styles', 'default,circle,square');
$cfg->setOption('advlist_number_styles', 'default,lower-alpha,upper-alpha,lower-roman,upper-roman');

// Useful editor options
$cfg->setOption('image_caption', true);
$cfg->setOption('image_advtab', true);
$cfg->setOption('browser_spellcheck', true);
$cfg->setOption('branding', false);
$cfg->setOption('statusbar', true);
$cfg->setOption('elementpath', true);
$cfg->setOption('resize', true);

// Additions to Silverstripe's default allow-list (also enforced when saving).
// An entry here REPLACES the default rule for that element, so only list
// elements the defaults lack, or a default rule with attributes added (img:
// + loading). p, a, h1-h6, div, span and table cells are already allowed with
// more attributes (id for anchors, dir, style) than a redefinition would keep.
// Never use [*]: it lets event handlers (onclick...) through the save filter.
$cfg->setOption('extended_valid_elements', implode(',', [
    'mark[class|style]',
    'img[id|dir|longdesc|usemap|class|src|border|alt=|title|hspace|vspace|width|height|align|name|data*|loading]',
    // Markup from the plugins above: accordion, codesample, insertdatetime,
    // image captions, advlist list styles
    'details[class|open],summary[class],code[class],time[datetime|class]',
    'figure[class|style],figcaption[class],ul[class|style],ol[class|style]',
]));

// Content CSS + "Styles" menu use the silverwind theme. Its classes are
// safelisted in the theme's src/css/theme.css (editor content lives in the
// database, so Tailwind can't discover them by scanning templates).
$cfg->setContentCSS(['/_resources/themes/silverwind/dist/editor.css']);

// Block types for Format > Blocks. No Heading 1: the page title is the h1.
$blockFormats = [
    ['title' => 'Paragraph', 'format' => 'p'],
    ['title' => 'Heading 2', 'format' => 'h2'],
    ['title' => 'Heading 3', 'format' => 'h3'],
    ['title' => 'Heading 4', 'format' => 'h4'],
    ['title' => 'Quote', 'format' => 'blockquote'],
];
$cfg->setOption('block_formats', implode('; ', array_map(
    fn ($f) => $f['title'] . '=' . $f['format'],
    $blockFormats
)));

$cfg->setOption('style_formats', [
    ['title' => 'Text', 'items' => $blockFormats],
    [
        'title' => 'Text color',
        'items' => [
            ['title' => 'Primary', 'inline' => 'span', 'classes' => 'text-primary', 'exact' => true],
            ['title' => 'Accent',  'inline' => 'span', 'classes' => 'text-accent', 'exact' => true],
            ['title' => 'Muted',   'inline' => 'span', 'classes' => 'opacity-70', 'exact' => true],
        ],
    ],
    [
        'title' => 'Highlight',
        'items' => [
            ['title' => 'Yellow', 'inline' => 'mark', 'classes' => 'bg-warning/40', 'exact' => true],
            ['title' => 'Blue',   'inline' => 'mark', 'classes' => 'bg-info/30',    'exact' => true],
        ],
    ],
    [
        'title' => 'Buttons',
        'items' => [
            ['title' => 'Primary Button',   'selector' => 'a', 'classes' => 'btn btn-primary'],
            ['title' => 'Secondary Button', 'selector' => 'a', 'classes' => 'btn btn-secondary'],
            ['title' => 'Outline Button',   'selector' => 'a', 'classes' => 'btn btn-outline'],
        ],
    ],
]);

// Optional: fixed color palette for forecolor/backcolor
$cfg->setOption('color_map', [
    '000000','Black','ffffff','White',
    'e53935','Red','fb8c00','Orange','fdd835','Yellow',
    '43a047','Green','1e88e5','Blue','8e24aa','Purple',
]);
