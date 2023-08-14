<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

require __DIR__ . '/vendor/autoload.php';

function getPackagesFilesRelativePathname(array $excludePackages): array
{
    $rootDirnameLen = strlen(__DIR__);

    $excludeFiles = [];

    foreach ($excludePackages as $package) {
        $packagePath = \Composer\InstalledVersions::getInstallPath($package);
        $packagePath = realpath($packagePath);

        $items = Finder::create()
            ->files()
            ->in($packagePath);

        foreach ($items as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */

            $relativePathname = substr($file->getPathname(), $rootDirnameLen + 1);

            $excludeFiles[] = $relativePathname;
        }
    }

    return $excludeFiles;
}

function findIlluminateHelperFuncs(): array
{
    $helpersFuncs = [];

    $funcs = get_defined_functions();
    foreach ($funcs['user'] as $func) {
        $ref = new \ReflectionFunction($func);

        if (!str_contains($ref->getFileName(), 'third-party-src/vendor/illuminate')) {
            continue;
        }

        $filename = $ref->getFileName();

        $filename = str_replace(dirname(__DIR__), '', $filename);

        echo "> {$func} => {$filename}" . PHP_EOL;

        $helpersFuncs[] = $func;
    }

    return $helpersFuncs;
}

$excludePackages = [
    'illuminate/contracts',
    'illuminate/container',
    'illuminate/macroable',
    'illuminate/conditionable',
];
$excludeFiles = getPackagesFilesRelativePathname($excludePackages);

$helpersFuncs = findIlluminateHelperFuncs();
$helpersFuncMatchStr = join('|', array_map('\preg_quote', $helpersFuncs));

function strPosGetLine(string $text, int $pos, ?int &$relativePos = null): string {
    $lineStart = $pos;
    $lineEnd = $pos;

    // 向前遍历，找到所在行的起始位置
    while ($lineStart > 0 && $text[$lineStart - 1] !== "\n") {
        $lineStart--;
    }

    // 向后遍历，找到所在行的结束位置
    while ($lineEnd < strlen($text) && $text[$lineEnd] !== "\n") {
        $lineEnd++;
    }

    $relativePos = $pos - $lineStart;

    return substr($text, $lineStart, $lineEnd - $lineStart);
}

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

return [
    // The prefix configuration. If a non null value is be used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => '__Illuminate',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        // Finder::create()->files()->in('src'),
        Finder::create()
            ->files()
            ->ignoreVCS(true)
//            ->name('/.*\\.php\\.dist|.*\\.php|composer\\.json/')
            ->notName('/LICENSE|.*\\.md|.*\\.neon|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'doc',
                'docs',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
        Finder::create()->append([
            'composer.json',
        ]),
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->name('/LICENSE|LICENSE.md/')
            ->in('vendor'),
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        'composer.json',
        'vendor/composer/installed.json',
        'vendor/composer/installed.php',
        'vendor/composer/InstalledVersions.php',
        ...$excludeFiles,
    ],

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents) use ($helpersFuncMatchStr, $helpersFuncs) : string {
            // Change the contents here.

            if (preg_match("/\\\\true|\\\\false/", $contents)) {
                $contents = str_replace([
                    '\\true',
                    '\\false',
                ], [
                    'true',
                    'false',
                ], $contents);
            }

            $re = "/([^A-Z0-9_\/$])({$helpersFuncMatchStr})([^A-Z0-9_{}])/i";

            if (str_contains($filePath, 'third-party-src/vendor/illuminate')
                && str_ends_with($filePath, '.php')
                && preg_match($re, $contents)
            ) {
                $contents = preg_replace_callback($re, static function ($matches) use ($contents, $prefix, $filePath, $helpersFuncs) {

                    $fullText = $matches[0][0];

                    $pos = $matches[2][1];

                    $func = $matches[2][0];

                    $line = strPosGetLine($contents, $pos, $relativePos);

                    if (preg_match('#^\s*?(//|\*)#', $line)) {
                        // 忽略注释
                        return $fullText;
                    }

                    if (preg_match(sprintf("#function\s+%s#", preg_quote($func)), $line)) {
                        // 忽略函数声明
                        return $fullText;
                    }

                    $testObjectMethod = substr($line, $relativePos - 2, 2 + strlen($func));

                    if (str_starts_with($testObjectMethod, '->') || str_starts_with($testObjectMethod, '::')) {
                        // 忽略对象方法调用
                        return $fullText;
                    }

                    if (!in_array($func, $helpersFuncs)) {
                        return $fullText;
                    }

                    $filePath = basename($filePath);

                    echo sprintf("  > %s::%s >> %s", $filePath, $func, trim($line)) . PHP_EOL;

                    return sprintf("%s\%s\%s%s", $matches[1][0], $prefix, $func, $matches[3][0]);
                }, $contents, -1, $count, PREG_OFFSET_CAPTURE);
            }

            return $contents;
        },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        'Composer',
        'Psr',
        'Symfony',
        'Carbon',
        'Doctrine',
        'League',
        'Ramsey',
        'voku',
        'PHPUnit',
        'Illuminate',
    ],
    'exclude-classes' => [
        'Illuminate\Support\Traits\Macroable'
    ],
    'exclude-functions' => [
        // 'mb_str_split',
    ],
    'exclude-constants' => [
        // 'STDIN',
    ],

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => [
    ],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
