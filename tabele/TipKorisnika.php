<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';

class TipKorisnika {
    public $id;
    public $naziv_tipa;
    public $prioritet;

    public static function tip_za_id($id) {
        $database = Database::getInstance();
        $tipovi = $database->select(
            'TipKorisnika',
            'SELECT * FROM tipovi_korisnika WHERE id LIKE :id',
            [
                ':id' => $id
            ]
        );
        foreach ($tipovi as $tip) {
            return $tip;
        }

        return null;
    }
}

?>