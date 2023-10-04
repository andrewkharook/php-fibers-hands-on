<?php

declare(strict_types=1);

/**
 * Refactored the code to be non-blocking.
 * The code will start our conversion process and will keep running
 * while the converter process does its work rather than wait for the result.
 * We achieve this by replacing the simple `exec` function call with `proc_open`.
 * Our code then will sit in a loop polling the status of the other processes to see if its completed or not
 */

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

    $proc = proc_open($cmd, $streams, $pipes);
    if (!$proc) {
        throw new RuntimeException('Unable to start conversion process');
    }

    do {
        usleep(1000);
        $status = proc_get_status($proc);
        // var_dump($status);
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
