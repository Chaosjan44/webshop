<!-- Cookie Banner -->
<div class="alert text-center cookiealert" role="alert">
    <b>Magst du Kekse?</b> &#x1F36A; Wir verwenden Cookies um dir ein großartiges Website-Erlebnis zu bieten.
    <a href="/cookies.php">
    <button type="button" class="btn btn-outline-primary btn-sm ms-3 me-3" data-bs-toggle="modal" data-bs-target="#cookieModal">
        Mehr erfahren
    </button>
    </a>
    <div class="vr"></div>
    <button type="button" class="btn btn-primary btn-sm acceptcookies ms-3">
        Ich stimme zu
    </button>
</div>
<script src="/js/cookies.js"></script>


<?php
// Variablen für Version und Datum
$vernum = "1.3.0";
$verdate ="25.05.2022";
// Wenn es sich um ein Desktop Handelt
if(!isMobile()):?>
    <footer class="container-fluid footer-footer sticky-bottom footer py-3 cbg">
        <div class="row">
            <div class="col ctext">
                Delta-Hardware
            </div>
            <div class="col text-center">
                <a href="/aboutus.php" class="ctext me-0">Über uns</a>
                <div class="vr mx-2"></div>
                <a class="ctext me-2" href="/impressum.php">Impressum</a>
            </div>
            <div class="col d-flex justify-content-end align-items-center text-end ctext">
                <input onchange="toggleStyle()" class="styleswitcher" type="checkbox" name="switch" id="style_switch" <?php if (check_style() == "dark") {print("checked");}?>>
                <label class="styleswitcherlabel" for="style_switch"></label>
                <div class="ps-3 text-end ctext">
                    Version <?=$vernum?> 
                    <div class="vr mx-1"></div>
                    <?=$verdate?>
                </div>
            </div>
        </div>
    </footer>
<!-- Wenn es sich um ein Mobiles Gerät handelt -->
<?php else:?>
    <footer class="container-fluid footer-footer sticky-bottom footer py-1 cbg">
        <div class="ctext">
            <div class="ctext col py-1 text-center">
                <a href="/aboutus.php" class="ctext">Über uns</a>
                <a class="ctext" href="/impressum.php">Impressum</a>
            </div>
            <div class="row">
                <div class="ctext col-4 py-1 pb-2 mb-2 d-flex align-items-center justify-content-start">
                    <input onchange="toggleStyle()" class="styleswitcher" type="checkbox" name="switch" id="style_switch" <?php if (check_style() == "dark") {print("checked");}?>>
                    <label class="styleswitcherlabel" for="style_switch"></label>
                </div>
                <div class="ctext col-8 py-1 pb-2 mb-2 d-flex align-items-center justify-content-end text-end">
                    Version <?=$vernum?>
                    <div class="vr mx-2"></div>
                    <?=$verdate?>
                </div>
            </div>
        </div>
    </footer>
<?php endif;?>

</body>
</html>
<script>
    // Style aktualisieren
    setStyle();
</script>
<?php unset($_SESSION['userid'])?>