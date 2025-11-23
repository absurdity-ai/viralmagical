<?php
$sponsors = [
    'bfl_hat' => [
        'id'   => 'bfl_hat',
        'name' => 'BFL x fal Hat',
        'image' => 'https://viralmagical.s3.us-east-1.amazonaws.com/icons/bfl-fal-hat.png', // Using the icon as the reference image for now, or the one from old config
        'image_ref' => 'https://i.gyazo.com/27b062f7f3e345259915b6f1064390cb.jpg', // The actual reference image for generation
        'modes' => ['ambient_prop', 'wearable_hero', 'panel_cameo'],
        'mode_prompts' => [
            'ambient_prop' =>
                'The BFL x FLUX hat from image S is placed as an intentional scene prop. ' .
                'It rests naturally on a stable surface (bench, table, ledge), matching lighting, shadows, and scale. ' .
                'It should feel like it has always belonged there, not like a sticker.',
            'wearable_hero' =>
                'The BFL x FLUX hat from image S is worn naturally by the clearly framed primary human subject in the foreground, ' .
                'fitted correctly above the eyes and ears. Never on animals, incidental background people, statues, mannequins, or non-human figures.',
            'panel_cameo' =>
                'In each panel, the hat from image S appears as a small recurring prop in the background or on a surface, ' .
                'consistent across panels but never dominating the story.',
        ],
    ],
    'la_croix' => [
        'id' => 'la_croix',
        'name' => 'La Croix',
        'image' => 'https://viralmagical.s3.us-east-1.amazonaws.com/icons/la-croix.png',
        'image_ref' => 'https://i.gyazo.com/77f16706cda2f403d9d93a38a265d928.jpg',
        'modes' => ['ambient_prop', 'panel_cameo'],
        'mode_prompts' => [
            'ambient_prop' => 'naturally integrates the La Croix can from image S into the environment. If a subject is present, the can appears near them in a casual and believable way, matching lighting and materials.',
            'panel_cameo' => 'The La Croix can from image S appears as a subtle background detail in the scene, consistent with the environment.'
        ]
    ],
    'claude_key' => [
        'id' => 'claude_key',
        'name' => 'Claude Key',
        'image' => 'https://viralmagical.s3.us-east-1.amazonaws.com/icons/claude-key.png',
        'image_ref' => 'https://i.gyazo.com/9c4c2d8a6798da1c6e12866842ae2be7.jpg',
        'modes' => ['ambient_prop', 'panel_cameo'],
        'mode_prompts' => [
            'ambient_prop' => 'subtly integrates the keycap from image S as an organic environmental element, resting on nearby surfaces or logically placed within the setting in a cohesive, believable manner.',
            'panel_cameo' => 'The keycap from image S appears as a small, hidden easter egg in the scene.'
        ]
    ]
];
?>
