<?php 
require_once("templates/header.php"); 
?>

<div class="container-fluid minheight100 py-4 px-3">
    <div class="row no-gutter">
        <div class="col">
            <!-- Das If-statement verändert die Breite, sobald es sich um ein Mobilgerät mit kleinerem Bildschirm handelt -->
            <div class="card cbg ctext mx-auto" style="width: <?php if(!isMobile()) print('75%'); else print('95%');?>;">
                <div class="card-body">
                <h4 class="fw-bold ctext">Wir verwenden nur notwendige Cookies um folgende Funktion bereitzustellen:</h4>
                    <br>
                    <p class="fs-5 ctext cookie-p-text">- Speichern der PHP-Session</p>
                    <p class="fs-5 ctext cookie-p-text">- Angemeldet bleiben</p>
                    <p class="fs-5 ctext cookie-p-text">- Speichern der Style-Einstellung</p>
                    <p class="fs-5 ctext cookie-p-text mb-1">- Speichern der Cookie-Einstellung</p>
                    <br>
                    <p class="fw-light fs-6 cookie-p-text ctext mb-5">Ihre Cookie-Einstellung wird gespeichert.</p>
                    <a href="index.php"><button type="button" class="btn btn-primary">Zurück zur Startseite</button></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include_once("templates/footer.php")
?>