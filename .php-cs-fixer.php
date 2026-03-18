<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->exclude(['Tests', 'Migrations', 'Resources'])
    ->name('*.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony'                      => true,
        '@Symfony:risky'                => true,
        '@PHP82Migration'               => true,
        '@PHP80Migration:risky'         => true,
        'declare_strict_types'          => true,
        'strict_param'                  => true,
        'array_syntax'                  => ['syntax' => 'short'],
        'ordered_imports'               => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'             => true,
        'single_quote'                  => true,
        'concat_space'                  => ['spacing' => 'one'],
        'phpdoc_align'                  => ['align' => 'left'],
        'phpdoc_separation'             => true,
        'class_attributes_separation'   => ['elements' => ['method' => 'one', 'property' => 'one']],
        'final_class'                   => false,
        'native_function_invocation'    => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
