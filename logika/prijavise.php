<?php
if (!isset($_POST['username'])) {
    header('Location: ../registracija.php');
    die();
}

$username = $_POST['username'];
$password = $_POST['password'];

require_once __DIR__ . '/../tabele/Korisnik.php';

$korisnik = Korisnik::proveri($username, $password);

if ($korisnik == null) {
    header('Location: ../login.php?login=0');
    die();
}

session_start();
$_SESSION['korisnik_id'] = $korisnik->id;

header('Location: ../prijavljen.php');
?>