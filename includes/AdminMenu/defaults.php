<?php

defined('ABSPATH') || exit;

/**
 * Reviewed default catalogue for the local Settings submenu.
 * Stable entry IDs retain saved ordering across translated menu slugs.
 * Source evidence: docs/admin-settings-menu-inventory.json (2026-10-16).
 * The array order is the default; runtime never creates links from this data.
 */
return [
    'akismet' => [
        'label' => __('Akismet Anti-spam', 'rrze-settings'),
        'slugs' => ['akismet-key-config'],
    ],
    'fau-cris' => [
        'label' => __('CRIS', 'rrze-settings'),
        'slugs' => ['options-fau-cris'],
    ],
    'fau-oembed' => [
        'label' => __('FAU oEmbed', 'rrze-settings'),
        'slugs' => ['fau-oembed'],
    ],
    'fau-orga-breadcrumb' => [
        'label' => __('FAU.ORG Breadcrumb', 'rrze-settings'),
        'slugs' => ['fau_orga_breadcrumb_settings'],
    ],
    'fau-person' => [
        'label' => __('FAU Person', 'rrze-settings'),
        'slugs' => ['fau-person'],
    ],
    'fau-studium' => [
        'label' => __('FAU Degree Program', 'rrze-settings'),
        'slugs' => ['fau_degree_program_settings'],
    ],
    'fau-studium-display' => [
        'label' => __('FAU Studium Display', 'rrze-settings'),
        'slugs' => ['fau-studium-display'],
    ],
    'fau-studium-fachanteile' => [
        'label' => __('FAU Studium Fachanteile', 'rrze-settings'),
        'slugs' => ['fau-studium-fachanteile'],
    ],
    'like-json' => [
        'label' => __('CMS-Basis', 'rrze-settings'),
        'slugs' => ['cms-basis'],
    ],
    'rrze-ac' => [
        'label' => __('Access Control', 'rrze-settings'),
        'slugs' => ['rrze-ac'],
    ],
    'rrze-advanced-search' => [
        'label' => __('Advanced Search', 'rrze-settings'),
        'slugs' => ['rrze-advanced-search'],
    ],
    'rrze-answers' => [
        'label' => __('RRZE Answers', 'rrze-settings'),
        'slugs' => ['rrze-answers-settings', 'rrze-answers-einstellungen'],
    ],
    'rrze-appointment' => [
        'label' => __('RRZE Appointment', 'rrze-settings'),
        'slugs' => ['rrze-appointment-settings'],
    ],
    'rrze-auto-tweet' => [
        'label' => __('Auto-Tweet', 'rrze-settings'),
        'slugs' => ['rrze-auto-tweet'],
    ],
    'rrze-autoshare' => [
        'label' => __('RRZE-Autoshare', 'rrze-settings'),
        'slugs' => ['rrze_autoshare'],
    ],
    'rrze-block-control' => [
        'label' => __('RRZE Block Control', 'rrze-settings'),
        'slugs' => ['rrze-block-control'],
    ],
    'rrze-bluesky' => [
        'label' => __('RRZE Bluesky', 'rrze-settings'),
        'slugs' => ['rrze-bluesky'],
    ],
    'rrze-cache' => [
        'label' => __('Cache', 'rrze-settings'),
        'slugs' => ['rrze-cache'],
    ],
    'rrze-calendar' => [
        'label' => __('Calendar', 'rrze-settings'),
        'slugs' => ['rrze-calendar'],
    ],
    'rrze-directions' => [
        'label' => __('RRZE Directions', 'rrze-settings'),
        'slugs' => ['rrze-directions-settings'],
    ],
    'rrze-downloads' => [
        'label' => __('RRZE Downloads', 'rrze-settings'),
        'slugs' => ['rrze-downloads'],
    ],
    'rrze-events' => [
        'label' => __('RRZE Events', 'rrze-settings'),
        'slugs' => ['rrze-events-settings'],
    ],
    'rrze-expo' => [
        'label' => __('RRZE Expo', 'rrze-settings'),
        'slugs' => ['rrze-expo'],
    ],
    'rrze-faq' => [
        'label' => __('RRZE FAQ', 'rrze-settings'),
        'slugs' => ['rrze-faq'],
    ],
    'rrze-faubox' => [
        'label' => __('RRZE FAUbox', 'rrze-settings'),
        'slugs' => ['rrze-faubox'],
    ],
    'rrze-faudir' => [
        'label' => __('RRZE FAUdir', 'rrze-settings'),
        'slugs' => ['rrze-faudir'],
    ],
    'rrze-formular' => [
        'label' => __('RRZE Formular', 'rrze-settings'),
        'slugs' => ['rrze-formular-settings', 'rrze-formular-einstellungen'],
    ],
    'rrze-glossary' => [
        'label' => __('RRZE Glossary', 'rrze-settings'),
        'slugs' => ['rrze-glossary'],
    ],
    'rrze-green-office' => [
        'label' => __('RRZE Green Office', 'rrze-settings'),
        'slugs' => ['rrze-green-office'],
    ],
    'rrze-greetings' => [
        'label' => __('Greetings', 'rrze-settings'),
        'slugs' => ['rrze-greetings'],
    ],
    'rrze-jobs' => [
        'label' => __('RRZE Jobs', 'rrze-settings'),
        'slugs' => ['rrze-jobs'],
    ],
    'rrze-jobspreader-analytics' => [
        'label' => __('Jobspreader Analytics', 'rrze-settings'),
        'slugs' => ['rrze-jobspreader-analytics'],
    ],
    'rrze-knowledgebase' => [
        'label' => __('RRZE Knowledge Base', 'rrze-settings'),
        'slugs' => ['rrze-kb-settings'],
    ],
    'rrze-lectures' => [
        'label' => __('RRZE Lectures', 'rrze-settings'),
        'slugs' => ['rrze-lectures'],
    ],
    'rrze-multilang' => [
        'label' => __('Multilanguage', 'rrze-settings'),
        'slugs' => ['rrze-multilang'],
    ],
    'rrze-newsletter' => [
        'label' => __('Newsletters', 'rrze-settings'),
        'slugs' => ['rrze-newsletter'],
    ],
    'rrze-post-expiration' => [
        'label' => __('Expiration', 'rrze-settings'),
        'slugs' => ['rrze_post_expiration'],
    ],
    'rrze-preise' => [
        'label' => __('RRZE Preise', 'rrze-settings'),
        'slugs' => ['rrze-preise'],
    ],
    'rrze-pwa' => [
        'label' => __('PWA', 'rrze-settings'),
        'slugs' => ['rrze-pwa'],
    ],
    'rrze-research-data' => [
        'label' => __('RRZE Research Data', 'rrze-settings'),
        'slugs' => ['rrze-research-data'],
    ],
    'rrze-rsvp' => [
        'label' => __('RRZE RSVP', 'rrze-settings'),
        'slugs' => ['rrze-rsvp'],
    ],
    'rrze-servicekatalog' => [
        'label' => __('RRZE Servicekatalog', 'rrze-settings'),
        'slugs' => ['rrze-servicekatalog-settings'],
    ],
    'rrze-siteimprove' => [
        'label' => __('RRZE Siteimprove', 'rrze-settings'),
        'slugs' => ['rrze-siteimprove'],
    ],
    'rrze-synonym' => [
        'label' => __('RRZE Synonym', 'rrze-settings'),
        'slugs' => ['rrze-synonym'],
    ],
    'rrze-typesettings' => [
        'label' => __('RRZE Typesettings', 'rrze-settings'),
        'slugs' => ['rrze-typesettings'],
    ],
    'rrze-univis' => [
        'label' => __('RRZE UnivIS', 'rrze-settings'),
        'slugs' => ['rrze-univis'],
    ],
    'rrze-video' => [
        'label' => __('RRZE Video Settings', 'rrze-settings'),
        'slugs' => ['rrze-video'],
    ],
    'rrze-webt' => [
        'label' => __('WEB-T Translator', 'rrze-settings'),
        'slugs' => ['rrze-webt'],
    ],
    'rrze-xliff' => [
        'label' => __('XLIFF Export/Import', 'rrze-settings'),
        'slugs' => ['rrze-xliff'],
    ],
    'tinymce-advanced' => [
        'label' => __('Advanced Editor Tools', 'rrze-settings'),
        'slugs' => ['tinymce-advanced'],
    ],
    'wp-downgrade' => [
        'label' => __('WP Downgrade', 'rrze-settings'),
        'slugs' => ['wpdowngrade'],
    ],
];
