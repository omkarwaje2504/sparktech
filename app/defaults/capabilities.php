<?php
/** Capabilities page (ported from src/app/capabilities/*, src/lib/content/capabilities.ts). */
return [
    'seo' => [
        'title' => 'Engineering, Manufacturing & EPC Capabilities',
        'description' => 'In-house process design, fabrication, testing, installation, commissioning and operator training for edible oil plants — one accountable EPC team.',
        'keywords' => 'process plant design, oil plant fabrication, EPC capabilities, plant installation and commissioning, quality testing, operator training, after sales service oil plant',
    ],
    'hero' => [
        'image' => '/images/home_page/home_page_banner-advanced_refinery_plants.webp',
        'crumb' => 'Capabilities',
        'eyebrow' => 'End to End EPC',
        'title' => 'Our',
        'highlight' => 'Capabilities',
        'subtitle' => 'End-to-end engineering, manufacturing, and project execution — from design and fabrication to installation, commissioning and after-sales support.',
        'primary' => ['label' => 'Send Your Enquiry', 'href' => '/contact/'],
        'secondary' => ['label' => 'Ask Our Experts', 'href' => 'tel:+918850058985'],
    ],
    'main' => [
        'eyebrow' => 'What We Can Do',
        'title' => 'Engineering',
        'highlight' => 'Capabilities',
        'subtitle' => 'From concept to commissioning, we deliver comprehensive solutions backed by decades of expertise.',
        'items' => [
            [
                'title' => 'Design & Development', 'glyph' => 'engineering',
                'description' => 'Our experienced engineering team designs customized solutions using the latest CAD/CAM software and simulation tools.',
                'features' => ['Custom plant design and engineering', '3D modeling and simulation', 'Process flow optimization', 'Equipment sizing and selection', 'P&ID and GA drawing preparation', 'Structural and civil design integration'],
                'image' => '/images/about-us/about_us_thumbnail_image.webp',
            ],
            [
                'title' => 'Manufacturing Excellence', 'glyph' => 'factory',
                'description' => 'State-of-the-art manufacturing facility equipped with modern machinery for precision fabrication.',
                'features' => ['CNC machining centers', 'Automated welding systems', 'Precision bending and rolling', 'Surface treatment and coating', 'Assembly and integration', 'Factory acceptance testing'],
                'image' => '/images/technologies/technologies_thumnail_images/oil_extraction.webp',
            ],
            [
                'title' => 'Quality & Testing', 'glyph' => 'quality',
                'description' => 'Rigorous quality control processes ensure every product meets international standards.',
                'features' => ['Incoming material inspection', 'In-process quality checks', 'NDT testing (UT, RT, MPT, DPT)', 'Hydrostatic pressure testing', 'Performance validation', 'Documentation and traceability'],
                'image' => '/images/updated-images/vegetable_oil_refinery.webp',
            ],
            [
                'title' => 'Certification & Standards', 'glyph' => 'certification',
                'description' => 'Our products and processes are certified to meet global quality and safety standards.',
                'features' => ['ISO 9001:2015 certified', 'CE marking compliant', 'ASME standards adherence', 'Food safety compliance', 'Environmental standards', 'Export documentation'],
                'image' => '/images/services/thumbnails/fractionation.webp',
            ],
        ],
    ],
    'support' => [
        'eyebrow' => 'Support Services',
        'title' => 'Complete',
        'highlight' => 'Service Support',
        'subtitle' => '',
        'items' => [
            ['title' => 'Installation Supervision', 'glyph' => 'wrench', 'description' => 'Expert guidance during equipment installation to ensure proper setup and alignment.'],
            ['title' => 'Commissioning Support', 'glyph' => 'bolt', 'description' => 'Hands-on support during plant commissioning to achieve optimal performance.'],
            ['title' => 'After Sales Support', 'glyph' => 'cog', 'description' => 'Comprehensive post-installation support including spare parts and technical assistance.'],
            ['title' => 'Training Programs', 'glyph' => 'book', 'description' => 'Operator training programs to ensure safe and efficient plant operation.'],
        ],
    ],
    'infrastructure' => [
        'background' => '/images/home_page/home_page_banner-turnkey_solutions.webp',
        'eyebrow' => 'Our Infrastructure',
        'title' => 'World-class',
        'highlight' => 'manufacturing facility',
        'subtitle' => 'Our facility in India is equipped with the latest machinery and technology to deliver precision-engineered products that meet international standards.',
        'stats' => [
            ['value' => 50, 'suffix' => 'K+', 'label' => 'Sq. Ft. Factory Area'],
            ['value' => 100, 'suffix' => '+', 'label' => 'Skilled Workers'],
            ['value' => 50, 'suffix' => '+', 'label' => 'CNC Machines'],
            ['value' => 24, 'suffix' => '/7', 'label' => 'Operations'],
        ],
    ],
    'cta' => [
        'eyebrow' => "Let's Build Together",
        'title' => 'Ready to start your',
        'highlight' => 'project?',
        'subtitle' => 'Let our engineering team help you design the perfect solution for your requirements.',
        'primary' => ['label' => 'Get Started', 'href' => '/contact/'],
    ],
];
