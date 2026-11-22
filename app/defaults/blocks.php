<?php
/**
 * Templates for the sections an admin can add to any page with
 * "+ Add a section" in the live editor. Each new section is a copy of one of these.
 *
 * Common keys: type, uid (set on creation), visible, bg (white | gray | dark | brand), anchor
 */
return [
    'text' => [
        'label' => 'Text',
        'hint' => 'Heading with a paragraph of formatted text.',
        'data' => [
            'type' => 'text', 'visible' => true, 'bg' => 'white', 'align' => 'center', 'anchor' => '',
            'eyebrow' => 'New section',
            'title' => 'Your heading',
            'highlight' => 'goes here',
            'subtitle' => '',
            'body' => '<p>Write your text here. Select words to make them <strong>bold</strong> or add a link.</p>',
        ],
    ],
    'image_text' => [
        'label' => 'Image + text',
        'hint' => 'Photo on one side, heading, text and a button on the other.',
        'data' => [
            'type' => 'image_text', 'visible' => true, 'bg' => 'gray', 'image_side' => 'left', 'anchor' => '',
            'image' => ['src' => '/images/about-us/who-we-are.webp', 'alt' => ''],
            'eyebrow' => 'Highlight',
            'title' => 'Tell your story',
            'highlight' => 'with a picture',
            'body' => '<p>Describe your project, product or capability here. Replace the photo by clicking the pencil on it.</p>',
            'button' => ['label' => 'Send Your Enquiry', 'href' => '/contact/'],
        ],
    ],
    'gallery' => [
        'label' => 'Photo gallery',
        'hint' => 'Grid of photos with captions; click opens a full-screen view.',
        'data' => [
            'type' => 'gallery', 'visible' => true, 'bg' => 'white', 'columns' => '3', 'anchor' => '',
            'eyebrow' => 'Gallery',
            'title' => 'Project',
            'highlight' => 'photos',
            'subtitle' => '',
            'items' => [
                ['image' => ['src' => '/images/about-us/manufacturing-facility/factory.webp', 'alt' => ''], 'caption' => 'Factory'],
                ['image' => ['src' => '/images/about-us/manufacturing-facility/fabrication.webp', 'alt' => ''], 'caption' => 'Fabrication'],
                ['image' => ['src' => '/images/about-us/manufacturing-facility/assembly.webp', 'alt' => ''], 'caption' => 'Assembly'],
            ],
        ],
    ],
    'cards' => [
        'label' => 'Cards',
        'hint' => 'Cards with image, title, text and a link — for products, team, benefits…',
        'data' => [
            'type' => 'cards', 'visible' => true, 'bg' => 'gray', 'columns' => '3', 'anchor' => '',
            'eyebrow' => 'Highlights',
            'title' => 'What we',
            'highlight' => 'offer',
            'subtitle' => '',
            'link_label' => 'Learn more',
            'items' => [
                ['image' => ['src' => '/images/services/solvent-extraction.webp', 'alt' => ''], 'title' => 'Card title', 'text' => 'Short description of this item.', 'href' => '/services/'],
                ['image' => ['src' => '/images/services/oil-refinery.webp', 'alt' => ''], 'title' => 'Card title', 'text' => 'Short description of this item.', 'href' => '/services/'],
                ['image' => ['src' => '/images/services/fractionation.webp', 'alt' => ''], 'title' => 'Card title', 'text' => 'Short description of this item.', 'href' => '/services/'],
            ],
        ],
    ],
    'cta' => [
        'label' => 'Call to action',
        'hint' => 'Dark banner with a big heading and buttons.',
        'data' => [
            'type' => 'cta', 'visible' => true, 'anchor' => '',
            'eyebrow' => "Let's Build Together",
            'title' => 'Ready to start your',
            'highlight' => 'next project?',
            'subtitle' => 'Talk to our engineering team about your plant.',
            'primary' => ['label' => 'Send Your Enquiry', 'href' => '/contact/'],
        ],
    ],
    'html' => [
        'label' => 'Custom HTML',
        'hint' => 'Design anything with your own HTML and CSS.',
        'data' => [
            'type' => 'html', 'visible' => true, 'bg' => 'white', 'contained' => true, 'anchor' => '',
            'html' => '<div class="custom-box">' . "\n"
                . '  <h2>Your custom section</h2>' . "\n"
                . '  <p>Edit this HTML and CSS with the ✎ pencil. Buttons can open pages or popups:</p>' . "\n"
                . '  <a class="btn" href="#popup-quick-contact">Open the contact popup</a>' . "\n"
                . '</div>',
            'css' => ".custom-box { padding: 40px; border-radius: 24px; text-align: center;\n  background: linear-gradient(120deg, #e0f2fe, #ecfccb); }\n"
                . ".custom-box h2 { font-size: 32px; font-weight: 700; color: #0f172a; }\n"
                . ".custom-box p { margin: 12px 0 24px; color: #475569; }\n"
                . ".btn { display: inline-block; padding: 14px 28px; border-radius: 999px;\n  background: #0ea5e9; color: #fff; font-weight: 600; }\n"
                . ".btn:hover { background: #0284c7; }",
        ],
    ],
];
