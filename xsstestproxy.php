<?php
declare(strict_types=1);

$input = file_get_contents('php://stdin');
$data = json_decode($input, true);

require_once 'application/modules/editor/src/Tools/XssInjector.php';

$xssInjector = new MittagQI\Translate5\Tools\XssInjector();

if ($data) {
    echo json_encode($xssInjector->process($_SERVER["REQUEST_URI"], $data));
} else {
    echo $input; // fallback
}
