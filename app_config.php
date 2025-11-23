<?php
$image_apps = [
    'comic_strip' => [
        'id'   => 'comic_strip',
        'name' => 'Comic Strip',
        'description' => 'Turn your character photos into a 3-panel comic.',
        'icon' => '📝', // Placeholder icon
        'inputs' => [
            ['slot' => 1, 'role' => 'character',  'label' => 'Character / OC', 'required' => true],
            ['slot' => 2, 'role' => 'pose_ref',   'label' => 'Pose / action (optional)', 'required' => false],
            ['slot' => 3, 'role' => 'background', 'label' => 'Background (optional)', 'required' => false],
        ],
        'layout' => [
            'type'   => 'multi_panel',
            'panels' => 3,
        ],
        'allowed_sponsor_modes' => ['panel_cameo', 'ambient_prop'],
        'prompt_template' => "A 3-panel comic strip featuring [character] in a story. Panel 1: [character] is [pose_ref] in [background]. Panel 2: Close up of [character]. Panel 3: [character] reacting to something. Style: Graphic novel, vibrant colors."
    ],
    'scene_remix' => [
        'id'   => 'scene_remix',
        'name' => 'Scene Remix',
        'description' => 'Merge character + background + prop into a new scene.',
        'icon' => '🎨',
        'inputs' => [
            ['slot' => 1, 'role' => 'character', 'label' => 'Character', 'required' => true],
            ['slot' => 2, 'role' => 'background', 'label' => 'Background', 'required' => true],
            ['slot' => 3, 'role' => 'prop', 'label' => 'Prop (optional)', 'required' => false],
        ],
        'allowed_sponsor_modes' => ['ambient_prop', 'wearable_hero'],
        'prompt_template' => "A cinematic shot of [character] in [background] holding or interacting with [prop]. High quality, detailed."
    ],
    'try_on' => [
        'id'   => 'try_on',
        'name' => 'Try-On Outfit',
        'description' => 'See yourself in a new outfit.',
        'icon' => '👗',
        'inputs' => [
            ['slot' => 1, 'role' => 'character', 'label' => 'Person', 'required' => true],
            ['slot' => 2, 'role' => 'clothing', 'label' => 'Clothing Reference', 'required' => true],
        ],
        'allowed_sponsor_modes' => ['ambient_prop'], // Can't wear sponsor if trying on other clothes usually, or maybe yes? keeping it simple.
        'prompt_template' => "A photo of [character] wearing [clothing]. Fashion photography, professional lighting. Render the full body clearly with correct proportions."
    ],
    'collage' => [
        'id'   => 'collage',
        'name' => 'Collage / Poster',
        'description' => 'Artistic collage of multiple elements.',
        'icon' => '🖼️',
        'inputs' => [
            ['slot' => 1, 'role' => 'element1', 'label' => 'Main Element', 'required' => true],
            ['slot' => 2, 'role' => 'element2', 'label' => 'Secondary Element', 'required' => false],
            ['slot' => 3, 'role' => 'style', 'label' => 'Style Reference', 'required' => false],
        ],
        'allowed_sponsor_modes' => ['ambient_prop', 'panel_cameo'],
        'prompt_template' => "An artistic collage poster featuring [element1] and [element2]. Style inspired by [style]. Mixed media, creative composition."
    ]
];

// Load custom apps
$customAppsFile = __DIR__ . '/custom_apps.json';
if (file_exists($customAppsFile)) {
    $customApps = json_decode(file_get_contents($customAppsFile), true);
    if ($customApps) {
        $image_apps = array_merge($image_apps, $customApps);
    }
}
?>
