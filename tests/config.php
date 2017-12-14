<?
return [
    'sources' => [
        'test' => [
            'root' => realpath(__DIR__ . '/files') . '/',
            'baseurl' => 'http://localhost:8181/tests/files/',
            'extensions' => ['jpg', 'png', 'gif', 'jpeg']
        ],
        'folder1' => [
            'root' => realpath(__DIR__ .  '/files/folder1') . '/',
            'baseurl' => 'http://localhost:8181/tests/files/folder1/',
            'extensions' => ['jpg', 'png', 'gif', 'jpeg'],
            'maxFileSize' => '1kb'
        ]
    ],
    'allowCrossOrigin' => true,
];