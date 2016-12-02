<?php

$finder = Symfony\CS\Finder\Symfony23Finder::create()
    ->in(__DIR__.'/src');

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers([
        '-phpdoc_short_description',
        'ordered_use',
        'short_array_syntax',
        '-phpdoc_short_description',
        '-unalign_equals',
        'align_equals',
        '-unalign_double_arrow',
        'align_double_arrow',
        '-no_empty_lines_after_phpdocs'
    ])
    ->finder($finder);
