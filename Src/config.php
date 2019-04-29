<?php
return [
        'xmlInput' => getenv('XML_PATH'), // xml из конфига
        'imagesPath' => getenv('IMAGES_PATH'), // папка с изображениями
        'symlink' => getenv('SYMLINK_PATH'), // куда класть сделанный json
        'symlinkImages' => getenv('SYMLINK_IMAGES_PATH'),
        'debug' => 1,
        'mode' => "f", // w - view , f - file
        'logPath' => '/storage/parser.log',
        'imagesOutput' => '/storage/images/',
        'xmlOutput' => '/storage/xml/',
        'jsonOutput' => '/storage/json/',
        'envPath' => realpath(dirname(__FILE__).'/../')
];
