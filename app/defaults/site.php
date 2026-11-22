<?php
/**
 * Global content: brand, contact details, header, footer, SEO defaults.
 * (Ported from src/lib/site.ts, Header.tsx, Footer.tsx, WhatsAppFloat.tsx, layout.tsx)
 */
return [
    // Old values of these fields are listed in defaults/legacy.php so live sites pick up new copy.
    'seo' => [
        'title' => 'Edible Oil Refinery & Solvent Extraction Plants | SPARKTECH',
        'title_suffix' => ' | SPARKTECH',
        'description' => 'Mumbai EPC maker of edible oil refinery, solvent extraction, fractionation & bakery shortening plants since 2007 — turnkey, concept to commissioning.',
        'keywords' => 'edible oil refinery plant, solvent extraction plant, oil extraction plant manufacturer, vegetable oil refinery, oil mill machinery, edible oil plant EPC, turnkey oil refinery, oil processing equipment, fats processing plant, oil plant manufacturer India, SPARKTECH Processes, Mumbai',
        'og_image' => '/images/home-banner/preview.png',
        'og_image_alt' => 'SPARKTECH edible oil refinery and solvent extraction plant',
        'locale' => 'en_IN',
        // Local SEO (geo meta + structured data). Position = "latitude;longitude".
        'geo_region' => 'IN-MH',
        'geo_placename' => 'Mumbai',
        'geo_position' => '19.0823;72.8353',
        'founding_year' => '2007',
        'opening_hours' => 'Mo-Sa 09:00-18:00',
        'area_served' => 'India, Bangladesh, Nepal, Sri Lanka, United States',
    ],
    'brand' => [
        'name' => 'SPARKTECH Processes LLP',
        'logo' => ['src' => '/images/sparktech-logo.webp', 'alt' => 'SPARKTECH Processes LLP'],
        'footer_logo' => ['src' => '/images/sparktech-logo.webp', 'alt' => 'SPARKTECH Processes LLP'],
    ],
    'contact' => [
        'phone_display' => '+91 88500 58985',
        'phone_href' => 'tel:+918850058985',
        'email_display' => 'info@SPARKTECHprocesses.com',
        'email_href' => 'mailto:info@SPARKTECHprocesses.com',
        'header_emails' => 'sparktech@sparktech.biz / amitw@sparktech.biz',
        'address_title' => '3/36, Hi! Life',
        'address_lines' => [
            'Sir, Phiroze Shah Mehta Road,',
            'Near Santacruz Railway Station, Mumbai',
        ],
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '',
        'country_code' => 'IN',
        'hours' => 'Mon - Sat: 9 AM - 6 PM',
        'whatsapp_number' => '+919820513136',
        'whatsapp_message' => 'Hello SPARKTECH Processes, I am interested in your project engineering solutions and would like to discuss our requirements.',
        'whatsapp_tooltip' => 'Chat with our experts',
    ],
    'social' => [
        ['name' => 'LinkedIn', 'icon' => 'linkedin', 'href' => 'https://linkedin.com/company/SPARKTECH'],
        ['name' => 'Facebook', 'icon' => 'facebook', 'href' => 'https://facebook.com/SPARKTECH'],
        ['name' => 'Twitter', 'icon' => 'twitter', 'href' => 'https://twitter.com/SPARKTECH'],
        ['name' => 'Instagram', 'icon' => 'instagram', 'href' => 'https://instagram.com/SPARKTECH'],
    ],
    'nav' => [
        'before' => [
            ['label' => 'Home', 'href' => '/'],
            ['label' => 'About', 'href' => '/about/'],
        ],
        'show_services' => true,
        'services_label' => 'All Services',
        'services_mobile_label' => 'Services',
        'services_all_label' => 'View All Services',
        'after' => [
            ['label' => 'Technologies', 'href' => '/technologies/'],
            ['label' => 'Gallery', 'href' => '/gallery/'],
            ['label' => 'Contact', 'href' => '/contact/'],
        ],
        'cta_small' => 'Send your Enquiry on',
        'cta_href' => '/contact/',
        'mobile_cta' => 'SEND YOUR ENQUIRY',
    ],
    'cta_phone_label' => 'Talk to an Expert',
    // Popups: any link to "#popup-{id}" opens one. Manage them in Admin → Popups.
    'popups' => [
        [
            'id' => 'quick-contact',
            'title' => 'Quick contact',
            'width' => 'md',
            'html' => '<div class="p-8 text-center sm:p-10">' . "
"
                . '  <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-600">Talk to an expert</span>' . "
"
                . '  <h2 class="mt-4 text-3xl font-bold text-gray-900">How can we <span class="bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] bg-clip-text text-transparent">help you?</span></h2>' . "
"
                . '  <p class="mx-auto mt-3 max-w-md text-gray-600">Share your capacity target and feedstock — our engineering team replies within 24 hours.</p>' . "
"
                . '  <div class="mt-8 grid gap-3 sm:grid-cols-3">' . "
"
                . '    <a href="tel:+918850058985" class="rounded-2xl border border-gray-200 px-4 py-4 font-semibold text-gray-800 transition hover:border-sky-400 hover:text-sky-600">Call us</a>' . "
"
                . '    <a href="https://wa.me/919820513136" class="rounded-2xl border border-gray-200 px-4 py-4 font-semibold text-gray-800 transition hover:border-emerald-400 hover:text-emerald-600">WhatsApp</a>' . "
"
                . '    <a href="/contact/" class="rounded-2xl bg-sky-500 px-4 py-4 font-semibold text-white transition hover:bg-sky-400">Enquiry form</a>' . "
"
                . '  </div>' . "
"
                . '</div>',
            'css' => '',
        ],
    ],
    'footer' => [
        'cta' => ['label' => 'Send Your Enquiry', 'href' => '/contact/'],
        'social' => [
            ['name' => 'Facebook', 'icon' => 'facebook', 'href' => 'https://facebook.com/SPARKTECH'],
            ['name' => 'LinkedIn', 'icon' => 'linkedin', 'href' => 'https://linkedin.com/company/SPARKTECH'],
        ],
        'columns' => [
            [
                'heading' => 'Technologies',
                'href' => '/technologies/',
                'items' => ['Oil Seed Preparation', 'Oil Extraction', 'Oil & Fats Refining', 'Oil Processing', 'Filtration', 'Value Added Projects'],
            ],
            [
                'heading' => 'Capabilities',
                'href' => '/capabilities/',
                'items' => ['Design & Development', 'Manufacturing', 'Quality & Testing', 'Certification', 'Installation Support', 'After Sales Service'],
            ],
            [
                'heading' => 'Company',
                'href' => '/about/',
                'items' => ['About', 'Projects', 'Gallery', 'Contact', 'Careers'],
            ],
        ],
        'wordmark' => 'SPARKTECH',
        'copyright' => 'SPARKTECH Processes LLP. All rights reserved.',
        'bottom_links' => [
            ['label' => 'Privacy Policy', 'href' => '/privacy/'],
            ['label' => 'Terms of Service', 'href' => '/terms/'],
        ],
        'bottom_text' => 'Global Presence',
    ],
];
