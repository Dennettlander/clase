<?php

class UploadMultiple {

    const   POLICY_KEEP = 1,
            POLICY_OVERWRITE = 2,
            POLICY_RENAME = 3;

    private $error = false,
            $files,
            $maxSize = 0,
            $name = '',
            $policy = self::POLICY_OVERWRITE,
            $savedNames = array(),
            $target = './',
            $type = '';

    function __construct($input) {
        if(isset($_FILES[$input])) {
            $this->files = $_FILES[$input];
        } else {
            $this->error = true;
        }
    }

    private function __doUpload($file, $index) {
        $result = false;
        switch($this->policy) {
            case self::POLICY_KEEP:
                $result = $this->__doUploadKeep($file, $index);
                break;
            case self::POLICY_OVERWRITE:
                $result = $this->__doUploadOverwrite($file, $index);
                break;
            case self::POLICY_RENAME:
                $result = $this->__doUploadRename($file, $index);
                break;
        }
        return $result;
    }

    private function __doUploadKeep($file, $index) {
        $result = false;
        $name = $this->__getFileName($file);
        if(!file_exists($this->target . $name)) {
            $result = $this->__move($file, $this->target . $name, $index);
        } else {
            $this->savedNames[$index] = 'policy error';
        }
        return $result;
    }

    private function __doUploadOverwrite($file, $index) {
        $name = $this->__getFileName($file);
        $result = $this->__move($file, $this->target . $name, $index);
        return $result;
    }

    private function __doUploadRename($file, $index) {
        $name = $this->__getFileName($file);
        $newName = $this->target . $name;
        if(file_exists($newName)) {
            $newName = self::__getValidName($newName);
        }
        $result = $this->__move($file, $newName, $index);
        return $result;
    }

    private function __getFileName($file) {
        $name = $file['name'];
        if($this->name !== '') {
            $name = $this->name;
        }
        return $name;
    }

    private function __getOrderedFiles() {
        $files = array();
        $names = $this->files['name'];//cojo todos los nombres que hay en files
        if(is_array($names)) {//si names es un array
            $files = $this->__reOrder($this->files);//reordeno files según el índice ofrecido por el navegador
        } else {//si no
            $files[] = $this->files;//relleno el array con el único file
        }
        return $files;//devuelvo array
    }

    private static function __getValidName($file) {
        $parts = pathinfo($file);
        $extension = '';
        if(isset($parts['extension'])) {
            $extension = '.' . $parts['extension'];
        }
        $cont = 0;
        while(file_exists($parts['dirname'] . '/' . $parts['filename'] . $cont . $extension)) {
            $cont++;
        }
        return $parts['dirname'] . '/' . $parts['filename'] . $cont . $extension;
    }

    private function __move($file, $name, $index) {
        $result = move_uploaded_file($file['tmp_name'], $name);//subo el archivo con su nombre temporal a la ruta $name
        if($result) {//si va bien
            $nameParts = pathinfo($name);
            $this->savedNames[$index] = $nameParts['basename'];//asigno su nombre al índice del archivo en la lista de archivos subidos 
        } else {
            $this->savedNames[$index] = 'move error';//si no, asigno un mensaje de error al índice del archivo en la lista de archivos subidos 
        }
        return $result;
    }

    private static function __reOrder(array $array) {
        $ordered = array();
        foreach($array as $key => $all) {//por cada atributo/campo
            foreach($all as $index => $value) {//cojo el array ofrecido por el navegador
                $ordered[$index][$key] = $value;//asigno el valor del array a un array bidimensional en la posición del índice ofrecido por el navegador y el campo
            }
        }
        return $ordered;
    }

    private function __uploadMultiple($files) {
        $result = 0;//cantidad de archivos subidos
        foreach($files as $index => $file) {//por cada archivo, según su índice recién ordenado
            if($file['error'] === 0 && $this->isValidSize($file) && $this->isValidType($file)) {//si el error del archivo es 0 Y tamaño y tipo son correctos 
                if($this->__doUpload($file, $index)) {//si se puede hacer doUpload con su archivo y su índice 
                    $result++;//anotar archivo subido
                }
            } else {
                $this->savedNames[$index] = 'upload, size or type error';//si no, mensaje de error según índice
            }
        }
        return $result;//total definitivo de archivos subidos
    }

    function getError() {
        return $this->error;
    }

    function getMaxSize() {
        return $this->maxSize;
    }

    function getNames() {
        return $this->savedNames;
    }

    function isValidSize($size) {
        return ($this->maxSize === 0 || $this->maxSize >= $size);
    }

    function isValidType($file) {
        $valid = true;
        if($this->type !== '') {
            $type = shell_exec('file --mime ' . $file);
            $posicion = strpos($type, $this->type);
            if($posicion === false) {
                $valid = false;
            }
        }
        return $valid;
    }

    function setMaxSize($size) {
        if(is_int($size) && $size > 0) {
            $this->maxSize = $size;
        }
        return $this;
    }

    function setName($name) {
        if(is_string($name) && trim($name) !== '') {
            $this->name = trim($name);
        }
        return $this;
    }

    function setPolicy($policy) {
        if(is_int($policy) && $policy >= self::POLICY_KEEP && $policy <= self::POLICY_RENAME) {
            $this->policy = $policy;
        }
        return $this;
    }

    function setTarget($target) {
        if(is_string($target) && trim($target) !== '') {
            $this->target = trim($target);
        }
        return $this;
    }

    function setType($type) {
        if(is_string($type) && trim($type) !== '') {
            $this->type = trim($type);
        }
        return $this;
    }

    function upload() {
        $result = 0;
        if(!$this->error) {
            $files = $this->__getOrderedFiles();//revisamos si hay varios archivos y si hay que ordenarlos
            $result = $this->__uploadMultiple($files);//subir archivos o dar mensaje error
        }
        return $result;//total definitivo de archivos subidos
    }

}