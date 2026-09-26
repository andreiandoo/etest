<?php

require 'vendor/autoload.php';

use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Connectors\BarouConnector;
use App\Services\Sources\Parsers\BarouParser;
use App\Services\Sources\PdfTextExtractor;

$dir = 'docs/barou-2026';
$paths = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

foreach ($it as $f) {
    if ($f->isFile()) {
        $key = str_replace(DIRECTORY_SEPARATOR, '/', substr($f->getPathname(), strlen($dir) + 1));
        $paths[$key] = $f->getPathname();
    }
}

$c = new BarouConnector(new BarouParser, new PdfTextExtractor, new PracticeTestBuilder);
$r = $c->parseDocuments($paths);

echo 'total=', $r['total'], ' ok=', count($r['questions']), ' rejected=', count($r['rejected']), PHP_EOL;

$hist = [];

foreach ($r['rejected'] as $row) {
    $hist[$row['reason']] = ($hist[$row['reason']] ?? 0) + 1;
}

foreach ($hist as $reason => $n) {
    echo $n, ' x ', $reason, PHP_EOL;
}

foreach (array_slice($r['rejected'], 0, 3) as $row) {
    echo '--- ', $row['code'], ' | ', $row['reason'], PHP_EOL, substr($row['text'], 0, 300), PHP_EOL;
}
