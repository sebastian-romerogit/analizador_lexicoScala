<?php
require_once 'Categoria.php';
require_once 'Token.php';
require_once 'AnalizadorLexico.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$codigoFuente = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (isset($data['codigoFuente'])) {
        $codigoFuente = $data['codigoFuente'];
    }
}

$analizador = new AnalizadorLexico();
$tokens = $analizador->analizar($codigoFuente);

$respuesta = [];
foreach ($tokens as $token) {
    $respuesta[] = [
        'palabra' => $token->palabra,
        'categoria' => $token->categoria,
        'indiceInicio' => $token->indiceInicio,
        'indiceFin' => $token->indiceFin
    ];
}

echo json_encode($respuesta);
