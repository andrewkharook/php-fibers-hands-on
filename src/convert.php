<?php

declare(strict_types=1);

const SOURCE_DIR = './photos/';
const DEST_DIR = './photos/export/';
const SOURCE_EXT = 'tiff';
const DEST_EXT = 'jpg';

$converter = getenv('CONVERTER_BIN') ?: 'convert';
$processesNumber = 4;
$fiberList = [];

$start = microtime(true);
foreach (new DirectoryIterator(SOURCE_DIR) as $item) {
    if ($item->getExtension() === SOURCE_EXT) {
        $src = $item->getPathname();
        $dest = getDestPath($item);

        $fiber = new Fiber(convertPhoto(...));
        $fiber->start($converter, $src, $dest);
        $fiberList[] = $fiber;
        if (count($fiberList) >= $processesNumber) {
            foreach (waitForFibers($fiberList, 1) as $fiber) {
                [$src, $dest] = $fiber->getReturn();
                echo 'Successfully converted ' . $src . ' => ' . $dest . PHP_EOL;
            }
        }
    }
}

foreach (waitForFibers($fiberList) as $fiber) {
    [$src, $dest] = $fiber->getReturn();
    echo 'Successfully converted ' . $src . ' => ' . $dest . PHP_EOL;
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

/**
 * @param Fiber[] $fiberList Reference to the list of fibers
 * @param int|null $completeLimit Minimum number of fibers that need to finish the job for the function to return
 * @return Fiber[]
 * @throws Throwable
 */
function waitForFibers(array &$fiberList, ?int $completeLimit = null): array
{
    $completed = [];
    $completeLimit ??= count($fiberList);
    while (count($fiberList) && count($completed) < $completeLimit) {
        usleep(1000);
        foreach ($fiberList as $id => $fiber) {
            if ($fiber->isSuspended()) {
                $fiber->resume();
            } elseif ($fiber->isTerminated()) {
                $completed[] = $fiber;
                unset($fiberList[$id]);
            }
        }
    }

    return $completed;
}
