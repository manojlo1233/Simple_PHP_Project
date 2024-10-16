<?php
require_once __DIR__ . '/includes_provera/da_li_je_prijavljen.php';
require_once __DIR__ . '/tabele/Komentar.php';
$komentari = Komentar::svi_komentari();
$korisnik = Korisnik::korisnik_za_id($_SESSION['korisnik_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Document</title>
    <script src="jquery-3.7.1.min.js"></script>
    <script>
        function izmena_komentara(form) {
            $('#postavi_komentar>input[name="naslov"]').val(form.find('input[name="naslov"]').val());
            $('#postavi_komentar>textarea[name="komentar"]').val(form.find('input[name="sadrzaj"]').val());
            $('#postavi_komentar>input[name="komentar_id"]').val(form.find('input[name="komentar_id"]').val());
            $('#postavi_komentar').attr('action', form.attr('action'));
        }
        $(function() {
            $('#postavi_komentar').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                /*$.ajax({
                    url: form.attr('action'),
                    method: form.attr('method');
                    data: {
                        
                    },
                    dataType: 'json',
                    success: function(odgovor) {

                    },
                    error: function(odgovor) {

                    }
                })*/
                $.ajax({
                    url:  form.attr('action'),
                    method: form.attr('method'),
                    data: {
                        'naslov': $('[name="naslov"]').val(),
                        'komentar': $('[name="komentar"]').val(),
                        'komentar_id': $('[name="komentar_id"]').val(),
                        'korisnik_id': '<?= $_SESSION['korisnik_id']?>'
                    },
                    dataType: 'json',
                    success: function(komentar) {
                        if (komentar.novi === 'true') {
                            console.log(komentar);
                            let htmlData = '<div class="komentar">' + 
                                    '<h2>' + komentar.created_at;
                            if (komentar.tip_korisnika.naziv_tipa === 'administrator') {
                                htmlData += '<form method="post" action="logika/obrisi_komentar.php" class="obrisi_komentar">' +  
                                            '<input type="hidden" name="komentar_id" value="'+ komentar.id + '">' + 
                                            '<button type="submit">Obriši</button>' + 
                                            '</form>'
                            } 
                            htmlData += '<form method="post" action="logika/izmeni_komentar.php" class="izmeni_komentar">' + 
                                        '<input type="hidden" name="komentar_id" value="' + komentar.id + '">' + 
                                        '<input type="hidden" name="naslov" value="' + komentar.naslov + '">' + 
                                        '<input type="hidden" name="sadrzaj" value="' + komentar.sadrzaj + '">' + 
                                        '<button type="submit">Izmeni</button>' + 
                                    '</form>'+                                    
                                    '</h2>' + 
                                    '<h3> ' + komentar.korisnik.username + '</h3>' + 
                                    '<h1>' + komentar.naslov + '</h1>' + 
                                    '<p>' +komentar.sadrzaj + '</p>' + 
                            '<hr></div>'
                            if ($('.komentar:first-of-type').length > 0) {
                                $('.komentar:first-of-type').before(htmlData)
                            }
                            else {
                                $('hr:last-of-type').after(htmlData);
                            }

                            $('.komentar:first .izmeni_komentar').on('submit', function(e) {
                                e.preventDefault();
                                let form = $(this);
                                izmena_komentara(form);
                            })

                            $('.obrisi_komentar').on('submit', function(e) {
                                e.preventDefault();
                                let form = $(this);
                                $.ajax({
                                    url: form.attr('action'),
                                    method: form.attr('method'),
                                    data: {
                                        'komentar_id': form.find('[name="komentar_id"]').val()
                                    },
                                    dataType: 'json',
                                    success: function(odgovor) {
                                        form.parent().parent().remove();
                                    },
                                    error: function(odgovor) {

                                    }
                                })
                            })
                        }
                        else {
                            let kom_el = $('.izmeni_komentar input[value="' + komentar.id + '"]').parent().parent().parent();
                            kom_el.find('h1').html($('[name="naslov"]').val())
                            kom_el.find('p').html($('[name="komentar"]').val())
                            form.attr('action', 'logika/postavi_komentar.php')
                            let izmeni_form = $('.izmeni_komentar');
                            izmeni_form.find('input[name="naslov"]').val($('#postavi_komentar>input[name="naslov"]').val());
                            izmeni_form.find('input[name="sadrzaj"]').val($('#postavi_komentar>textarea[name="komentar"]').val());
                        }
                        form.find('input, textarea').val('');
                    },
                    error: function(odgovor) {
                        console.log(odgovor);
                    }
                })
            })
        

            $('.obrisi_komentar').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    method: form.attr('method'),
                    data: {
                        'komentar_id': form.find('[name="komentar_id"]').val()
                    },
                    dataType: 'json',
                    success: function(odgovor) {
                        form.parent().parent().remove();
                    },
                    error: function(odgovor) {

                    }
                })
            })

            $('.izmeni_komentar').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                izmena_komentara(form);
            })
        });
    </script>
</head>
<body>
    <a href="logika/logout.php">Odjavi se</a>
    <hr>
    <form action="logika/postavi_komentar.php" method="post" id="postavi_komentar">
        <input type="text" name="naslov" placeholder="Uneti naslov komentara"> <br>
        <textarea name="komentar" placeholder="Uneti sadrzaj komentara"></textarea> <br>
        <input type="hidden" name="komentar_id">
        <button type="submit">Posalji komentar</button>
    </form>
    <hr>
    <?php foreach($komentari as $komentar): ?>
        <div class="komentar">
            <h2>
                <?php echo date('d.m.Y. H:i', strtotime($komentar->created_at)) ?>
                <?php if ($korisnik->tip_korisnika()->naziv_tipa == 'administrator'): ?> 
                    <form method="post" action="logika/obrisi_komentar.php" class="obrisi_komentar">
                        <input type="hidden" name="komentar_id" value="<?php echo $komentar->id ?>">
                        <button type="submit">Obriši</button>
                    </form>
                <?php endif?>
                <?php if ($korisnik->id === $komentar->korisnik_id):?>
                    <form method="post" action="logika/izmeni_komentar.php" class="izmeni_komentar">
                        <input type="hidden" name="komentar_id" value="<?php echo $komentar->id ?>">
                        <input type="hidden" name="naslov" value="<?php echo $komentar->naslov ?>">
                        <input type="hidden" name="sadrzaj" value="<?php echo $komentar->sadrzaj ?>">
                        <button type="submit">Izmeni</button>
                    </form>
                <?php endif ?>
            </h2>
            <h3><?php echo $komentar->korisnik()->username ?></h3>
            <h1><?php echo $komentar->naslov ?></h1>
            <p><?php echo $komentar->sadrzaj ?></p>
            <hr>
        </div>
        
    <?php endforeach?>
</body>
</html>
