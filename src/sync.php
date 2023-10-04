<?php

declare(strict_types=1);

const SOURCE_DIR = './photos/';
const DEST_DIR = './photos/export/';
const SOURCE_EXT = 'tiff';
const DEST_EXT = 'jpg';

$converter = getenv('CONVERT_BIN') ?: 'convert';

$start = microtime(true);
foreach (new DirectoryIterator(SOURCE_DIR) as $item) {
    if ($item->getExtension() === SOURCE_EXT) {
        $source = $item->getPathname();
        $dest = getDestPath($item);

        $cmd = $converter . ' ' . $source . ' ' . $dest;
        exec($cmd, $output, $ret);
        if ($ret !== 0) {
            throw new RuntimeException('Failed to convert ' . $item->getFilename());
        }

        echo 'Successfully converted ' . $source . ' => ' . $dest . PHP_EOL;
    }
}
$end = microtime(true);
echo 'Directory processed in ' . round($end - $start, 1) . ' seconds' . PHP_EOL;

function getDestPath(DirectoryIterator $file): string
{
    return DEST_DIR . substr($file->getFilename(), 0, -5) . DEST_EXT;
}
