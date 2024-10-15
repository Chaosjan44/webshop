<?php
// fügt die php-Funktionen hinzu
require_once("php/functions.php");
// fügt den header hinzu und liest die Benutzer-Infos hinzu
$user = require_once("templates/header.php");
//Überprüfe, dass der User eingeloggt ist und bindet evtl. die login.php ein
if (!isset($user['id'])) {
    require_once("login.php");
    exit;
}
// Überprüft ob eine ID übergeben wurde
if (!isset($_GET['id'])) {
    echo("<script>location.href='internal.php'</script>");
}
// Liest die Produkte der Bestellung aus
$stmt = $pdo->prepare('SELECT *, (SELECT img From product_images WHERE product_images.product_id=products.id ORDER BY id LIMIT 1) AS image, products.quantity as maxquantity FROM products, product_list where product_list.product_id = products.id and products.id in (SELECT product_id FROM product_list where list_id = (select id from orders where kunden_id = ? and id = ?)) and product_list.list_id = (select id from orders where kunden_id = ? and id = ?)');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$stmt->bindValue(2, $_GET['id'], PDO::PARAM_INT);
$stmt->bindValue(3, $user['id'], PDO::PARAM_INT);
$stmt->bindValue(4, $_GET['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Database error', pdo_debugStrParams($stmt));
}
$total_products = $stmt->rowCount();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Überprüft ob Produkte zu vorhanden sind
if ($total_products > 0) {
    if(isset($_POST['action'])) {
        // Überprüft ob etwas geändert werden soll
        if($_POST['action'] == 'edit') {
            $stmt = $pdo->prepare('UPDATE orders SET rechnungsadresse = ?, lieferadresse = ? WHERE id = ? AND kunden_id = ? AND ordered = 1 AND NOT `sent` = 1');
            $stmt->bindValue(1, $_POST['rechnugsaddresse'], PDO::PARAM_INT);
            $stmt->bindValue(2, $_POST['lieferaddresse'], PDO::PARAM_INT);
            $stmt->bindValue(3, $_GET['id'], PDO::PARAM_INT);
            $stmt->bindValue(4, $user['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Database error', pdo_debugStrParams($stmt));
            }
            echo("<script>location.href='/internal.php'</script>");
            exit;
        }
        // Überprüft ob die Bestellung storniert werden soll
        if($_POST['action'] == 'del') {
            foreach ($products as $product) {
                $stmt = $pdo->prepare('UPDATE products SET products.quantity = products.quantity + ? WHERE id = ?');
                $stmt->bindValue(1, $product['quantity'], PDO::PARAM_INT);
                $stmt->bindValue(2, $product['product_id'], PDO::PARAM_INT);
                $result = $stmt->execute();
                if (!$result) {
                    error('Database error', pdo_debugStrParams($stmt));
                }
            }
            $stmt = $pdo->prepare('DELETE FROM product_list WHERE list_id = ?');
            $stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Database error', pdo_debugStrParams($stmt));
            }
            $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ? AND kunden_id = ? AND ordered = 1 AND NOT sent = 1');
            $stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Database error', pdo_debugStrParams($stmt));
            }
            echo("<script>location.href='/internal.php'</script>");
            exit;
        }
    }
} else {
    // Wenn keine Produkte zurück kommen ist das nicht die Bestellung des Benutzers oder die ID existiert nicht
    error('Wrong ID!');
    exit;
}
// Ruft die Bestelleigenschafte ab
$stmt = $pdo->prepare('SELECT * FROM orders where kunden_id = ? and id = ?');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$stmt->bindValue(2, $_GET['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Database error', pdo_debugStrParams($stmt));
}
$order = $stmt->fetchAll(PDO::FETCH_ASSOC);
$summprice = 0;
// Errechnet die Summe
foreach ($products as $product) {
    $summprice = $summprice + ($product['price'] * $product['quantity']);
}
// Ruft die Rechnungsadressen ab
$stmt = $pdo->prepare('SELECT * FROM citys, `address` where `address`.`citys_id` = citys.id AND `address`.`id` = ?');
$stmt->bindValue(1, $order[0]['rechnungsadresse'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Database error', pdo_debugStrParams($stmt));
}
$rechnungsadresse = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Ruft die Lieferadresse ab
$stmt = $pdo->prepare('SELECT * FROM citys, `address` where `address`.`citys_id` = citys.id AND `address`.`id` = ?');
$stmt->bindValue(1, $order[0]['lieferadresse'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Database error', pdo_debugStrParams($stmt));
}
$lieferadresse = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Ruft die Adressen ab
$stmt = $pdo->prepare('SELECT * FROM `citys`, `address` where address.citys_id = citys.id and user_id = ?');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Database error', pdo_debugStrParams($stmt));
}
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (!isMobile()): ?>
    <div class="container minheight100 py-3 px-3">
        <div class="row">
            <div class="py-3 px-3 cbg ctext rounded">
                <div class="row d-flex justify-content-between">
                    <div class="col-5">
                        <h1>Bestellung</h1>
                        <p><?=$total_products?> Produkt<?=($total_products>1 ? 'e':'')?></p>
                        <p>Bestelldatum: <?=$order[0]['ordered_date']?></p>
                        <!-- Zeigt das Versanddatum an wenn es bereits verschickt wurde -->
                        <?php if ($order[0]['sent']==1): ?>
                            <p>Versanddatum: <?=$order[0]['sent_date']?></p>
                        <?php endif ?>
                    </div>
                    <!-- Zeigt die Option der Stornierung und Bearbeitung an wenn es noch nicht verschickt wurde -->
                    <?php if ($order[0]['sent']!=1): ?>
                            <div class="col-5 d-flex justify-content-end">
                                <form action="?id=<?=$_GET['id']?>" method="post" class="">
                                <button class="btn btn-outline-danger me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas" aria-controls="offcanvas">Bestellung stornieren</button>
                                <div class="offcanvas offcanvas-end cbg" data-bs-scroll="true" tabindex="-1" id="offcanvas" aria-labelledby="offcanvasLabel">
                                    <div class="offcanvas-header">
                                        <h2 class="offcanvas-title ctext" id="offcanvasLabel">Wirklich Stornieren?</h2>
                                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                    </div>
                                    <div class="offcanvas-body">
                                        <div class="d-flex justify-content-center">
                                            <button class="btn btn-outline-success mx-2" type="submit" name="action" value="del">Ja</button>
                                            <button class="btn btn-outline-danger mx-2" type="button" data-bs-dismiss="offcanvas" aria-label="Close">Nein</button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="action" value="edit" class="btn btn-outline-success ms-2">Speichern</button>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-5 mx-1">
                                    <div class="input-group mb-3">
                                        <label class="text-dark input-group-text" for="inputRechnugsaddresse">Rechnungsadresse</label>
                                        <select class="form-select border-0 text-dark fw-bold" id="inputRechnugsaddresse" name="rechnugsaddresse">
                                            <?php foreach ($addresses as $address): ?>
                                                <?php if ($order[0]['rechnungsadresse'] == $address['id']): ?>
                                                    <option class="text-dark" value="<?=$address['id']?>" selected><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                                <?php else:?>
                                                    <option class="text-dark" value="<?=$address['id']?>" ><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-5 mx-1">
                                    <div class="input-group mb-3">
                                        <label class="text-dark input-group-text" for="inputLieferaddresse">Lieferadresse</label>
                                        <select class="form-select border-0 text-dark fw-bold" id="inputLieferaddresse" name="lieferaddresse">
                                            <?php foreach ($addresses as $address): ?>
                                                <?php if ($order[0]['lieferadresse'] == $address['id']): ?>
                                                    <option class="text-dark" value="<?=$address['id']?>" selected><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                                <?php else:?>
                                                    <option class="text-dark" value="<?=$address['id']?>" ><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <!-- Sonst zeigt es nur die Addresses an -->
                <?php else: ?>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <h2>Rechnungsadresse</h2>
                            <div class="card cbg2 mx-auto py-2 px-2">
                                <p class="mb-0"><?=$user['vorname'].' '.$user['nachname']?></br>
                                <?=$rechnungsadresse[0]['street']?> <?=$rechnungsadresse[0]['number']?></br>
                                <?=$rechnungsadresse[0]['PLZ']?> <?=$rechnungsadresse[0]['city']?></br>
                            </div>
                        </div>
                        <div class="col-6">
                            <h2>Lieferadresse</h2>
                            <div class="card cbg2 mx-auto py-2 px-2">
                                <p class="mb-0"><?=$user['vorname'].' '.$user['nachname']?></br>
                                <?=$lieferadresse[0]['street']?> <?=$lieferadresse[0]['number']?></br>
                                <?=$lieferadresse[0]['PLZ']?> <?=$lieferadresse[0]['city']?></br>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <div class="ctext rounded">
                                    <th scope="col" class="border-0">
                                        <div class="p-2 px-3 text-uppercase ctext">Produkt</div>
                                    </th>
                                    <th scope="col" class="border-0 text-center">
                                        <div class="p-2 px-3 text-uppercase ctext">Preis</div>
                                    </th>
                                    <th scope="col" class="border-0 text-center">
                                        <div class="p-2 px-3 text-uppercase ctext">Menge</div>
                                    </th>
                                </div>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Zeigt alle Produkte an -->
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="border-0">
                                        <div class="p-2">
                                            <?php if (empty($product['image'])) {
                                                print('<img src="images/image-not-found.png" width="150" class="img-fluid rounded shadow-sm" alt="' . $product['name'] . '">');
                                            } else {
                                                print('<img src="product_img/' . $product['image'] . '" width="150" class="img-fluid rounded shadow-sm" alt="' . $product['name'] . '">');
                                            }?>
                                            <div class="ms-3 d-inline-block align-middle">
                                                <h5 class="mb-0"> 
                                                    <a href="product.php?id=<?=$product['product_id']?>" class="ctext d-inline-block align-middle text-wrap"><?=$product['name']?></a>
                                                </h5>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="border-0 align-middle text-center ctext">
                                        <span><?=$product['price']?>&euro;</span>
                                    </td>
                                    <td class="border-0 align-middle text-center ctext">
                                        <span><?=$product['quantity']?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>         
                <strong>Summe: <?=$summprice?>&euro;</strong>
                <button class="ms-2 btn btn-outline-danger" type="button" onclick="window.location.href = '/internal.php';">Abbrechen</button>
            </div>
        </div>
    </div> 
<!-- Mobile-View nur leicht abgewandeltes HTML-->
<?php else: ?>
    <div class="container minheight100 py-3 px-3">
        <div class="row">
            <div class="py-3 px-3 cbg ctext rounded">
                <div class="row d-flex justify-content-between">
                    <div>
                        <h1>Bestellung</h1>
                        <p><?=$total_products?> Produkt<?=($total_products>1 ? 'e':'')?></p>
                        <p>Bestelldatum: <?=$order[0]['ordered_date']?></p>
                        <?php if ($order[0]['sent']==1): ?>
                            <p>Versanddatum: <?=$order[0]['sent_date']?></p>
                        <?php endif ?>
                    </div>
                    <?php if ($order[0]['sent']!=1): ?>
                        <form action="?id=<?=$_GET['id']?>" method="post" class="">                                
                            <div class="col-12 my-2">
                                <div class="input-group">
                                    <label class="text-dark input-group-text" for="inputRechnugsaddresse">Rechnungsadresse</label>
                                    <select class="form-select border-0 text-dark fw-bold" id="inputRechnugsaddresse" name="rechnugsaddresse">
                                        <?php foreach ($addresses as $address): ?>
                                            <?php if ($order[0]['rechnungsadresse'] == $address['id']): ?>
                                                <option class="text-dark" value="<?=$address['id']?>" selected><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                            <?php else:?>
                                                <option class="text-dark" value="<?=$address['id']?>" ><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 my-2">
                                <div class="input-group">
                                    <label class="text-dark input-group-text" for="inputLieferaddresse">Lieferadresse</label>
                                    <select class="form-select border-0 text-dark fw-bold" id="inputLieferaddresse" name="lieferaddresse">
                                        <?php foreach ($addresses as $address): ?>
                                            <?php if ($order[0]['lieferadresse'] == $address['id']): ?>
                                                <option class="text-dark" value="<?=$address['id']?>" selected><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                            <?php else:?>
                                                <option class="text-dark" value="<?=$address['id']?>" ><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button class="col-12 btn btn-outline-danger my-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas" aria-controls="offcanvas">Bestellung stornieren</button>
                            <div class="offcanvas offcanvas-end cbg" data-bs-scroll="true" tabindex="-1" id="offcanvas" aria-labelledby="offcanvasLabel">
                                <div class="offcanvas-header">
                                    <h2 class="offcanvas-title ctext" id="offcanvasLabel">Wirklich Stornieren?</h2>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-outline-success mx-2" type="submit" name="action" value="del">Ja</button>
                                        <button class="btn btn-outline-danger mx-2" type="button" data-bs-dismiss="offcanvas" aria-label="Close">Nein</button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="action" value="edit" class="col-12 btn btn-outline-success my-2">Speichern</button>
                        </form>
                    </div>
                <?php else: ?>
                    </div>
                    <div class="row mb-2 row-cols-1">
                        <div class="col-12">
                            <h2>Rechnungsadresse</h2>
                            <div class="card cbg2 mx-auto py-2 px-2">
                                <p class="mb-0"><?=$user['vorname'].' '.$user['nachname']?></br>
                                <?=$rechnungsadresse[0]['street']?> <?=$rechnungsadresse[0]['number']?></br>
                                <?=$rechnungsadresse[0]['PLZ']?> <?=$rechnungsadresse[0]['city']?></br>
                            </div>
                        </div>
                        <div class="col-12">
                            <h2>Lieferadresse</h2>
                            <div class="card cbg2 mx-auto py-2 px-2">
                                <p class="mb-0"><?=$user['vorname'].' '.$user['nachname']?></br>
                                <?=$lieferadresse[0]['street']?> <?=$lieferadresse[0]['number']?></br>
                                <?=$lieferadresse[0]['PLZ']?> <?=$lieferadresse[0]['city']?></br>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
                <div class="row row-cols-1">
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="card mx-auto cbg2">
                                <div class="card-body">
                                    <?php if (empty($product['image'])) {
                                        print('<img src="images/image-not-found.png" class="card-img-top rounded mb-3" alt="' . $product['name'] . '">');
                                    } else {
                                        print('<img src="product_img/' . $product['image'] . '" class="card-img-top rounded mb-3" alt="' . $product['name'] . '">');
                                    }?>
                                    <h4 class="card-title name"><?=$product['name']?></h4>
                                    <span class="card-text price">
                                        Preis: &euro;<?=$product['price']?><br>
                                        Menge: <?=$product['quantity']?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>  
                </div>
                <div class="d-flex justify-content-between my-2">     
                    <strong class="col">Summe: <?=$summprice?>&euro;</strong>
                    <button class="col ms-2 btn btn-outline-danger" type="button" onclick="window.location.href = '/internal.php';">Abbrechen</button>
                </div>
            </div>
        </div>
    </div> 
<?php endif; 
// Fügt den Footer ein
include_once("templates/footer.php")
?>