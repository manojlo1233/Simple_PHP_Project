<?php 
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/Korisnik.php';
class Komentar 
{
    public $id;
    public $naslov;
    public $sadrzaj;
    public $created_at;
    public $korisnik_id;

    public function korisnik() {
        return Korisnik::korisnik_za_id($this->korisnik_id);
    }

    public static function unesi_komentar($naslov, $sadrzaj, $korisnik_id) {
        $database = Database::getInstance();

        $database->insert(
            'Komentar',
            'INSERT INTO komentari (naslov, sadrzaj, korisnik_id) VALUES (:naslov, :sadrzaj, :korisnik_id)',
            [
                ":naslov" => $naslov,
                ":sadrzaj" => $sadrzaj,
                ":korisnik_id" => $korisnik_id,
            ]
        );

        $id = $database->lastInsertId();
        return $id;
    }

    public static function svi_komentari() {
        $database = Database::getInstance();
        $komentari = $database->select(
            'Komentar',
            'SELECT * from komentari ORDER BY created_at desc');

        return $komentari;
    }

    public static function obrisi_komentar($id) {
        $database = Database::getInstance();
        $database->delete(
            'DELETE FROM komentari WHERE id = :id',
            [
                ":id" => $id
            ]
        );
    }

    public static function komentar_za_id($id) {
        $database = Database::getInstance();
        $komentari = $database->select(
            'Komentar',
            'SELECT * FROM komentari WHERE id LIKE :id',
            [
                ':id' => $id
            ]
        );

        foreach ($komentari as $komentar) {
            return $komentar;
        }

        return null;
    }

    public static function izmeni_komentar($naslov, $komentar, $korisnik_id, $komentar_id) {
        $database = Database::getInstance();
        $database->update(
            'Komentar',
            'UPDATE komentari SET naslov=:naslov, sadrzaj=:komentar WHERE id=:id AND korisnik_id=:korisnik_id',
            [
                ':naslov' => $naslov,
                ':komentar' => $komentar,
                ':id' => $komentar_id,
                ':korisnik_id' => $korisnik_id
            ]
        );
        return $komentar_id;
    }
}
?>