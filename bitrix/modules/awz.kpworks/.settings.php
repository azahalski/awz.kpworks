<?php
return [
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzkpworks-user',
                    'provider' => [
                        'moduleId' => 'awz.kpworks',
                        'className' => '\\Awz\\Kpworks\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzkpworks-group',
                    'provider' => [
                        'moduleId' => 'awz.kpworks',
                        'className' => '\\Awz\\Kpworks\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ],
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\Awz\\Kpworks\\Api\\Controller' => 'api'
            ]
        ],
        'readonly' => true
    ],
];