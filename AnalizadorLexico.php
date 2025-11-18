<?php
require_once 'Categoria.php';
require_once 'Token.php';

class AnalizadorLexico {
    // Autómata para operador de asignación
    private function automataOperadorAsignacion($codigoFuente, &$i, $longitud) {
        if ($codigoFuente[$i] === '=') {
            if (!($i + 1 < $longitud && $codigoFuente[$i + 1] === '=')) {
                $token = new Token('=', Categoria::OPERADOR_ASIGNACION, $i, $i);
                $i++;
                return $token;
            }
        }
        return null;
    }

    // Autómata para operadores compuestos
    private function automataOperadorCompuesto($codigoFuente, &$i, $longitud) {
        $operadoresCompuestos = ['+=', '-='];
        if ($i + 1 < $longitud) {
            $opComp = $codigoFuente[$i] . $codigoFuente[$i + 1];
            foreach ($operadoresCompuestos as $op) {
                if ($opComp === $op) {
                    $token = new Token($opComp, Categoria::OPERADOR_COMPUESTO, $i, $i + 1);
                    $i += 2;
                    return $token;
                }
            }
        }
        return null;
    }

    // Autómata para operador de incremento
    private function automataOperadorIncremento($codigoFuente, &$i, $longitud) {
        $operadoresIncremento = ['++', '--'];
        if ($i + 1 < $longitud) {
            $opInc = $codigoFuente[$i] . $codigoFuente[$i + 1];
            foreach ($operadoresIncremento as $op) {
                if ($opInc === $op) {
                    $token = new Token($opInc, Categoria::OPERADOR_INCREMENTO, $i, $i + 1);
                    $i += 2;
                    return $token;
                }
            }
        }
        return null;
    }

    // Autómata para operadores relacionales
    private function automataOperadorRelacional($codigoFuente, &$i, $longitud) {
        $operadoresRelacionales = ['==', '!=', '<=', '>=', '<', '>'];
        foreach ($operadoresRelacionales as $op) {
            $len = strlen($op);
            if ($i + $len - 1 < $longitud && substr($codigoFuente, $i, $len) === $op) {
                $token = new Token($op, Categoria::OPERADOR_RELACIONAL, $i, $i + $len - 1);
                $i += $len;
                return $token;
            }
        }
        return null;
    }

    // Autómata para operadores lógicos
    private function automataOperadorLogico($codigoFuente, &$i, $longitud) {
        $operadoresLogicos = ['&&', '||', '!'];
        foreach ($operadoresLogicos as $op) {
            $len = strlen($op);
            if ($i + $len - 1 < $longitud && substr($codigoFuente, $i, $len) === $op) {
                $token = new Token($op, Categoria::OPERADOR_LOGICO, $i, $i + $len - 1);
                $i += $len;
                return $token;
            }
        }
        return null;
    }

    // Autómata para operadores aritméticos
    private function automataOperadorAritmetico($codigoFuente, &$i, $longitud) {
        $operadoresAritmeticos = ['+', '-', '*', '/', '%'];
        if (in_array($codigoFuente[$i], $operadoresAritmeticos)) {
            $token = new Token($codigoFuente[$i], Categoria::OPERADOR_ARITMETICO, $i, $i);
            $i++;
            return $token;
        }
        return null;
    }

    // Autómata para llaves
    private function automataLlave($codigoFuente, &$i, $longitud) {
        if ($codigoFuente[$i] === '{' || $codigoFuente[$i] === '}') {
            $token = new Token($codigoFuente[$i], Categoria::LLAVE, $i, $i);
            $i++;
            return $token;
        }
        return null;
    }

    // Autómata para paréntesis y punto y coma
    private function automataParentesisPuntoYComa($codigoFuente, &$i, $longitud) {
        if ($codigoFuente[$i] === '(' || $codigoFuente[$i] === ')' || $codigoFuente[$i] === ';') {
            $token = new Token($codigoFuente[$i], Categoria::NO_RECONOCIDO, $i, $i);
            $i++;
            return $token;
        }
        return null;
    }
    // Autómata para caracteres tipo char (ejemplo: 'a')
    private function automataCaracter($codigoFuente, &$i, $longitud)
    {
        $inicio = $i;
        $caracter = '';
        $cerrada = false;
        // Debe iniciar con comilla simple
        if ($codigoFuente[$i] === "'") {
            $caracter .= "'";
            $i++;
            // Verificar si es un caracter escapado
            if ($i < $longitud && $codigoFuente[$i] === '\\') {
                $caracter .= '\\';
                $i++;
                if ($i < $longitud) {
                    $caracter .= $codigoFuente[$i];
                    $i++;
                }
            } elseif ($i < $longitud) {
                $caracter .= $codigoFuente[$i];
                $i++;
            }
            // Cerrar con comilla simple
            if ($i < $longitud && $codigoFuente[$i] === "'") {
                $caracter .= "'";
                $i++;
                $cerrada = true;
            }
        }
        if ($cerrada) {
            return new Token($caracter, Categoria::CARACTER, $inicio, $i - 1);
        } else {
            return new Token($caracter, Categoria::ERROR_CADENA_SIN_CERRAR, $inicio, $i - 1);
        }
    }
    // Palabras reservadas de Scala
    public $palabrasReservadas = [
        'abstract',
        'case',
        'catch',
        'class',
        'def',
        'do',
        'else',
        'extends',
        'false',
        'final',
        'finally',
        'for',
        'forSome',
        'if',
        'implicit',
        'import',
        'lazy',
        'match',
        'new',
        'null',
        'object',
        'override',
        'package',
        'private',
        'protected',
        'return',
        'sealed',
        'super',
        'this',
        'throw',
        'trait',
        'true',
        'try',
        'type',
        'val',
        'var',
        'while',
        'with',
        'yield'
    ];

    // Autómata para identificadores y palabras reservadas
    private function automataIdentificador($codigoFuente, &$i, $longitud)
    {
        $inicio = $i;
        $palabra = '';
        while ($i < $longitud && (ctype_alnum($codigoFuente[$i]) || $codigoFuente[$i] === '_' || $codigoFuente[$i] === '$')) {
            $palabra .= $codigoFuente[$i];
            $i++;
        }
        $esReservada = false;
        foreach ($this->palabrasReservadas as $reservada) {
            if (strlen($palabra) === strlen($reservada)) {
                $coincide = true;
                for ($j = 0; $j < strlen($palabra); $j++) {
                    if (strtolower($palabra[$j]) !== strtolower($reservada[$j])) {
                        $coincide = false;
                        break;
                    }
                }
                if ($coincide) {
                    $esReservada = true;
                    break;
                }
            }
        }
        if (!$esReservada && strlen($palabra) > 15) {
            return new Token($palabra, Categoria::ERROR_IDENTIFICADOR_LARGO, $inicio, $i - 1);
        }
        $categoria = $esReservada ? Categoria::PALABRA_RESERVADA : Categoria::IDENTIFICADOR;
        return new Token($palabra, $categoria, $inicio, $i - 1);
    }

    // Autómata para números
    private function automataNumero($codigoFuente, &$i, $longitud)
    {
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
    private function automataCadena($codigoFuente, &$i, $longitud)
    {
        $inicio = $i;
        $delimitador = $codigoFuente[$i];
        $cadena = $delimitador;
        $i++;
        $cerrada = false;
        while ($i < $longitud) {
            if ($codigoFuente[$i] === '\\' && $i + 1 < $longitud && $codigoFuente[$i + 1] === $delimitador) {
                $cadena .= $codigoFuente[$i];
                $cadena .= $codigoFuente[$i + 1];
                $i += 2;
                continue;
            }
            if ($codigoFuente[$i] === $delimitador) {
                $cerrada = true;
                break;
            }
            $cadena .= $codigoFuente[$i];
            $i++;
        }
        if ($cerrada && $i < $longitud) {
            $cadena .= $delimitador;
            $i++;
            return new Token($cadena, Categoria::CADENA_CARACTERES, $inicio, $i - 1);
        } else {
            return new Token($cadena, Categoria::ERROR_CADENA_SIN_CERRAR, $inicio, $i - 1);
        }
    }

    // Autómata para comentario de línea
    private function automataComentarioLinea($codigoFuente, &$i, $longitud)
    {
        $inicio = $i;
        $comentario = '';
        while ($i < $longitud && $codigoFuente[$i] !== "\n") {
            $comentario .= $codigoFuente[$i];
            $i++;
        }
        return new Token($comentario, Categoria::COMENTARIO_LINEA, $inicio, $i - 1);
    }

    // Autómata para comentario de bloque
    private function automataComentarioBloque($codigoFuente, &$i, $longitud)
    {
        $inicio = $i;
        $comentario = '';
        $i += 2;
        $comentario .= '/*';
        $cerrado = false;
        while ($i < $longitud) {
            if ($codigoFuente[$i] === '*' && $i + 1 < $longitud && $codigoFuente[$i + 1] === '/') {
                $comentario .= '*';
                $comentario .= '/';
                $i += 2;
                $cerrado = true;
                break;
            }
            $comentario .= $codigoFuente[$i];
            $i++;
        }
        if ($cerrado) {
            return new Token($comentario, Categoria::COMENTARIO_BLOQUE, $inicio, $i - 1);
        } else {
            return new Token($comentario, Categoria::ERROR_COMENTARIO_SIN_CERRAR, $inicio, $i - 1);
        }
    }

    // Autómata para operadores y símbolos
    private function automataOperadores($codigoFuente, &$i, $longitud)
    {
        $token = null;
        $token = $this->automataOperadorAsignacion($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataOperadorCompuesto($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataLlave($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataOperadorIncremento($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataOperadorRelacional($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataOperadorLogico($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataOperadorAritmetico($codigoFuente, $i, $longitud);
        if ($token) return $token;
        $token = $this->automataParentesisPuntoYComa($codigoFuente, $i, $longitud);
        if ($token) return $token;
        // No reconocido: solo si no es ASCII básico
        $caracter = $codigoFuente[$i];
        if (ord($caracter) < 32 || ord($caracter) > 126) {
            $token = new Token($caracter, Categoria::NO_RECONOCIDO, $i, $i);
        } else {
            $token = new Token($caracter, Categoria::NO_RECONOCIDO, $i, $i);
        }
        $i++;
        return $token;
    }

    public function analizar($codigoFuente)
    {
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
            if (ctype_alpha($caracter) || $caracter === '_' || $caracter === '$') {
                $tokens[] = $this->automataIdentificador($codigoFuente, $i, $longitud);
                continue;
            }

            // Número entero o decimal
            if (ctype_digit($caracter)) {
                $tokens[] = $this->automataNumero($codigoFuente, $i, $longitud);
                continue;
            }

            // Cadena de caracteres
            if ($caracter === '"') {
                $tokens[] = $this->automataCadena($codigoFuente, $i, $longitud);
                continue;
            }
            // Caracter tipo char (comilla simple)
            if ($caracter === "'") {
                $tokens[] = $this->automataCaracter($codigoFuente, $i, $longitud);
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
