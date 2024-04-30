<?php

// Luo halutun mittaisen satunnaismerkkijonon.
// $length = muodostettavan merkkijonon pituus
function generateHash($length) {

    // Määritellään lyhytosoitteessa käytettävät merkit.
    $chars = "2346789BCDFGHJKMPQRTVWXY";

    // Lasketaan käytettävien merkkien lukumäärä.
    $charcount = strlen($chars);

    // Esitellään muuttuja, johon tulosmerkkijono koostetaan.
    $result = "";

    // Toista muodostettavan merkkijonon pituuden mukainen määrä.
    for ($i = 1; $i <= $length; $i++) {

        // Valitaan satunnaisesti merkin järjestysnumero.
        $index = rand(1, $charcount);

        // Haetaan järjestysnumeroa vastaava merkki.
        $char = $chars[$index - 1];

        // Liitetään merkki tulosmuuttujan loppuun.
        $result = $result . $char;

    }

    // Palautetaan muodostettu merkkijono.
    return $result;

}

// Testaa generateHash-funktion toiminnan.
// echo generateHash(5);

?>