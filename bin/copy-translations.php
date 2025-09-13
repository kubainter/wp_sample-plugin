<?php
$wpRootDir = dirname(dirname(dirname(dirname(__DIR__))));
$wpLangDir = $wpRootDir . '/wp-content/languages';

$pluginsLangDir = $wpLangDir . '/plugins';
if (!is_dir($pluginsLangDir)) {
    if (!is_dir($wpLangDir)) {
        mkdir($wpLangDir, 0755, true);
    }
    mkdir($pluginsLangDir, 0755, true);
}

$source = __DIR__ . '/../languages/graduates-pl_PL.mo';
$dest = $pluginsLangDir . '/graduates-pl_PL.mo';

if (file_exists($source)) {
    if (copy($source, $dest)) {
        echo "Successfully copied translations to: $dest" . PHP_EOL;
        exit(0);
    } else {
        echo "Failed to copy translations to: $dest" . PHP_EOL;
        exit(1);
    }
} else {
    echo "Source translation file not found: $source" . PHP_EOL;
    exit(1);
}
