<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_categoryfields_get_my_courses_with_programs' => [
        'classname'   => 'local_categoryfields\external\get_my_courses_with_programs',
        'description' => 'Obtém os cursos do usuário com dados de programa injetados.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'moodle/my:view',
    ],
];

$services = [
    'Category Fields Service' => [
        'functions'        => ['local_categoryfields_get_my_courses_with_programs'],
        'restrictedusers'  => 0,
        'enabled'          => 1,
        'shortname'        => 'local_categoryfields_service',
    ]
];
