<?php
$sponsor_prompts = [
    'can' => [
        'name' => 'La Croix',
        'text' => 'naturally integrates the can from image 1 into the environment. If a subject is present, the can appears near them in a casual and believable way, matching lighting and materials.',
        'image' => 'https://i.gyazo.com/77f16706cda2f403d9d93a38a265d928.jpg' ,
        'image_icon'=>'https://viralmagical.s3.us-east-1.amazonaws.com/icons/la-croix.png',
        'role' => 'secondary product',
        'placement' => 'context-aware, scale-accurate, consistent with lighting and materials',
        'object_config' => [
            'description' => 'the La Croix can from image 1',
            'role' => 'secondary',
            'position' => 'naturally integrated in the environment',
            'context' => 'matches lighting, textures, and materials',
            'priority' => 'low'
        ]
    ],
    'keycap' => [
        'name' => 'Claude Key',
        'text' => 'subtly integrates the keycap from image 1 as an organic environmental element, resting on nearby surfaces or logically placed within the setting in a cohesive, believable manner.',
        'image' => 'https://i.gyazo.com/9c4c2d8a6798da1c6e12866842ae2be7.jpg',
        'image_icon'=>'https://viralmagical.s3.us-east-1.amazonaws.com/icons/claude-key.png',
        'role' => 'secondary product',
        'placement' => 'context-aware, scale-accurate, consistent with lighting and materials',
        'object_config' => [
            'description' => 'the keycap from image 1',
            'role' => 'secondary',
            'position' => 'organic element of the setting',
            'context' => 'harmonious with surroundings',
            'priority' => 'low'
        ]
    ],
    'hat' => [
    'name' => 'BFLxFal',
    'text' => 'The hat from image 1 is an intentional scene prop. It should be placed naturally in the environment, resting on a flat surface or object in a contextually appropriate way. If and only if a clearly framed primary human subject is intentionally posed in the foreground, that human may wear the hat, fitted correctly above the eyes and ears. The hat must never be worn by animals, incidental background people, crowds, sculptures, mannequins, statues, or non-human characters.',
    'image' => 'https://i.gyazo.com/27b062f7f3e345259915b6f1064390cb.jpg',
    'image_icon' => 'https://viralmagical.s3.us-east-1.amazonaws.com/icons/bfl-fal-hat.png',
    'role' => 'scene prop',
    'placement' => 'environmental by default, context-aware, scale-accurate, consistent with lighting and materials',
    'object_config' => [
        'description' => 'The hat from image 1 placed as a physical object in the environment.',
        'role' => 'scene prop',
        'placement' => 'resting on a stable flat surface or object',
        'priority' => 'low'
    ]
    ]
];
?>
