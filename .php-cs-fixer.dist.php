<?php

declare(strict_types=1);

$header = <<<'EOF'
    Pagination - Simple, lightweight and universal service that implements pagination on collections of things.

    @author    Eric Sizemore <admin@secondversion.com>
    @version   2.0.2
    @copyright (C) 2024 Eric Sizemore
    @license   The MIT License (MIT)
    
    Copyright (C) 2024 Eric Sizemore <https://www.secondversion.com/>.
    Copyright (c) 2015-2019 Ashley Dawson <ashley@ashleydawson.co.uk>.
    
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to
    deal in the Software without restriction, including without limitation the
    rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
    sell copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
    EOF;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS'                                       => true,
        '@PSR12'                                        => true,
        '@PHP82Migration'                               => true,
        'array_syntax'                                  => ['syntax' => 'short'],
        'php_unit_internal_class'                       => ['types' => ['normal', 'final']],
        'php_unit_namespaced'                           => true,
        'php_unit_expectation'                          => true,
        'php_unit_strict'                               => ['assertions' => ['assertAttributeEquals', 'assertAttributeNotEquals', 'assertEquals', 'assertNotEquals']],
        'php_unit_set_up_tear_down_visibility'          => true,
        'phpdoc_align'                                  => true,
        'phpdoc_indent'                                 => true,
        'phpdoc_inline_tag_normalizer'                  => true,
        'phpdoc_no_access'                              => true,
        'phpdoc_no_alias_tag'                           => true,
        'phpdoc_no_empty_return'                        => true,
        'phpdoc_no_package'                             => true,
        'phpdoc_param_order'                            => true,
        'phpdoc_return_self_reference'                  => true,
        'phpdoc_scalar'                                 => true,
        'phpdoc_separation'                             => true,
        'phpdoc_single_line_var_spacing'                => true,
        'phpdoc_summary'                                => true,
        'phpdoc_tag_casing'                             => true,
        'phpdoc_tag_type'                               => true,
        'phpdoc_to_comment'                             => false,
        'phpdoc_trim'                                   => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_var_without_name'                       => true,
        'phpdoc_no_useless_inheritdoc'                  => true,
        'align_multiline_comment'                       => true,
        'phpdoc_add_missing_param_annotation'           => ['only_untyped' => true],
        'binary_operator_spaces'                        => [
            'operators' => [
                '*=' => 'align_single_space_minimal',
                '+=' => 'align_single_space_minimal',
                '-=' => 'align_single_space_minimal',
                '/=' => 'align_single_space_minimal',
                '='  => 'align_single_space_minimal',
                '=>' => 'align_single_space_minimal',
            ],
        ],
        'heredoc_to_nowdoc'       => true,
        'ordered_imports'         => ['imports_order' => ['class', 'function', 'const',]],
        'declare_equal_normalize' => ['space' => 'none'],
        'declare_parentheses'     => true,
        'declare_strict_types'    => true,
        //'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'header_comment'          => ['comment_type' => 'PHPDoc', 'header' => $header, 'separate' => 'top'],
    ])
    ->setLineEnding("\n")
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
    )
;

return $config;
