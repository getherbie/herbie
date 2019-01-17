<?php

ini_set('memory_limit', '500M');
$data = range(0, 1000000);

// array_filter loop average 0.74 seconds
$start = microtime(true);
$newData = array_filter($data, function ($item) {
    return $item%2;
});
$end = microtime(true);

echo $end - $start . '<br>';

// Foreach loop average 0.37 seconds
$start = microtime(true);
$newData = array();
foreach ($data as $item) {
    if ($item%2) {
        $newData[] = $item;
    }
}
$end = microtime(true);

echo $end - $start . '<br>';

// For loop average 0.61 seconds
$start = microtime(true);
$newData = array();
$numItems = count($data);
for($i=0;$i<$numItems;$i++) {
    if ($data[$i]%2) {
        $newData[] = $data[$i];
    }
}
$end = microtime(true);

echo $end - $start . '<br>';

// While loop average 0.58 seconds
$start = microtime(true);
$newData = array();
$numItems = count($data);
$i = 0;
while ($i < $numItems) {
    if ($data[$i]%2) {
        $newData[] = $data[$i];
    }
    $i++;
}
$end = microtime(true);

echo $end - $start . '<br>';
