<?php
return new PhpCsFixer\Config()
    ->setRules([
        '@Symfony' => true,
        'phpdoc_separation' => false,
        'phpdoc_var_without_name' => false,
        'array_syntax' => ['syntax' => 'short'],
        'group_import' => true,
        'single_import_per_statement' => false,
        'braces' => [
            'allow_single_line_closure' => true,
            'position_after_functions_and_oop_constructs' => 'next',
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'method_argument_space' => [
            'on_multiline' => 'ignore',
        ],
    ])
    ->setFinder(new PhpCsFixer\Finder()
        ->in(__DIR__)
        ->exclude('var'))
;
