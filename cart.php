<?php
require_once("php/functions.php");
$user = require_once("templates/header.php");
// Leite User auf Login weiter wenn dieser nicht Angemeldet ist
if (!isset($user['id'])) {
    require_once("login.php");
    exit;
}
// Wenn "action" gesetzt ist
if(isset($_POST['action'])) {
    // Wenn die action "add" ist
    if($_POST['action'] == 'add') {
        // Wenn alle benötigten Felder gesetzt und nicht leer sind
        if(isset($_POST['productid']) and isset($_POST['quantity']) and !empty($_POST['productid']) and !empty($_POST['quantity'])) {
            // Abfrage der Produkte des aktuellen Warenkorbs, hierbei darf der Warenkorb weder Bestellt, noch von uns bearbeitet sein darf
            $stmt = $pdo->prepare('SELECT *, products.quantity as maxquantity FROM products, product_list where product_list.product_id = products.id and product_id = ? and products.id in (SELECT product_id FROM product_list where list_id = (select id from orders where kunden_id = ? and ordered = 0 and sent = 0)) and product_list.list_id = (select id from orders where kunden_id = ? and ordered = 0 and sent = 0)');
            $stmt->bindValue(1, $_POST['productid'], PDO::PARAM_INT);
            $stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(3, $user['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            $product = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Wenn das Produkt bereits im Warenkorb ist
            if (isset($product[0])) {
                // Sicherstellen das die quantity mindestens 1 und nicht höher wie der Lagerbestand ist
                if ($_POST['quantity'] + $product[0]['quantity'] > $product[0]['maxquantity']) {
                    $quantity = $product[0]['maxquantity'];
                } else {
                    $quantity = $_POST['quantity'] + $product[0]['quantity'];
                }
                if ($quantity < 1) {
                    $quantity = 1;
                }
                // Update der quantity der eines Produktes
                $stmt = $pdo->prepare('UPDATE product_list SET quantity = ? WHERE id = ?');
                $stmt->bindValue(1, $quantity, PDO::PARAM_INT);
                $stmt->bindValue(2, $product[0]['id'], PDO::PARAM_INT);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }                
                echo("<script>location.href='cart.php'</script>");
                exit;
            // Wenn das Produkt zum Warenkorb hinzugefügt wird und noch nicht im Warenkorb war
            } else {
                // Datenbank abfrage des Produktes
                $stmt = $pdo->prepare('SELECT * FROM products where products.id = ?');
                $stmt->bindValue(1, $_POST['productid'], PDO::PARAM_INT);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }
                $product = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Sicherstellen das die quantity mindestens 1 und nicht höher wie der Lagerbestand ist
                if ($_POST['quantity'] > $product[0]['quantity']) {
                    $quantity = $product[0]['quantity'];
                } else {
                    $quantity = $_POST['quantity'];
                }
                if ($quantity < 1) {
                    $quantity = 1;
                }
                // Produkt in den Warenkorb hinzufügen
                $stmt = $pdo->prepare('INSERT INTO product_list (list_id, product_id, quantity) VALUES ((select id from orders where kunden_id = ? and ordered = 0 and sent = 0), ?, ?)');
                $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
                $stmt->bindValue(2, $_POST['productid']);
                $stmt->bindValue(3, $quantity, PDO::PARAM_INT);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }
                echo("<script>location.href='cart.php'</script>");
                exit;
            }
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
    // Wenn die action "del" ist
    if($_POST['action'] == 'del') {
        // Wenn die listid gesetzt und nicht leer ist
        if(isset($_POST['listid']) and !empty($_POST['listid'])) {
            // Löschen des Items aus dem Warenkorb
            $stmt = $pdo->prepare('DELETE FROM product_list WHERE id = ? and list_id = (select id from orders where kunden_id = ? and ordered = 0 and sent = 0)');
            $stmt->bindValue(1, $_POST['listid'], PDO::PARAM_INT);
            $stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }                    
            echo("<script>location.href='cart.php'</script>");
            exit;
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
    // Wenn die action "mod" ist
    if($_POST['action'] == 'mod') {
        // Wenn die listid gesetzt und nicht leer ist
        if(isset($_POST['listid']) and !empty($_POST['listid'])) {
            // Produkt details aus der Datenbank abfragen
            $stmt = $pdo->prepare('SELECT *, products.quantity as maxquantity from products, product_list where products.id = product_list.product_id and product_list.id = ?');
            $stmt->bindValue(1, $_POST['listid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }            
            $product = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Sicherstellen das die quantity mindestens 1 und nicht höher wie der Lagerbestand ist
            if ($_POST['quantity'] > $product[0]['maxquantity']) {
                $quantity = $product[0]['maxquantity'];
            } else {
                $quantity = $_POST['quantity'];
            }
            if ($quantity < 1) {
                $quantity = 1;
            }
            // Update der Produktliste (also des Warenkorbs)
            $stmt = $pdo->prepare('UPDATE product_list SET quantity = ? WHERE id = ? and list_id = (select id from orders where kunden_id = ? and ordered = 0 and sent = 0)');
            $stmt->bindValue(1, $quantity, PDO::PARAM_INT);
            $stmt->bindValue(2, $_POST['listid'], PDO::PARAM_INT);
            $stmt->bindValue(3, $user['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }            
            echo("<script>location.href='cart.php'</script>");
            exit;
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
}

// Abfrage aller Produkte und Sortierung nach date added
$stmt = $pdo->prepare('SELECT *, (SELECT img From product_images WHERE product_images.product_id=products.id ORDER BY id LIMIT 1) AS image, products.quantity as maxquantity FROM products_types, products, product_list where product_list.product_id = products.id and products.product_type_id = products_types.id and products.id in (SELECT product_id FROM product_list where list_id = (select id from orders where kunden_id = ? and ordered = 0 and sent = 0)) and product_list.list_id = (select id from orders where kunden_id = ? and ordered = 0 and sent = 0)');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$total_products = $stmt->rowCount();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$summprice = 0;
// Summe
foreach ($products as $product) {
    $summprice = $summprice + ($product['price'] * $product['quantity']);
}
?>
<!-- Desktop Ansicht -->
<?php if (!isMobile()): ?>
    <div class="container minheight100 products content-wrapper py-3 px-3">
        <div class="row">
            <div class="py-3 px-3 cbg ctext rounded">
                <h1>Warenkorb</h1>
                <p><?php print($total_products); ?> Produkt<?php if ($total_products > 1) { print('e'); } ?> im Warenkorb</p>
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
                                    <th scope="col" class="border-0">
                                        <div class="p-2 px-3 text-uppercase"></div>
                                    </th>
                                </div>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="border-0">
                                        <div class="p-2 row align-items-center" style="max-width: 40rem">
                                            <div class="col-md-3">
                                                <?php if (empty($product['image'])) {
                                                    print('<img src="images/image-not-found.png" width="150" class="img-fluid rounded shadow-sm" alt="' . $product['name'] . '">');
                                                } else {
                                                    print('<img src="product_img/' . $product['image'] . '" width="150" class="img-fluid rounded shadow-sm" alt="' . $product['name'] . '">');
                                                }?>
                                            </div>
                                            <div class="col-md-9 text-wrap">
                                                <a href="product.php?id=<?=$product['product_id']?>" class="ctext d-inline-block align-middle text-wrap"><?=$product['name']?></a>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="border-0 align-middle text-center ctext">
                                        <span><?=$product['price']?>&euro;</span>
                                    </td>
                                    <td class="border-0 align-middle text-center ctext">
                                        <span><?=$product['quantity']?></span>
                                    </td>
                                    <td class="border-0 align-middle actions">
                                        <form action="cart.php" method="post" class="row me-2">
                                            <div class="col px-3 input-group">
                                                <span class="input-group-text">Menge:</span>
                                                <input class="form-control" type="number" value="<?=$product['id']?>" name="listid" style="display: none;" required>
                                                <input class="form-control" type="number" value="<?=$product['quantity']?>" min="1" max="<?=$product['maxquantity']?>" class="form-control form-control-sm" name="quantity" required>
                                                <button type="submit" name="action" value="mod" class="btn btn-outline-primary">Speichern</button>
                                            </div>
                                            <div class="col-3 px-3">
                                                <!-- <input type="number" value="<?=$product['id']?>" name="listid" style="display: none;" required>
                                                <button type="submit" name="action" value="del" class="btn btn-outline-primary">Löschen</button> -->
                                                <input type="number" value="<?=$product['id']?>" name="listid" style="display: none;" required>
                                                <button class="btn btn-outline-danger" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas<?=$product['id']?>" aria-controls="offcanvas<?=$product['id']?>">Löschen</button>
                                                <div class="offcanvas offcanvas-end cbg" data-bs-scroll="true" tabindex="-1" id="offcanvas<?=$product['id']?>" aria-labelledby="offcanvas<?=$product['id']?>Label">
                                                    <div class="offcanvas-header">
                                                        <h2 class="offcanvas-title ctext" id="offcanvas<?=$product['id']?>Label">Wirklich Löschen?</h2>
                                                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                                    </div>
                                                    <div class="offcanvas-body">
                                                        <button class="btn btn-outline-success mx-2" type="submit" name="action" value="del">Ja</button>
                                                        <button class="btn btn-outline-danger mx-2" type="button" data-bs-dismiss="offcanvas" aria-label="Close">Nein</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>         
                <strong>Summe: <?=$summprice?>&euro;</strong>
                <?php
                if ($total_products > 0) {
                    print('<a href="placeorder.php"><button class="btn btn-outline-primary mx-2 my-2" type="button">Bestellen</button></a>');
                } ?>
            </div>
        </div>
    </div> 
<!-- Mobile Ansicht -->
<?php else: ?>
    <div class="container minheight100 products content-wrapper py-3 px-3">
        <div class="row row-cols-1 row-cols-md-1 g-3">
            <div class="col">
                <div class="card mx-auto cbg">
                    <div class="card-body">
                        <h2 class="card-title name">Warenkorb</h2>
                        <p class="card-text"><?php print($total_products); ?> Produkt<?php if ($total_products > 1) { print('e'); } ?> im Warenkorb</p>
                    </div>
                </div>
            </div>
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card mx-auto cbg">
                        <div class="card-body">
                            <?php if (empty($product['image'])) {
                                print('<img src="images/image-not-found.png" class="card-img-top rounded mb-3" alt="' . $product['name'] . '">');
                            } else {
                                print('<img src="product_img/' . $product['image'] . '" class="card-img-top rounded mb-3" alt="' . $product['name'] . '">');
                            }?>
                            <h4 class="card-title name text-wrap"><?=$product['name']?></h4>
                            <span class="card-text price">
                                Preis: &euro;<?=$product['price']?><br>
                                Menge: <?=$product['quantity']?>
                            </span>
                            <form action="cart.php" method="post" class="pt-2">
                                <div class="mx-auto pb-3 input-group">
                                    <span class="input-group-text">Menge:</span>
                                    <input class="form-control" type="number" value="<?=$product['id']?>" name="listid" style="display: none;" required>
                                    <input class="form-control" type="number" value="<?=$product['quantity']?>" min="1" max="<?=$product['maxquantity']?>" class="form-control form-control-sm" name="quantity" required>
                                    <button type="submit" name="action" value="mod" class="btn btn-outline-primary">Speichern</button>
                                </div>
                                <div class="row mx-auto">
                                    <input type="number" value="<?=$product['id']?>" name="listid" style="display: none;" required>
                                    <button type="submit" name="action" value="del" class="btn btn-outline-primary">Löschen</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col">
                <div class="card mx-auto cbg">
                    <div class="card-body">
                        <h2 class="card-title name">Summe:</h2>
                        <strong class="card-text"><?=$summprice?>&euro;</strong>
                        <?php
                        if ($total_products > 0) {
                            print('<a href="placeorder.php"><button class="btn btn-outline-primary mx-2 my-2" type="button">Bestellen</button></a>');
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php
include_once("templates/footer.php")
?>