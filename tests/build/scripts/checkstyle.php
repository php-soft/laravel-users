<?php

function run($path)
{
    $xml = simplexml_load_file($path);

    foreach ($xml->file as $file) {
        echo sprintf("file: %s", $file['name']) . PHP_EOL;
        foreach ($file->error as $violation) {
            echo "  " . printMessage($violation) . PHP_EOL;
            echo sprintf(
                "    severity: %s rule: %s at line %s column %s",
                $violation['severity'],
                $violation['source'],
                $violation['line'],
                $violation['column']
            ),
            PHP_EOL;
        }
    }

    return 0;
}

function printMessage($violation)
{
    $str = $violation['message'];

    if (!class_exists('ColorCLI')) {
        return $str;
    }

    $severity = $violation['severity'];

    if ($severity == 'error') {
        return ColorCLI::red($str);
    } elseif ($severity == 'warning') {
        return ColorCLI::yellow($str);
    }

    return ColorCLI::cyan($str);
}

function checkFile($xmlFileName)
{
    $root = realpath(__DIR__ . "/..");
    $path = realpath("$root/logs/$xmlFileName");

    if ($path === false || !file_exists($path)) {
        return "Not found $xmlFileName";
    }

    return run($path);
}

$colorCli = realpath(__DIR__ . '/ColorCLI.php');

if (file_exists($colorCli)) {
    include_once $colorCli;
}

define('NORMAL_PRIORITY', 3);


$result = array(
    checkFile("checkstyle.xml"),
    // checkFile("checkstyle-apigen.xml"),
);

foreach ($result as $value) {
    if (is_string($value)) {
        echo $value, PHP_EOL;
    }
}
