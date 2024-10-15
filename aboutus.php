<?php 
require_once("templates/header.php")
?>

<div class="container-fluid minheight100 py-4 px-3">
    <div class="row no-gutter">
        <div class="col">
            <!-- der PHP Code setzt hier die breite des Containers, wenn es ein Mobiles Gerät ist, ist dieser breiter -->
            <div class="card cbg ctext mx-auto" style="width: <?php if(!isMobile()) print('75%'); else print('95%');?>;">
                <div class="card-body">
                    <h1 class="card-title mb-2 text-center">Wer sind wir?</h1>
                    <p class="card-text text-center">
                        Wir sind 5 Schüler*innen der <a href="https://its-stuttgart.de/">it.schule</a> Stuttgart.<br>
                        Genau genommen sind wir die Projektgruppe Delta, welche an der Erstellung eines Hardware Webshops arbeitet. <br>
                        Dieser Webshop wird das Produkt unserer BfK-S Projektarbeit in der Klasse E2FS3BT sein.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include_once("templates/footer.php")
?>