<?php
session_start();
if (!isset($_SESSION['korisnik_id'])) {
    header('Location: ../login.php');
    die();
}

if (empty($_POST['naslov']) || empty($_POST['komentar']) || empty($_POST['komentar_id'])) {
    header('Location: ../prijavljen.php');
    die();
}

if ($_POST['korisnik_id'] === $_SESSION['korisnik_id']) {
    header('Location: ../prijavljen.php');
    die();
}

require_once __DIR__ . '/../tabele/Komentar.php';

$id = Komentar::izmeni_komentar($_POST['naslov'], $_POST['komentar'], $_SESSION['korisnik_id'], $_POST['komentar_id']);

if ($id > 0) {
    $komentar = Komentar::komentar_za_id($id);
    $komentar->korisnik = $komentar->korisnik();
    $komentar->created_at = date('d.m.Y. H:i', strtotime($komentar->created_at));
    $komentar->novi = 'false';
    echo json_encode($komentar);
}
else {
    header('Location: ../prijavljen.php?error=komentar');
    die();
}
?>