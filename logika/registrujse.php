<?php
if (!isset($_POST['username'])) {
    header('Location: ../registracija.php');
    die();
}

$username = $_POST['username'];
$password = $_POST['password'];
$password_repeat = $_POST['password_repeat'];
$email = $_POST['email'];

if ($password !== $password_repeat) {
    header('Location: ../registracija.php?pass=0');
    die();
}

require_once __DIR__ . '/../tabele/Korisnik.php';
$id = Korisnik::registracija($username, $password, $email, 4);

if ($id > 0) {
    header('Location: ../login.php');
    die();
}
else {
    header('Location: ../registracija.php?error=0');
    die();
}
?>