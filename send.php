<?php

$content = file_get_contents('last.txt');
$lines = explode("\n", $content);
$line = array_shift($lines);
file_put_contents('last.txt', implode("\n", $lines));

echo empty($line) ? '{}' : $line;