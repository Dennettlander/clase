<?php

require('UploadMultiple.php');
$archivo = new UploadMultiple('archivos0');
$archivo->setPolicy(UploadMultiple::POLICY_RENAME);

echo 'Archivos subidos: ' . $archivo->upload() . '<br>';

$nombreArchivos = $archivo->getNames();
$numeroArchivos = count($nombreArchivos);
for ($i = 0; $i < $numeroArchivos; $i++) { 
    echo $i+1 . 'ª archivo subido como ' . $nombreArchivos[$i] . '<br>';
}
