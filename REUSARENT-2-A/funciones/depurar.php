<?php

    function depurar($entrada){
        if(is_array($entrada)) return $entrada; //No procesar arrays
        $salida = htmlspecialchars($entrada); //Prevenir los XSS
        $salida = trim($salida);//Eliminar los espacios en blanco delos extremos de la cadena
        $salida = preg_replace("/\s+/"," ", $salida);//Un solo espacio en blanco no más

        return $salida;
    }

    function depurarContrasena($entrada){
        if(is_array($entrada)) return $entrada; //No procesar arrays
        $salida = htmlspecialchars($entrada); //Prevenir los XSS
        $salida = trim($salida);//Eliminar los espacios en blanco delos extremos de la cadena
        $salida = preg_replace("/\s+/","", $salida);//Un solo espacio en blanco no más

        return $salida;
    }

    function depurarUsuario($entrada){
        if(is_array($entrada)) return $entrada; //No procesar arrays
        $salida = htmlspecialchars($entrada); //Prevenir los XSS
        $salida = trim($salida);//Eliminar los espacios en blanco delos extremos de la cadena
        $salida = preg_replace("/\s+/","_", $salida);//Un solo espacio en blanco no más

        return $salida;
    }

?>