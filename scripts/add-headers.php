<?php

/**
 * Idempotent copyright-header insertion script for phlix-plugin-example.
 *
 * Finds all PHP files under src/ and tests/ (excluding vendor/, .git/, generated files)
 * that do not yet carry the @copyright 2026 Joe Huss <detain@interserver.net> line,
 * and inserts a file-level docblock in the required format.
 *
 * Run: php scripts/add-headers.php
 * Run again to verify idempotency (second run = no diff).
 */

declare(strict_types=1);

$repoRoot = __DIR__ . '/..';
$copyrightLine = '@copyright 2026 Joe Huss <detain@interserver.net>';
$headerTemplate = <<<'PHPBLOCK'

/**
 * <one-line description>.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */
PHPBLOCK;

// One-line descriptions per file (best-effort; empty string is acceptable per spec).
$descriptions = [
    'src/HelloMetadataProvider.php'          => 'Reference metadata-provider plugin for Phlix',
    'tests/HelloMetadataProviderTest.php'    => 'Smoke tests for HelloMetadataProvider',
    'tests/bootstrap.php'                     => 'Test bootstrap for the plugin PHPUnit suite',
];

$changed = 0;
$skipped = 0;

foreach (getPHPFiles($repoRoot) as $file) {
    $relativePath = substr($file, strlen($repoRoot) + 1);

    // Skip files that already carry the copyright line.
    $content = file_get_contents($file);
    if (str_contains($content, $copyrightLine)) {
        echo "SKIP  {$relativePath}  (already has copyright)\n";
        $skipped++;
        continue;
    }

    // Build the specific docblock for this file.
    $description = $descriptions[$relativePath] ?? inferDescription($file, $relativePath);
    $docblock = str_replace('<one-line description>.', $description . '.', $headerTemplate);

    // Insert after <?php, preserving existing content.
    if (preg_match('/^<\?php\r?\n?/', $content, $openMatch)) {
        $afterOpenTag = substr($content, strlen($openMatch[0]));
        $newContent = "<?php\n" . $docblock . "\n" . $afterOpenTag;
    } else {
        // Fallback: prepend.
        $newContent = "<?php\n" . $docblock . "\n" . $content;
    }

    file_put_contents($file, $newContent);
    echo "ADDED {$relativePath}\n";
    $changed++;
}

echo "\nDone. {$changed} file(s) updated, {$skipped} already had copyright.\n";

/**
 * Recursively collect all .php files under src/ and tests/, excluding vendor/, .git/, generated.
 *
 * @return list<string> Absolute file paths.
 */
function getPHPFiles(string $repoRoot): array
{
    $dirs = ['src', 'tests'];
    $excludes = ['vendor', '.git', 'generated', 'node_modules', '.phpunit.cache'];

    $files = [];
    foreach ($dirs as $dir) {
        $path = $repoRoot . '/' . $dir;
        if (!is_dir($path)) {
            continue;
        }
        collect($path, $files, $excludes);
    }

    sort($files);
    return $files;
}

function collect(string $dir, array &$files, array $excludes): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $node) {
        if ($node->isDir()) {
            $basename = $node->getBasename();
            if (in_array($basename, $excludes, true)) {
                $iterator->setFlags(RecursiveIteratorIterator::CATCH_GET_CHILD);
            }
            if (in_array($basename, $excludes, true)) {
                continue;
            }
        }

        if ($node->isFile() && $node->getExtension() === 'php') {
            $files[] = $node->getPathname();
        }
    }
}

/**
 * Best-effort one-line description derived from namespace / class name.
 */
function inferDescription(string $file, string $relativePath): string
{
    $content = file_get_contents($file);

    // Try to extract namespace or class name.
    if (preg_match('/^namespace\s+([^;]+);/m', $content, $nsMatch)) {
        $parts = explode('\\', $nsMatch[1]);
        $last = end($parts);
        if ($last !== '') {
            return 'Phlix plugin component: ' . $last;
        }
    }

    if (preg_match('/(?:final\s+)?class\s+(\S+)/', $content, $classMatch)) {
        return 'Phlix plugin component: ' . $classMatch[1];
    }

    return 'Phlix plugin source file.';
}
