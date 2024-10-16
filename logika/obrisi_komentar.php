<?php
session_start();
if (!isset($_SESSION['korisnik_id'])) {
    header('Location: ../login.php');
    die();
}
require_once __DIR__ . '/../tabele/Korisnik.php';
$korisnik = Korisnik::korisnik_za_id(($_SESSION['korisnik_id']));

if ($korisnik->tip_korisnika()->naziv_tipa !== 'administrator') {
   header("Location: ../prijavljen.php");
    die();
}

if (empty($_POST['komentar_id'])) {
    header("Location: ../prijavljen.php");
    die();
}

require_once __DIR__ . '/../tabele/Komentar.php';
Komentar::obrisi_komentar($_POST['komentar_id']);

echo '{"uspeh":"true"}';
//header('Location: ../prijavljen.php')
?>