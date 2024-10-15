<?php
// Aufgrund des Unterordners muss hier erst wieder auf den DOCUMENT ROOT gewechselt werden
chdir ($_SERVER['DOCUMENT_ROOT']);
require_once("php/functions.php");
$user = require_once("templates/header.php");
// Leite User auf Login weiter wenn dieser nicht Angemeldet ist
if (!isset($user['id'])) {
    require_once("login.php");
    exit;
}
// Zeit die Error Seite wenn der User keine Berechtigungen hat
if ($user['showOrders'] != 1) {
    error('Unzureichende Berechtigungen!');
}
// Falls in der URL keine ID angegeben ist wird auf die internal.php umgeleitet
if (!isset($_GET['id']) or empty($_GET['id'])) {
    echo("<script>location.href='/internal.php'</script>");
}
// Ruft alle Produkte und Bilder dieser von der Datenbank ab, welche im Warenkorb mit entsprechender ID sind
$stmt = $pdo->prepare('SELECT *, (SELECT img From product_images WHERE product_images.product_id=products.id ORDER BY id LIMIT 1) AS image, products.quantity as maxquantity FROM products, product_list where product_list.product_id = products.id and product_list.list_id = ?');
$stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
$result = $stmt->execute();
// Zeige die Error Page mit der Meldung "Datenbank Fehler!"
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$total_products = $stmt->rowCount();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Überprüfe ob die POST Action "confirm" gesetzt ist
if(isset($_POST['confirm'])) {
    // Überprüfe ob die POST Action "confirm" auf "yes" gesetzt ist
    if($_POST['confirm'] == 'yes') {
		// Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['markOrders'] != 1) {
			error('Unzureichende Berechtigungen!');
		}
        // Updatet die Bestellung mit Entsprechender ID, der Parameter "sent" wird auf 1 gesetzt und das sent_date wird auf das Aktuelle Datum gesetzt
        $stmt = $pdo->prepare('UPDATE orders SET sent = 1, sent_date = now() WHERE id = ? and ordered = 1');
        $stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
        $result = $stmt->execute();
        // Zeige die Error Page mit der Meldung "Datenbank Fehler!"
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        // Weiterleiten auf die Internal
		echo("<script>location.href='/internal.php'</script>");
    }
}
// Ruft die Order mit der ID aus der URL ab, hierbei werden auch die Daten des Users gezogen
$stmt = $pdo->prepare('SELECT * FROM users, orders where users.id = orders.kunden_id AND orders.id = ?');
$stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
$result = $stmt->execute();
// Zeige die Error Page mit der Meldung "Datenbank Fehler!"
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$customer = $stmt->fetchAll(PDO::FETCH_ASSOC);
// SQL Abfrage für die Adresse bei der Bestellung ausgewählten Rechnungsadresse
$stmt = $pdo->prepare('SELECT * FROM citys, `address` where `address`.`citys_id` = citys.id AND `address`.`id` = ?');
$stmt->bindValue(1, $customer[0]['rechnungsadresse'], PDO::PARAM_INT);
$result = $stmt->execute();
// Zeige die Error Page mit der Meldung "Datenbank Fehler!"
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$rechnungsadresse = $stmt->fetchAll(PDO::FETCH_ASSOC);
// SQL Abfrage für die Adresse bei der Bestellung ausgewählten Lieferadresse
$stmt = $pdo->prepare('SELECT * FROM citys, `address` where `address`.`citys_id` = citys.id AND `address`.`id` = ?');
$stmt->bindValue(1, $customer[0]['lieferadresse'], PDO::PARAM_INT);
$result = $stmt->execute();
// Zeige die Error Page mit der Meldung "Datenbank Fehler!"
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$lieferadresse = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Summe wird berechnet
$summprice = 0;
foreach ($products as $product) {
    $summprice = $summprice + ($product['price'] * $product['quantity']);
}
?>
<!-- Desktop View -->
<?php if (!isMobile()): ?>
    <div class="container minheight100 products content-wrapper py-3 px-3">
        <div class="row">
            <div class="py-3 px-3 cbg ctext rounded">
                <div class="row mb-2">
                    <div class="d-flex justify-content-between">
                        <div class="col-8">
                            <h1>Bestellung bearbeiten</h1>
                            <p>Bitte folgende<?=($total_products>1 ? ' '.$total_products:'s')?> Produkt<?=($total_products>1 ? 'e':'')?> für den Kunden einpacken und das Packet mit folgendem Addressaufkleber versehen:</p>
                        </div>
                        <div class="col-4">
                            <!-- Wird nur angezeigt wenn der user die Berechtigungen zum erledigen von Bestellungen hat -->
                            <?php if ($user['markOrders'] == 1) { ?>
                                <form action="?id=<?=$_GET['id']?>" method="post" class="d-flex justify-content-end">
                                    <button type="submit" name="confirm" value="yes" class="btn btn-outline-success me-2">Erledigt</button>
                                    <button class="ms-2 btn btn-outline-danger" type="button" onclick="window.location.href = '/internal.php';">Abbrechen</button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <h2>Rechnungsadresse</h2>
                        <div class="card cbg2 mx-auto py-2 px-2">
                            <p class="mb-0"><?=$customer[0]['vorname'].' '.$customer[0]['nachname']?></br>
                            <?=$rechnungsadresse[0]['street']?> <?=$rechnungsadresse[0]['number']?></br>
                            <?=$rechnungsadresse[0]['PLZ']?> <?=$rechnungsadresse[0]['city']?></br>
                        </div>
                    </div>
                    <div class="col-6">
                        <h2>Lieferadresse</h2>
                        <div class="card cbg2 mx-auto py-2 px-2">
                            <p class="mb-0"><?=$customer[0]['vorname'].' '.$customer[0]['nachname']?></br>
                            <?=$lieferadresse[0]['street']?> <?=$lieferadresse[0]['number']?></br>
                            <?=$lieferadresse[0]['PLZ']?> <?=$lieferadresse[0]['city']?></br>
                        </div>
                    </div>
                </div>
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
                            <!-- Für Produkte der Bestellung -->
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="border-0">
                                        <div class="p-2">
                                            <?php if (empty($product['image'])) {
                                                print('<img src="/images/image-not-found.png" width="150" class="img-fluid rounded shadow-sm" alt="' . $product['name'] . '">');
                                            } else {
                                                print('<img src="/product_img/' . $product['image'] . '" width="150" class="img-fluid rounded shadow-sm" alt="' . $product['name'] . '">');
                                            }?>
                                            <div class="ms-3 d-inline-block align-middle">
                                                <h5 class="mb-0"> 
                                                    <a href="/product.php?id=<?=$product['product_id']?>" class="ctext d-inline-block align-middle"><?=$product['name']?></a>
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
            </div>
        </div>
    </div> 
<!-- Mobile View -->
<?php else: ?>
    <div class="container minheight100 products content-wrapper py-2 px-2">
        <div class="card mx-auto my-2 cbg">
            <div class="card-body">
                <h2 class="card-title name">Bestellung bearbeiten</h2>
                <p class="card-text">Bitte folgende<?=($total_products>1 ? ' '.$total_products:'s')?> Produkt<?=($total_products>1 ? 'e':'')?> für den Kunden einpacken und das Packet mit folgendem Addressaufkleber versehen:</p>
            </div>
        </div>
        <div class="card mx-auto my-2 cbg">
            <div class="card-body">
                <h2>Rechnungsadresse</h2>
                <div class="card-text cbg2 py-2 px-2">
                    <p class="mb-0"><?=$customer[0]['vorname'].' '.$customer[0]['nachname']?></br>
                    <?=$rechnungsadresse[0]['street']?> <?=$rechnungsadresse[0]['number']?></br>
                    <?=$rechnungsadresse[0]['PLZ']?> <?=$rechnungsadresse[0]['city']?></br>
                </div>
            </div>
            <div class="card-body">
                <h2>Lieferadresse</h2>
                <div class="card-text cbg2 py-2 px-2">
                    <p class="mb-0"><?=$customer[0]['vorname'].' '.$customer[0]['nachname']?></br>
                    <?=$lieferadresse[0]['street']?> <?=$lieferadresse[0]['number']?></br>
                    <?=$lieferadresse[0]['PLZ']?> <?=$lieferadresse[0]['city']?></br>
                </div>
            </div>
        </div>
        <!-- Wird nur angezeigt wenn der User Berechtigungen hat die Bestellungen als Erledigt zu Markieren -->
        <?php if ($user['markOrders'] == 1) { ?>
            <div class="card mx-auto my-2 cbg">
                <div class="card-body">
                    <form action="?id=<?=$_GET['id']?>" method="post" class="d-flex justify-content-between">
                        <button type="submit" name="confirm" value="yes" class="py-2 btn btn-outline-success">Erledigt</button>
                        <button class="py-2 btn btn-outline-danger" type="button" onclick="window.location.href = '/internal.php';">Abbrechen</button>
                    </form>
                </div>
            </div>
        <?php } ?>
        <!-- Für jedes Produkt der Bestellung -->
        <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card mx-auto my-2 cbg">
                    <?php if (empty($product['image'])) {
                        print('<img src="/images/image-not-found.png" class="card-img-top img-fluid" alt="' . $product['name'] . '">');
                    } else {
                        print('<img src="/product_img/' . $product['image'] . '" class="card-img-top img-fluid" alt="' . $product['name'] . '">');
                    }?>
                        <div class="card-body">
                        <h4 class="card-title name"><?=$product['name']?></h4>
                        <span class="card-text price">
                            Preis: &euro;<?=$product['price']?><br>
                            Menge: <?=$product['quantity']?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="card mx-auto my-2 cbg">
            <div class="card-body">
                <h2 class="card-title name">Summe:</h2>
                <strong class="card-text"><?=$summprice?>&euro;</strong>
            </div>
        </div>
    </div>
<?php endif;?>

<?php
include_once("templates/footer.php")
?>