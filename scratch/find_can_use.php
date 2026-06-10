<?php
$js = file_get_contents('public/assets/js/app.js');
$lines = explode("\n", $js);

foreach ($lines as $lineNum => $line) {
    if (strpos($line, 'canUsePage') !== false && strpos($line, 'function') !== false) {
        echo "Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
    }
}
?>
