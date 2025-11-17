<?php
class Token {
    public $palabra;
    public $categoria;
    public $indiceInicio;
    public $indiceFin;

    public function __construct($palabra, $categoria, $indiceInicio, $indiceFin) {
        $this->palabra = $palabra;
        $this->categoria = $categoria;
        $this->indiceInicio = $indiceInicio;
        $this->indiceFin = $indiceFin;
    }
}
