<?php

function run($path)
{
    $xml = simplexml_load_file($path);
    $project = $xml->testsuite;

    echo sprintf("total:    %s msec", formatMsec($project['time'])) . PHP_EOL;

    foreach ($project->testsuite as $testsuite) {
        echo sprintf("  suite:  %s msec : %s", formatMsec($testsuite['time']), $testsuite['name']) . PHP_EOL;

        foreach ($testsuite->testcase as $testcase) {
            echo sprintf("    case: %s msec :   %s", printMsec($testcase['time']), $testcase['name']) . PHP_EOL;
        }
    }

    return 0;
}

function msec($str)
{
    return floatval((string)$str) * 1000;
}

function formatMsec($time)
{
    return sprintf('%9.3f', msec($time));
}

function printMsec($time, $warn = 5, $error = 10)
{
    $str = formatMsec($time);

    if (!class_exists('ColorCLI')) {
        return $str;
    }

    $msec = msec($time);

    if ($msec < $warn) {
        return ColorCLI::lightGreen($str);
    } elseif ($msec < $error) {
        return ColorCLI::yellow($str);
    }

    return ColorCLI::red($str);
}

$colorCli = realpath(__DIR__ . '/ColorCLI.php');

if (file_exists($colorCli)) {
    include_once $colorCli;
}

$xmlFileName = "junit.xml";
$root = realpath(__DIR__ . "/..");
$path = realpath("$root/logs/$xmlFileName");

if ($path === false || !file_exists($path)) {
    die("Not found $xmlFileName");
}

return run($path);
