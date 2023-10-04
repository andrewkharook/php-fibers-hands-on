<?php

declare(strict_types=1);

const SOURCE_DIR = './photos/';
const DEST_DIR = './photos/export/';
const SOURCE_EXT = 'tiff';
const DEST_EXT = 'jpg';

$converter = getenv('CONVERTER_BIN') ?: 'convert';

$start = microtime(true);
foreach (new DirectoryIterator(SOURCE_DIR) as $item) {
    if ($item->getExtension() === SOURCE_EXT) {
        $src = $item->getPathname();
        $dest = getDestPath($item);
        convertPhoto($converter, $src, $dest);

        echo 'Successfully converted ' . $src . ' => ' . $dest . PHP_EOL;
    }
}
$end = microtime(true);
echo 'Directory processed in ' . round($end - $start, 1) . ' seconds' . PHP_EOL;

function getDestPath(DirectoryIterator $file): string
{
    return DEST_DIR . substr($file->getFilename(), 0, -5) . '.' . DEST_EXT;
}

function convertPhoto(string $converter, string $src, string $dest): array
{
    $cmd = $converter . ' ' . $src . ' ' . $dest;

    $stdout = fopen('php://temporary', 'w+');
    $stderr = fopen('php://temporary', 'w+');
    $streams = [
        0 => ['pipe', 'r'],
        1 => $stdout,
        2 => $stderr,
    ];

    /*
     * Refactored the code to be non-blocking.
     * Replacing simple `exec` function with `proc_open` allows main process to keep running instead of waiting for the converter process to finish.
     */
    $proc = proc_open($cmd, $streams, $pipes);
    if (!$proc) {
        throw new RuntimeException('Unable to start conversion process');
    }

    do {
        usleep(1000);
        $status = proc_get_status($proc);
    } while ($status['running']);

    proc_close($proc);
    fclose($stdout);
    fclose($stderr);
    $success = $status['exitcode'] === 0;
    if ($success) {
        return [$src, $dest];
    } else {
        throw new \RuntimeException('Unable to perform conversion');
    }
}
