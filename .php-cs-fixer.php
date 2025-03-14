<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['public', 'config', 'app', 'database']) // Set directories to format
    ->name('*.php')
    ->notName('vendor')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true, // Follow PSR-12 coding standards
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'single_blank_line_at_eof' => true,
        'no_trailing_whitespace' => true,
        'indentation_type' => true,
        'braces' => ['position_after_functions_and_oop_constructs' => 'next'],
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder);
