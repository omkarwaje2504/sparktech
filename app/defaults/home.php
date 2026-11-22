<?php
/** Home page content (ported from src/app/page.tsx). Service cards come from the services doc. */
return [
    'seo' => [
        'title' => 'Edible Oil Refinery & Solvent Extraction Plants | SPARKTECH',
        'description' => 'Turnkey edible oil plants, concept to commissioning: solvent extraction, physical & chemical refining, fractionation, dewaxing, hydrogenation. EPC since 2007.',
        'keywords' => 'edible oil plant manufacturer, oil refinery plant manufacturer in India, solvent extraction plant manufacturer, turnkey edible oil plant, oil seed processing plant, vegetable oil processing plant, EPC contractor edible oil',
    ],
    'hero' => [
        'video' => '/images/home-banner/sparktech_video_compressed.mp4',
        'poster' => '/images/home-banner/preview.webp',
        'eyebrow' => 'Concept to Commissioning',
        'title' => 'Edible Oil, Fats',
        'highlight' => '& Derivatives',
        'subtitle' => 'Specialized in the supply and initialization of oil extraction, refinery & food processing units — engineered from concept to commissioning.',
        'pills' => ['Oil Extraction Plants', 'Refinery Setup & Upgrades', 'Food Processing Units'],
        'primary' => ['label' => 'Send Your Enquiry', 'href' => '/contact/'],
        'secondary' => ['label' => 'Ask Our Experts', 'href' => 'tel:+918850058985'],
    ],
    'about' => [
        'image' => ['src' => '/images/about-us/about_us_thumbnail_image.webp', 'alt' => 'SPARKTECH edible oil plant engineering'],
        'overlay_title' => 'Leading the future of edible oil processing',
        'overlay_text' => 'SPARKTECH combines engineering depth with execution discipline so buyers can move from idea to production with less friction and more clarity.',
        'title' => 'EPC expertise',
        'subtitle' => 'From Concept to Commissioning, We deliver Reliability.',
        'read_more' => 'Read more',
        'items' => [
            ['title' => 'One EPC partner', 'description' => 'Concept development, detailed engineering, procurement support, installation guidance, and handover under one team.', 'href' => '/services/'],
            ['title' => 'Process-first engineering', 'description' => 'Solutions are scoped around feedstock, target products, utilities, and the operating realities of edible oil plants.', 'href' => '/services/'],
            ['title' => 'Global project exposure', 'description' => 'References across India, Bangladesh, Nepal, Sri Lanka, the USA, and other international markets.', 'href' => '/gallery/'],
            ['title' => 'Commissioning and training', 'description' => 'Support continues after delivery so operators can reach steady, reliable production faster.', 'href' => '/services/'],
        ],
    ],
    'services' => [
        'title' => 'Our Solutions',
        'button' => ['label' => 'View All Services', 'href' => '/services/'],
    ],
    'process' => [
        'title' => 'Process clarity reduces buying friction',
        'subtitle' => 'A visible process builds confidence before the first call — from discussion to delivery.',
        'steps' => [
            ['title' => 'Consultation and plant brief', 'description' => 'Capture capacity targets, feedstock, product mix, utilities, and expansion goals before engineering starts.', 'image' => '/images/processes/process-1.webp'],
            ['title' => 'Process design and detailed engineering', 'description' => 'Translate business requirements into practical process flow, equipment scope, and execution-ready planning.', 'image' => '/images/processes/process-2.webp'],
            ['title' => 'Procurement and fabrication', 'description' => 'Align materials, equipment, and fabrication decisions with the approved process and delivery schedule.', 'image' => '/images/processes/process-3.webp'],
            ['title' => 'Installation and commissioning', 'description' => 'Coordinate site execution, startup, and performance stabilization with fewer handoff gaps.', 'image' => '/images/processes/process-4.webp'],
            ['title' => 'Training and post-handover support', 'description' => 'Equip operators and maintenance teams to run the plant with confidence after commissioning.', 'image' => '/images/processes/process-5.webp'],
        ],
    ],
    'technologies' => [
        'title' => 'Our Technologies',
        'subtitle' => 'SPARKTECH have, after a long experience, developed a complete range of equipment suited for each type of raw material and processing requirement.',
        'enquire_label' => 'Send Enquiry',
        'learn_label' => 'Learn More',
        'items' => [
            ['title' => 'Oil Seed Preparation', 'href' => '/technologies/#seed-preparation', 'image' => '/images/technologies/seed-preparation.webp'],
            ['title' => 'Oil Extraction', 'href' => '/technologies/#oil-extraction', 'image' => '/images/technologies/oil-extraction.webp'],
            ['title' => 'Oil & Fats Refining', 'href' => '/technologies/#oil-refining', 'image' => '/images/technologies/oil-refining.webp'],
            ['title' => 'Oil Processing', 'href' => '/technologies/#oil-processing', 'image' => '/images/technologies/oil-processing.webp'],
            ['title' => 'Filtration', 'href' => '/technologies/#filtration', 'image' => '/images/technologies/filtration.webp'],
            ['title' => 'Value Added Projects', 'href' => '/technologies/#value-added', 'image' => '/images/technologies/value-added.webp'],
        ],
    ],
    'gallery' => [
        'title' => 'Our Projects Gallery',
        'subtitle' => 'Explore our successfully completed projects across the globe.',
        'button' => ['label' => 'Review Project Gallery', 'href' => '/gallery/'],
        'projects' => [
            ['title' => '200 TPD Oil Refinery', 'location' => 'Bangladesh', 'proof' => 'Refinery reference', 'image' => '/images/gallery/gallery_thumnail_images/refinery/200_tpd_oil_refinery_-_bangladesh.webp'],
            ['title' => '500 TPD Solvent Extraction', 'location' => 'Nepal', 'proof' => 'Extraction reference', 'image' => '/images/gallery/gallery_thumnail_images/solvent_extraction/500_tpd_solvent_extraction_-_nepal.webp'],
            ['title' => 'Palm Oil Fractionation', 'location' => 'Sri Lanka', 'proof' => 'Value-added processing reference', 'image' => '/images/gallery/gallery_thumnail_images/fractionation/palm_oil_fractionation_-_sri_lanka.webp'],
            ['title' => '1000 kg/hr Bakery Shortening', 'location' => 'India', 'proof' => 'Specialty fats reference', 'image' => '/images/gallery/gallery_thumnail_images/bakery/1000_kghr_bakery_shortening_-_india.webp'],
        ],
    ],
];
