<?php

declare(strict_types=1);

it('contains no debugging termination calls', function (): void {
    $sourceFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../src'),
    );
    $violations = [];

    foreach ($sourceFiles as $sourceFile) {
        if ( ! $sourceFile->isFile() || $sourceFile->getExtension() !== 'php') {
            continue;
        }

        $source = file_get_contents($sourceFile->getPathname());

        foreach (token_get_all((string) $source) as $token) {
            if ( ! is_array($token)) {
                continue;
            }

            if ($token[0] === T_EXIT
                || ($token[0] === T_STRING && strtolower($token[1]) === 'var_dump')) {
                $violations[] = $sourceFile->getPathname().':'.$token[2];
            }
        }
    }

    expect($violations)->toBe([]);
});
