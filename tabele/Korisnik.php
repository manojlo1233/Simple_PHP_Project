<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/TipKorisnika.php';

class Korisnik {
    public $id;
    public $username;
    public $password;
    public $email;
    public $tip_korisnika_id;
    public $ceated_at;

    public function tip_korisnika() {
        return TipKorisnika::tip_za_id($this->tip_korisnika_id);
    }

    public static function registracija($username, $password, $email, $tip_korisnika_id) {
        $password = hash('sha512', $password);
        $database = Database::getInstance();

        $database->insert(
            'Korisnik',
            'INSERT INTO korisnici (username, password, email, tip_korisnika_id) VALUES (:username, :password, :email, :tip_korisnika_id)',
            [
                ':username' => $username,
                ':password' => $password,
                ':email' => $email,
                ':tip_korisnika_id' => $tip_korisnika_id
            ]
        );
        $id = $database->lastInsertId();
        return $id;
    }

    public static function proveri($username, $password) {
        $password = hash('sha512', $password);
        $database = Database::getInstance();

        $korisnici = $database->select(
            'Korisnik',
            'SELECT * FROM korisnici WHERE username LIKE :username AND password LIKE :password',
            [
                ':username' => $username,
                ':password' => $password
            ]
        );

        foreach ($korisnici as $korisnik) {
            return $korisnik;
        }

        return null;
    }

    public static function korisnik_za_id($id) {
        $database = Database::getInstance();
        $korisnici = $database->select(
            'Korisnik',
            'SELECT * FROM korisnici WHERE id LIKE :id',
            [
                ':id' => $id
            ]
        );

        foreach ($korisnici as $korisnik) {
            return $korisnik;
        }

        return null;
    }

}

?>