<?php
declare(strict_types=1);

$converter = getenv('CONVERT_BIN') ?: 'convert';

$start = microtime(true);
foreach (new DirectoryIterator('./photos') as $item){
    if ($item->getExtension() === 'tiff'){
        $source = $item->getPathname();
        $dest = './photos/export/'.substr($item->getFilename(), 0, -5).'.jpg';

        $cmd = $converter . ' ' . $source . ' ' . $dest;
        exec($cmd, $output, $ret);
        if ($ret !== 0){
            throw new RuntimeException('Failed to convert '.$item->getFilename());
        }

        echo 'Successfully converted ' . $source . ' => ' . $dest . PHP_EOL;
    }
}
$end = microtime(true);
echo 'Directory processed in ' . round($end - $start, 1) . ' seconds' . PHP_EOL;
