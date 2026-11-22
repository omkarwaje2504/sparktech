<?php
/**
 * Earlier shipped default values: doc => [path => [old values]].
 *
 * Saving a page stores the whole doc, so a changed default never reaches a live
 * site on its own. When a stored value still equals one of these old defaults
 * (the admin never edited it), Content::data() swaps in the current default.
 * Values an admin typed themselves are left alone.
 */
return [
    'site' => [
        'seo.title' => ['SPARKTECH Processes LLP | Edible Oil Extraction & Refinery Equipment'],
        'seo.title_suffix' => [' | SPARKTECH Processes LLP'],
        'seo.description' => ['Leading manufacturer and exporter of industrial machinery for edible oil extraction, refinery plants, and food processing equipment. A Perfect EPC Project Partner since 2007.'],
        'seo.keywords' => ['oil extraction plant, edible oil refinery, solvent extraction, vegetable oil, oil processing equipment, Mumbai, India'],
    ],
    'home' => [
        'seo.title' => ['SPARKTECH Processes LLP | Edible Oil Extraction & Refinery Equipment'],
        'seo.description' => ['Leading manufacturer and exporter of industrial machinery for edible oil extraction, refinery plants, and food processing equipment. A Perfect EPC Project Partner since 2007.'],
    ],
    'about' => [
        'seo.title' => ['About Us'],
        'seo.description' => ['SPARKTECH Processes LLP — an EPC project partner since 2007 delivering engineering, procurement, manufacturing, installation and commissioning for edible oil plants.'],
    ],
    'services' => [
        'seo.title' => ['Services'],
        'seo.description' => ['Complete range of edible oil processing equipment - Solvent Extraction, Refinery, Fractionation, Lecithin Plants, and Bakery Shortening.'],
    ],
    'technologies' => [
        'seo.title' => ['Technologies'],
        'seo.description' => ['Explore our advanced technologies for oil seed preparation, extraction, refining, and processing. State-of-the-art solutions for edible oil industry.'],
    ],
    'capabilities' => [
        'seo.title' => ['Capabilities'],
        'seo.description' => ['Explore our engineering capabilities - Design & Development, Manufacturing, Quality Testing, and Certification. End-to-end EPC solutions.'],
    ],
    'gallery' => [
        'seo.title' => ['Project Gallery'],
        'seo.description' => ['Explore our completed projects worldwide - Oil refineries, extraction plants, and processing facilities in India, Bangladesh, Nepal, Sri Lanka and more.'],
    ],
    'contact' => [
        'seo.title' => ['Contact Us'],
        'seo.description' => ['Get in touch with SPARKTECH Processes LLP for inquiries about oil extraction plants, refineries, and processing equipment. Mumbai, India.'],
    ],
    'page:terms' => [
        'seo.description' => ['Terms of use for the SPARKTECH Processes LLP website.'],
    ],
    'page:privacy' => [
        'seo.description' => ['How SPARKTECH Processes LLP collects, uses and protects information shared through this website.'],
        'body' => [
            '<p><strong>Information we collect.</strong> When you send an enquiry we receive the details you enter — your name, e-mail address, phone number, company and message — so that our engineering team can respond.</p>'
                . '<p><strong>How we use it.</strong> Enquiry details are used only to answer your request and discuss your project. We do not sell or rent your information to third parties.</p>'
                . '<p><strong>Cookies.</strong> This website uses a small cookie to remember which service you viewed so that the enquiry form can be pre-filled for you. No advertising cookies are used.</p>'
                . '<p><strong>Contact.</strong> For any questions about this policy, or to ask us to delete your information, please contact us using the details on our Contact page.</p>',
        ],
    ],
];
