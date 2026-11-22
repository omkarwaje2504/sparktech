<?php
/**
 * Simple text pages (the footer links to these). Admins can add more from
 * Admin → Pages. Each page: seo{}, hero{}, body (rich text).
 */
return [
    'privacy' => [
        'seo' => ['title' => 'Privacy Policy', 'description' => 'How SPARKTECH Processes LLP collects, uses and protects enquiry details and anonymous website usage statistics, and how to ask us to delete your information.'],
        'hero' => [
            'crumb' => 'Privacy Policy',
            'eyebrow' => 'Legal',
            'title' => 'Privacy',
            'highlight' => 'Policy',
            'subtitle' => 'How we collect, use and protect the information you share with us.',
            'image' => '/images/about-us/banner.webp',
        ],
        'body' => '<p><strong>Information we collect.</strong> When you send an enquiry we receive the details you enter — your name, e-mail address, phone number, company and message — so that our engineering team can respond.</p>'
            . '<p><strong>How we use it.</strong> Enquiry details are used only to answer your request and discuss your project. We do not sell or rent your information to third parties.</p>'
            . '<p><strong>Cookies.</strong> This website uses a small cookie to remember which service you viewed so that the enquiry form can be pre-filled for you. No advertising cookies are used.</p>'
            . '<p><strong>Website statistics.</strong> To understand which pages are useful, our own server records the pages you view, the links and buttons you click, your device type, browser, screen size and approximate location (country and city, looked up from your IP address, which we store only in shortened form). Your browser keeps a random visitor ID so we can count repeat visits; it does not identify you personally and is never shared with advertisers. If you send an enquiry, it is linked to that visit history so we can follow up on the pages you were interested in.</p>'
            . '<p><strong>Contact.</strong> For any questions about this policy, or to ask us to delete your information, please contact us using the details on our Contact page.</p>',
    ],
    'terms' => [
        'seo' => ['title' => 'Terms of Service', 'description' => 'Terms of use for the SPARKTECH Processes LLP website: content, intellectual property, technical data accuracy and liability for edible oil plant information.'],
        'hero' => [
            'crumb' => 'Terms of Service',
            'eyebrow' => 'Legal',
            'title' => 'Terms of',
            'highlight' => 'Service',
            'subtitle' => 'The terms that apply when you use this website.',
            'image' => '/images/about-us/banner.webp',
        ],
        'body' => '<p><strong>Website content.</strong> The information on this website is provided for general information about our products and services. Technical data may change without notice; please confirm specifications with our team before purchase.</p>'
            . '<p><strong>Intellectual property.</strong> All text, images, drawings and logos on this website belong to SPARKTECH Processes LLP unless stated otherwise and may not be reproduced without permission.</p>'
            . '<p><strong>Liability.</strong> While we take care to keep the website accurate and available, we cannot guarantee it is free of errors or interruptions.</p>'
            . '<p><strong>Contact.</strong> Questions about these terms can be sent to us through the Contact page.</p>',
    ],
];
