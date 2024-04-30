<?php

// Tuodaan funktiot.
require_once('utils.php');

// Pagestatus määrittelee mikä sivu tulostetaan.
//  0 = etusivu
//  1 = lisätyn osoitteen tietosivu
// -1 = virheellinen tunniste
// -2 = tietokantavirhe
$pagestatus = 0;

// Tallennetaan perusosoite muuttujaan
$baseurl = "https://neutroni.hayo.fi/~akoivu/redirect/";

// Määritellään yhteys-muuttujat.
// Tietokannan nimi, käyttäjä ja salasana, haetaan palvelimen ympäristömuuttujista.
$dsn = "mysql:host=localhost;dbname={$_SERVER['DB_DATABASE']};charset=utf8mb4";
$user = $_SERVER['DB_USERNAME'];
$pwd = $_SERVER['DB_PASSWORD'];
$options = [
    // Mahdolliset tietokantalauseiden virheet näkyville.
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // Oletus hakutulos assosiatiiviseksi taulukoksi.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Poista valmisteltujen lauseiden emulointi käytöstä.
    PDO::ATTR_EMULATE_PREPARES => false
];

// Tarkistetaan onko lyhennys-nappia painettu.
if (isset($_POST["shorten"])) {

    $url = $_POST["url"];

    try {
        // Tietokantayhteyden avaus.
        $yhteys = new PDO($dsn, $user, $pwd, $options);    
        
        // Valmistellaan kysely joka tarkastaa löytyykö lyhytosoite kannasta.
        $stmt = $yhteys->prepare("SELECT 1 FROM osoite WHERE tunniste = ?");
      
        // Esitellään hash muuttuja, johon lyhytosoite tullaan sijoittamaan.
        $hash = "";

        // Luodaan lyhytosoitteita niin kauan kunnes löytyy
        // sellainen jota kannassa ei vielä ole.
        while ($hash == "") {

            // Muodostetaan lyhytosoite-ehdokas.
            $generated = generateHash(5);

            // Tarkistetaan, löytyykö lyhytosoitetta kannasta.
            $stmt->execute([$generated]);
            $result = $stmt->fetchColumn();
            if (!$result) {
                // Lyhytosoitetta ei ole kannassa, tallennetaan sen muuttujaan.
                $hash = $generated;
            }

        }

        // Haetaan käyttäjän ip-osoite.
        $ip = $_SERVER['REMOTE_ADDR'];

        // Alustetaan kantaan lisäys.
        $stmt2 = $yhteys->prepare("INSERT INTO osoite (tunniste, url, ip) VALUES (?, ?, ?)");

        // Suoritetaan muuttujien arvoilla.
        $stmt2->execute([$hash, $url, $ip]);

        // Osoite on lisätty kantaan, muodostetaan käyttäjälle tietosivu.
        $pagestatus = 1;
        $shorturl = $baseurl . $hash;
        
    } catch (PDOException $e) {
        // Virhe avaamisessa, tulostetaan virheilmoitus.
        $pagestatus = -2;
        $error = $e->getMessage();
    }    

}

// Löytyykö urlista hash-parametri.
if (isset($_GET["hash"])) {

    // Tallennetaan hash-arvo muuttujaan.
    $hash = $_GET["hash"];

    try {

        // Tietokantayhteyden avaus.
        $yhteys = new PDO($dsn, $user, $pwd, $options);

        // Tunnisteen haku tietokannasta.
        // Kyselyn valmistelu.
        $kysely = "SELECT url FROM osoite WHERE tunniste = ?";
        $lause = $yhteys->prepare($kysely);
        // Sidotaan $hash -tunniste kyselyn parametriin.
        $lause->bindValue(1, $hash);
        // Ja suoritetaan haku.
        $lause->execute();
        // Hakutuloksen rivi tallennetaan $tulos -muuttujaan.
        $tulos = $lause->fetch();

        // Tarkistetaan kyselyn tulos.

        // Löytyykö kannasta riviä hash-tunnisteella.
        if ($tulos) {

            // Rivi löytyi, haetaan osoite.
            $url = $tulos['url'];

            // Edelleenohjataan tietokannasta löytyvään osoitteeseen.
            header("Location: " . $url);
            exit;

        } else {

            // Kannassa ei ole hash-muuttujaa vastaavaa tunnistetta,
            // tulostetaan virheilmoitus.
            $pagestatus = -1;

        }

    } catch (PDOException $e) {
        // Virhe avaamisessa, tulostetaan virheilmoitus.
        $pagestatus = -2;
        $error = $e->getMessage();
    }

}

?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='styles.css' rel='stylesheet'>
    <title>Lyhentäjä</title>
</head>
<body>
    <div class='page'>

        <header>
            <h1>Lyhentäjä</h1>
            <div>ällistyttävä osoitelyhentäjä</div>
        </header>

        <main>
            <?php 
                if($pagestatus == 0) { 
            ?>
                <div class='form'>
                    <p>Tällä palvelulla voit lyhentää pitkän osoitteen lyhyeksi. Syötä alla olevaan kenttään pitkä osoite ja paina nappia, saat käyttöösi lyhytosoitteen, jota voit jakaa eteenpäin.</p>
                    <form action='' method='POST'>
                        <label for='url'>Syötä lyhennettävä osoite</label>
                        <div class='url'>
                            <input type='text' name='url' placeholder='tosi pitkä osoite'>
                            <input type='submit' name='shorten' value='lyhennä'>
                        </div>
                    </form>
                </div>
            <?php 
                }

                if($pagestatus == -1) { 
            ?>
                <div class='error'>
                    <h2>HUPSISTA!</h2>
                    <p>Näyttää siltä, että lyhytosoitetta ei löytynyt. Ole hyvä ja tarkista antamasi osoite.</p>
                    <p>Voit tehdä <a href="<?=$baseurl?>">tällä palvelulla</a> oman lyhytosoitteen.</p>
                </div>
            <?php 
                }

                if($pagestatus == -2) { 
            ?>
                <div class='error'>
                    <h2>NYT KÄVI HASSUSTI!</h2>
                    <p>Nostamme käden ylös virheen merkiksi, palvelimellamme on pientä hässäkkää. Ole hyvä ja kokeile myöhemmin uudelleen.</p>
                    <p>(virheilmoitus: <?=$error?>)</p>
                </div>
            <?php 
                }

                if ($pagestatus == 1) {
            ?>
                <div class='finish'>
                    <h2>JIPPII!</h2>
                    <p>Loit itsellesi uuden lyhytosoitteen, aivan mahtava juttu! Jatkossa voit käyttää seuraavaa osoitetta: <div class='code'><?=$shorturl?></div></p>
                    <p>Voit tehdä uuden lyhytosoitteen <a href="<?=$baseurl?>">täällä</a>.</p>
                </div>
            <?php
                }
            ?>
        </main>

        <footer>
            <hr>
            &copy; Kurpitsa Solutions
        </footer>

    </div>
</body>
</html>