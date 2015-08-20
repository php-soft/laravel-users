<?php

function run($path)
{
    $xml = simplexml_load_file($path);

    foreach ($xml->file as $file) {
        echo sprintf("file: %s", $file['name']) . PHP_EOL;
        foreach ($file->violation as $violation) {
            echo "  " . printMessage($violation) . PHP_EOL;
            echo sprintf(
                "    priority: %s rule: %s:%s at line %s - %s",
                $violation['priority'],
                $violation['ruleset'],
                $violation['rule'],
                $violation['beginline'],
                $violation['endline']
            ),
            PHP_EOL;
        }
    }

    return 0;
}

function isHighPriority($priority)
{
    // red
    return $priority < NORMAL_PRIORITY;
}

function isNormatPriority($priority)
{
    // yellow
    return $priority == NORMAL_PRIORITY;
}

function isLowPriority($priority)
{
    return $priority > NORMAL_PRIORITY;
}

function printMessage($violation)
{
    $str = formatMessage($violation);

    if (!class_exists('ColorCLI')) {
        return $str;
    }

    $priority = $violation['priority'];

    if (isHighPriority($priority)) {
        return ColorCLI::red($str);
    } elseif (isNormatPriority($priority)) {
        return ColorCLI::yellow($str);
    }

    return ColorCLI::cyan($str);
}

function formatMessage($violation)
{
    return trim($violation);
}

$colorCli = realpath(__DIR__ . '/ColorCLI.php');

if (file_exists($colorCli)) {
    include_once $colorCli;
}

$xmlFileName = "pmd.xml";
$root = realpath(__DIR__ . "/..");
$path = realpath("$root/logs/$xmlFileName");

if ($path === false || !file_exists($path)) {
    die("Not found $xmlFileName");
}

define('NORMAL_PRIORITY', 3);

return run($path);
