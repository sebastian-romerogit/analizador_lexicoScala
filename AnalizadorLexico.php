<?php
require_once 'Categoria.php';
require_once 'Token.php';

class AnalizadorLexico {
    // Palabras reservadas de Scala
    public $palabrasReservadas = [
        'abstract', 'case', 'catch', 'class', 'def', 'do', 'else', 'extends', 'false', 'final', 'finally', 'for', 'forSome', 'if', 'implicit', 'import',
        'lazy', 'match', 'new', 'null', 'object', 'override', 'package', 'private', 'protected', 'return', 'sealed', 'super', 'this', 'throw', 'trait',
        'true', 'try', 'type', 'val', 'var', 'while', 'with', 'yield'
    ];

    // Autómata para identificadores y palabras reservadas
    private function automataIdentificador($codigoFuente, &$i, $longitud) {
        $inicio = $i;
        $palabra = '';
        while ($i < $longitud && (ctype_alnum($codigoFuente[$i]) || $codigoFuente[$i] === '_')) {
            $palabra .= $codigoFuente[$i];
            $i++;
        }
        $categoria = in_array(strtolower($palabra), $this->palabrasReservadas) ? Categoria::PALABRA_RESERVADA : Categoria::IDENTIFICADOR;
        return new Token($palabra, $categoria, $inicio, $i - 1);
    }

    // Autómata para números
    private function automataNumero($codigoFuente, &$i, $longitud) {
        $inicio = $i;
        $numero = '';
        $esDecimal = false;
        while ($i < $longitud && (ctype_digit($codigoFuente[$i]) || ($codigoFuente[$i] === '.' && !$esDecimal))) {
            if ($codigoFuente[$i] === '.') {
                $esDecimal = true;
            }
            $numero .= $codigoFuente[$i];
            $i++;
        }
        $categoria = $esDecimal ? Categoria::DECIMAL : Categoria::ENTERO;
        return new Token($numero, $categoria, $inicio, $i - 1);
    }

    // Autómata para cadenas de caracteres
    private function automataCadena($codigoFuente, &$i, $longitud) {
        $inicio = $i;
        $delimitador = $codigoFuente[$i];
        $cadena = $delimitador;
        $i++;
        while ($i < $longitud) {
            if ($codigoFuente[$i] === '\\' && $i + 1 < $longitud && $codigoFuente[$i + 1] === $delimitador) {
                $cadena .= $codigoFuente[$i];
                $cadena .= $codigoFuente[$i + 1];
                $i += 2;
                continue;
            }
            if ($codigoFuente[$i] === $delimitador) {
                break;
            }
            $cadena .= $codigoFuente[$i];
            $i++;
        }
        if ($i < $longitud) {
            $cadena .= $delimitador;
            $i++;
        }
        return new Token($cadena, Categoria::CADENA_CARACTERES, $inicio, $i - 1);
    }

    // Autómata para comentario de línea
    private function automataComentarioLinea($codigoFuente, &$i, $longitud) {
        $inicio = $i;
        $comentario = '';
        while ($i < $longitud && $codigoFuente[$i] !== "\n") {
            $comentario .= $codigoFuente[$i];
            $i++;
        }
        return new Token($comentario, Categoria::COMENTARIO_LINEA, $inicio, $i - 1);
    }

    // Autómata para comentario de bloque
    private function automataComentarioBloque($codigoFuente, &$i, $longitud) {
        $inicio = $i;
        $comentario = '';
        $i += 2;
        $comentario .= '/*';
        while ($i < $longitud && !($codigoFuente[$i] === '*' && $i + 1 < $longitud && $codigoFuente[$i + 1] === '/')) {
            $comentario .= $codigoFuente[$i];
            $i++;
        }
        if ($i + 1 < $longitud) {
            $comentario .= '*/';
            $i += 2;
        }
        return new Token($comentario, Categoria::COMENTARIO_BLOQUE, $inicio, $i - 1);
    }

    // Autómata para operadores y símbolos
    private function automataOperadores($codigoFuente, &$i, $longitud) {
        $operadoresAritmeticos = ['+', '-', '*', '/', '%'];
        $operadoresRelacionales = ['==', '!=', '<=', '>=', '<', '>'];
        $operadoresLogicos = ['&&', '||', '!'];
        $operadoresIncremento = ['++', '--'];
        $operadoresCompuestos = ['+=', '-='];
        // Operador de asignación
        if ($codigoFuente[$i] === '=') {
            // Evitar confundir con '=='
            if (!($i + 1 < $longitud && $codigoFuente[$i + 1] === '=')) {
                $token = new Token('=', Categoria::OPERADOR_ASIGNACION, $i, $i);
                $i++;
                return $token;
            }
        }

        // Operadores compuestos
        if ($i + 1 < $longitud) {
            $opComp = $codigoFuente[$i] . $codigoFuente[$i + 1];
            for ($j = 0; $j < count($operadoresCompuestos); $j++) {
                if ($opComp === $operadoresCompuestos[$j]) {
                    $token = new Token($opComp, Categoria::OPERADOR_COMPUESTO, $i, $i + 1);
                    $i += 2;
                    return $token;
                }
            }
        }

        $caracter = $codigoFuente[$i];

        // Llaves
        if ($caracter === '{' || $caracter === '}') {
            $token = new Token($caracter, Categoria::LLAVE, $i, $i);
            $i++;
            return $token;
        }

        // Operador de incremento
        if ($i + 1 < $longitud) {
            $opInc = $caracter . $codigoFuente[$i + 1];
            for ($j = 0; $j < count($operadoresIncremento); $j++) {
                if ($opInc === $operadoresIncremento[$j]) {
                    $token = new Token($opInc, Categoria::OPERADOR_INCREMENTO, $i, $i + 1);
                    $i += 2;
                    return $token;
                }
            }
        }

        // Operador relacional
        for ($j = 0; $j < count($operadoresRelacionales); $j++) {
            $op = $operadoresRelacionales[$j];
            $len = strlen($op);
            if ($i + $len - 1 < $longitud && substr($codigoFuente, $i, $len) === $op) {
                $token = new Token($op, Categoria::OPERADOR_RELACIONAL, $i, $i + $len - 1);
                $i += $len;
                return $token;
            }
        }

        // Operador lógico
        for ($j = 0; $j < count($operadoresLogicos); $j++) {
            $op = $operadoresLogicos[$j];
            $len = strlen($op);
            if ($i + $len - 1 < $longitud && substr($codigoFuente, $i, $len) === $op) {
                $token = new Token($op, Categoria::OPERADOR_LOGICO, $i, $i + $len - 1);
                $i += $len;
                return $token;
            }
        }

        // Operador aritmético
        for ($j = 0; $j < count($operadoresAritmeticos); $j++) {
            if ($caracter === $operadoresAritmeticos[$j]) {
                $token = new Token($caracter, Categoria::OPERADOR_ARITMETICO, $i, $i);
                $i++;
                return $token;
            }
        }

        // Paréntesis y punto y coma
        if ($caracter === '(' || $caracter === ')' || $caracter === ';') {
            $token = new Token($caracter, Categoria::NO_RECONOCIDO, $i, $i); // Se puede crear una categoría específica si se desea
            $i++;
            return $token;
        }

        // No reconocido
        $token = new Token($caracter, Categoria::NO_RECONOCIDO, $i, $i);
        $i++;
        return $token;
    }

    public function analizar($codigoFuente) {
        $tokens = array();
        $longitud = strlen($codigoFuente);
        $i = 0;

        while ($i < $longitud) {
            $caracter = $codigoFuente[$i];

            // Saltar espacios y saltos de línea
            if (ctype_space($caracter)) {
                $i++;
                continue;
            }

            // Identificador o palabra reservada
            if (ctype_alpha($caracter) || $caracter === '_') {
                $tokens[] = $this->automataIdentificador($codigoFuente, $i, $longitud);
                continue;
            }

            // Número entero o decimal
            if (ctype_digit($caracter)) {
                $tokens[] = $this->automataNumero($codigoFuente, $i, $longitud);
                continue;
            }

            // Cadena de caracteres
            if ($caracter === '"' || $caracter === "'") {
                $tokens[] = $this->automataCadena($codigoFuente, $i, $longitud);
                continue;
            }

            // Comentario de línea
            if ($caracter === '/' && $i + 1 < $longitud && $codigoFuente[$i + 1] === '/') {
                $tokens[] = $this->automataComentarioLinea($codigoFuente, $i, $longitud);
                continue;
            }

            // Comentario de bloque
            if ($caracter === '/' && $i + 1 < $longitud && $codigoFuente[$i + 1] === '*') {
                $tokens[] = $this->automataComentarioBloque($codigoFuente, $i, $longitud);
                continue;
            }

            // Operadores y otros símbolos
            $tokens[] = $this->automataOperadores($codigoFuente, $i, $longitud);
        }

        return $tokens;
    }
}
