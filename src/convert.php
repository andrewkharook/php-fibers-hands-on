<?php

declare(strict_types=1);

const SOURCE_DIR = './photos/';
const DEST_DIR = './photos/export/';
const SOURCE_EXT = 'tiff';
const DEST_EXT = 'jpg';

$converter = getenv('CONVERTER_BIN') ?: 'convert';

$fiberList = [];

$start = microtime(true);
foreach (new DirectoryIterator(SOURCE_DIR) as $item) {
    if ($item->getExtension() === SOURCE_EXT) {
        $src = $item->getPathname();
        $dest = getDestPath($item);

        /*
         * We create a new fiber for each file that we want to convert
         */
        $fiber = new Fiber(convertPhoto(...));
        $fiber->start($converter, $src, $dest);
        $fiberList[] = $fiber;
    }
}

/*
 * Loop over the fibers, resuming each one until they all terminate, and we can get the conversion result.
 */
while ($fiberList) {
    foreach ($fiberList as $id => $fiber) {
        if ($fiber->isTerminated()) {
            [$src, $dest] = $fiber->getReturn();
            echo 'Successfully converted ' . $src . ' => ' . $dest . PHP_EOL;
            unset($fiberList[$id]);
        } else {
            $fiber->resume();
        }
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
        /*
         * Fiber::suspend() lets PHP continue running the main process without waiting for conversion to complete.
         * We need to resume it at come point (line 39) to get the conversion result, otherwise fiber process will run forever.
         * That's why we've put all fibers on the list (line 23), so we could iterate through them, resume and get a result when ready.
         */
        Fiber::suspend();
        $status = proc_get_status($proc);
    } while ($status['running']);

    proc_close($proc);
    fclose($stdout);
    fclose($stderr);
    $success = $status['exitcode'] === 0;
    if ($success) {
        return [$src, $dest];
    } else {
        throw new RuntimeException('Unable to perform conversion');
    }
}
