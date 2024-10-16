<?php
session_start();
if (!isset($_SESSION['korisnik_id'])) {
    header('Location: login.php');
    die();
}
?>