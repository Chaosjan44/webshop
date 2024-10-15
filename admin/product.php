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
if ($user['showProduct'] != 1) {
    error('Unzureichende Berechtigungen!');
}
// Überprüfe ob die POST Action "action" gesetzt ist
if(isset($_POST['action'])) {
    // Wenn die action "mod" ist
    if($_POST['action'] == 'mod') {
        // Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['modifyProduct'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Abfrage des ausgewählten Produktes
        $stmt = $pdo->prepare('SELECT * FROM products where products.id = ?');
        $stmt->bindValue(1, $_POST['productid'], PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        $product = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Abfrage aller ROOT Kategorien
        $stmt = $pdo->prepare('SELECT * FROM products_types where not products_types.parent_id = 0');
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Bilder zum Selektierten Produkt abfragen
        $stmt = $pdo->prepare('SELECT * FROM product_images where product_id = ?');
        $stmt->bindValue(1, $_POST['productid'], PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Löschen aller Ausgewählten Bilder
        for ($x = 0; $x < count($imgs); $x++) {
            $var = 'delImage-'.$x;
            if (isset($_POST[$var])) {
                #del
                $stmt = $pdo->prepare('DELETE FROM product_images where id = ? and product_id = ?');
                $stmt->bindValue(1, $_POST[$var], PDO::PARAM_INT);
                $stmt->bindValue(2, $_POST['productid'], PDO::PARAM_INT);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }                
            }
        }
        // Wenn Dateien hochgeladen werden
        if (!empty($_FILES["file"]["name"][0])){
            $allowTypes = array('jpg','png','jpeg','gif');
            $fileCount = count($_FILES['file']['name']);
            // für jedes Bild
            for($i = 0; $i < $fileCount; $i++){
                // Bild wird zum Abspeichern mit einer Einmaligen ID + Uhrsprungsame versehen
                $fileName = uniqid('image_') . '_' . basename($_FILES["file"]["name"][$i]);
                $targetFilePath = "product_img/" . $fileName;
                if(in_array(pathinfo($targetFilePath,PATHINFO_EXTENSION), $allowTypes)){
                    // Hochladen der Bilder
                    if(move_uploaded_file($_FILES["file"]["tmp_name"][$i], $targetFilePath)){
                        // Einpflegen der Bilder in die Datenbank
                        $stmt = $pdo->prepare("INSERT into product_images (img, product_id) VALUES ( ? , ? )");
                        $stmt->bindValue(1, $fileName);
                        $stmt->bindValue(2, $_POST['productid'], PDO::PARAM_INT);
                        $result = $stmt->execute();
                        if (!$result) {
                            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                        }                        
                        if (!$stmt) {
                            error("Hochladen Fehlgeschlagen");
                        } 
                    } else {
                        error("Hochladen Fehlgeschlagen");
                    }
                } else {
                    error('Wir unterstützen nur JPG, JPEG, PNG & GIF Dateien.');
                }
            }
        }
        // Aktualisieren der restlichen Produkt Daten
        if(isset($_POST['name']) and isset($_POST['price']) and isset($_POST['rrp']) and isset($_POST['quantity']) and isset($_POST['desc']) and isset($_POST['productid']) and isset($_POST['categorie']) and !empty($_POST['name']) and !empty($_POST['price']) and !empty($_POST['desc']) and !empty($_POST['productid']) and !empty($_POST['categorie'])) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, rrp = ?, quantity = ?, `desc` = ?, visible = ?, product_type_id = ?, updated_at = now() WHERE products.id = ?");
            $stmt->bindValue(1, $_POST['name']);
            // Komma durch Punkt ersetzen (da sonnst error)
            $stmt->bindValue(2, str_replace(",", ".", $_POST['price']));
            $stmt->bindValue(3, str_replace(",", ".", $_POST['rrp']));
            $stmt->bindValue(4, $_POST['quantity']);
            $stmt->bindValue(5, $_POST['desc']);
            $stmt->bindValue(6, (isset($_POST['visible']) ? "1" : "0"), PDO::PARAM_INT);
            $stmt->bindValue(7, $_POST['categorie'], PDO::PARAM_INT);
            $stmt->bindValue(8, $_POST['productid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }

            echo("<script>location.href='product.php'</script>");
            exit;
        } else {
        require_once("templates/header.php");
        ?>
        <!-- Anzeigen der Produkt Modifizierung Seite -->
        <div class="minheight100 px-3 my-3">
            <div>
                <h1>Produkt anpassen</h1>
                <div>
                    <form action="product.php" method="post" enctype="multipart/form-data">
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputName">Name</span>
                            <input class="form-control" id="inputName" name="name" type="text" value="<?=$product[0]['name']?>" required>
                        </div>
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputPrice">Preis</span>
                            <input class="form-control" pattern="[\d,.]*" id="inputPrice" name="price" type="text" value="<?=$product[0]['price']?>" required>
                            <span class="input-group-text">&euro;</span>
                        </div>
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputRrp">UVP</span>
                            <input class="form-control" pattern="[\d,.]*" id="inputRrp" name="rrp" type="text" value="<?=$product[0]['rrp']?>">
                            <span class="input-group-text">&euro;</span>
                        </div>
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputQuantity">Menge</span>
                            <input class="form-control" pattern="[\d]*" id="inputQuantity" name="quantity" type="text" value="<?=$product[0]['quantity']?>" required>
                        </div>
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputDesc">Description</span>
                            <textarea  class="form-control" name="desc" id="inputDesc" required><?=$product[0]['desc']?></textarea> 
                        </div>
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputVisible">Visible</span>
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="checkbox" id="inputVisible" name="visible" <?=($product[0]['visible']==1 ? 'checked':'')?>>
                            </div>
                        </div>
                        <div class="input-group py-2" style="max-width: 50rem;">
                            <span style="width: 150px;" class="input-group-text" for="inputCategorie">Type</span>
                            <select class="form-select" id="inputCategorie" name="categorie">
                                <?php foreach ($types as $type) {
                                    if ($type['id'] == $product[0]['product_type_id']) {
                                        print('<option class="text-dark" value="' . $type['id'] . '" selected>' . $type['type'] . '</option>');
                                    } else {
                                        print('<option class="text-dark" value="' . $type['id'] . '">' . $type['type'] . '</option>');
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="row row-cols-1 row-cols-md-3 g-4 py-2">
                            <?php for ($x = 0; $x < count($imgs); $x++) :?>
                                <div class="col">
                                    <div class="card prodcard cbg2">
                                        <img src="/product_img/<?=$imgs[$x]['img']?>" class="card-img-top img-fluid rounded" alt="<?=$imgs[$x]['id']?>">
                                        <div class="card-body">
                                            <div class="input-group py-2 d-flex justify-content-center">
                                                <span class="input-group-text" for="inputVisible">Löschen?</span>
                                                <div class="input-group-text">
                                                    <input type="checkbox" class="form-check-input" value="<?=$imgs[$x]['id']?>" name="<?='delImage-'.$x?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor;?>
                        </div>
                        <div>
                            <h2>Diese Bilder werden hinzufügt</h2>
                            <div class="row row-cols-1 row-cols-md-3 g-4 py-2" id="preview">
                            </div>
                        </div>
                        <div class="row py-2 row-cols-2">
                            <div class="col-6">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="file[]" accept="image/png, image/gif, image/jpeg" multiple  onchange="showPreview(event);">
                                </div>
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <input type="number" value="<?=$_POST['productid']?>" name="productid" style="display: none;" required>
                                <button class="btn btn-success mx-1" type="submit" name="action" value="mod">Speichern</button>
                                <button class="btn btn-danger mx-1" type="button" onclick="window.location.href = '/admin/product.php';">Abbrechen</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php 
        include_once("templates/footer.php");
        exit;
        } 
    }
    // Wenn die action "add" ist
    if($_POST['action'] == 'add') {
        // Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['createProduct'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Abfrage aller ROOT Kategorien
        $stmt = $pdo->prepare('SELECT * FROM products_types where not products_types.parent_id = 0');
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }        
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Erstellen des Produktes
        if(isset($_POST['name']) and isset($_POST['price']) and isset($_POST['rrp']) and isset($_POST['quantity']) and isset($_POST['desc']) and isset($_POST['categorie']) and !empty($_POST['name']) and !empty($_POST['price']) and !empty($_POST['desc']) and !empty($_POST['categorie'])) {
            // SQL Insert für das neue Produkt
            $stmt = $pdo->prepare("INSERT INTO products (name, price, rrp, quantity, `desc`, visible, product_type_id, updated_at, created_at) VALUE (?, ?, ?, ?, ?, ?, ?, now(), now())");
            $stmt->bindValue(1, $_POST['name']);
            $stmt->bindValue(2, str_replace(",", ".", $_POST['price']));
            $stmt->bindValue(3, str_replace(",", ".", $_POST['rrp']));
            $stmt->bindValue(4, $_POST['quantity']);
            $stmt->bindValue(5, $_POST['desc']);
            $stmt->bindValue(6, (isset($_POST['visible']) ? "1" : "0"), PDO::PARAM_INT);
            $stmt->bindValue(7, $_POST['categorie'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Abfragen des soeben Hinzugefügten Produktes für die Bilder
            $stmt = $pdo->prepare('SELECT * FROM products where name = ? and `desc` = ? order by id desc');
            $stmt->bindValue(1, $_POST['name']);
            $stmt->bindValue(2, $_POST['desc']);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }            
            $productForImg = $stmt->fetchAll(PDO::FETCH_ASSOC); 
            // Wenn Dateien hochgeladen werden
            if (!empty($_FILES["file"]["name"][0])){
                $allowTypes = array('jpg','png','jpeg','gif');
                $fileCount = count($_FILES['file']['name']);
                // für jedes Bild
                for($i = 0; $i < $fileCount; $i++){
                    // Bild wird zum Abspeichern mit einer Einmaligen ID + Uhrsprungsame versehen
                    $fileName = uniqid('image_') . '_' . basename($_FILES["file"]["name"][$i]);
                    $targetFilePath = "product_img/" . $fileName;
                    if(in_array(pathinfo($targetFilePath,PATHINFO_EXTENSION), $allowTypes)){
                        // Hochladen der Bilder
                        if(move_uploaded_file($_FILES["file"]["tmp_name"][$i], $targetFilePath)){
                            // Einpflegen der Bilder in die Datenbank
                            $stmt = $pdo->prepare("INSERT into product_images (img, product_id) VALUES ( ? , ? )");
                            $stmt->bindValue(1, $fileName);
                            $stmt->bindValue(2, $productForImg[0]['id'], PDO::PARAM_INT);
                            $result = $stmt->execute();
                            if (!$result) {
                                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                            }                            
                            if (!$stmt) {
                                error("Hochladen Fehlgeschlagen");
                            } 
                        } else {
                            error("Hochladen Fehlgeschlagen");
                        }
                    } else {
                        error('Wir unterstützen nur JPG, JPEG, PNG & GIF Dateien.');
                    }
                }
            }
            echo("<script>location.href='product.php'</script>");
            exit;
        } else {
        require_once("templates/header.php");
        ?>
        <!-- Anzeigen der Seite zum Hinzufügen von Produkten -->
        <div class="minheight100 px-3 py-3">
            <h1>Produkt hinzufügen</h1>
            <div>
                <form action="product.php" method="post" enctype="multipart/form-data">
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputName">Name</span>
                        <input class="form-control" id="inputName" name="name" type="text" required>
                    </div>
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputPrice">Preis</span>
                        <input class="form-control" pattern="[\d,.]*" id="inputPrice" name="price" type="text" required>
                        <span class="input-group-text">&euro;</span>
                    </div>
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputRrp">UVP</span>
                        <input class="form-control" pattern="[\d,.]*" id="inputRrp" name="rrp" type="text">
                        <span class="input-group-text">&euro;</span>
                    </div>
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputQuantity">Menge</span>
                        <input class="form-control" pattern="[\d]*" id="inputQuantity" name="quantity" type="text" required>
                    </div>
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputDesc">Description</span>
                        <textarea  class="form-control" name="desc" id="inputDesc" required></textarea> 
                    </div>
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputVisible">Visible</span>
                        <div class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="inputVisible" name="visible">
                        </div>
                    </div>
                    <div class="input-group py-2" style="max-width: 50rem;">
                        <span style="width: 150px;" class="input-group-text" for="inputCategorie">Type</span>
                        <select class="form-select" id="inputCategorie" name="categorie">
                            <?php foreach ($types as $type) {
                                
                                print('<option class="text-dark" value="' . $type['id'] . '">' . $type['type'] . '</option>');
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <h2>Diese Bilder werden hinzufügt</h2>
                        <div class="row row-cols-1 row-cols-md-3 g-4 py-2" id="preview">
                        </div>
                    </div>
                    <div class="py-2" style="max-width: 50rem;">
                        <div class="input-group">
                            <input type="file" class="form-control" name="file[]" accept="image/png, image/gif, image/jpeg" multiple onchange="showPreview(event);">
                        </div>
                    </div>
                    <div class="py-2">
                        <button class="btn btn-success mx-1" type="submit" name="action" value="add">Speichern</button>
                        <button class="btn btn-danger mx-1" type="button" onclick="window.location.href = '/admin/product.php';">Abbrechen</button>
                    </div>
                </form>
            </div>
        </div>
        <?php 
        include_once("templates/footer.php");
        exit;
        } 
    }
    // Wenn die action "cancel" ist
    if ($_POST['action'] == 'cancel') {
        // Zurückleiten auf die Admin Produkt Seite
        echo("<script>location.href='product.php'</script>");
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM products_types, products where products.product_type_id = products_types.id ORDER BY products.id;');
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$total_products = $stmt->rowCount();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Anzeigen der Admin Produkt Seite -->
<div class="container minheight100 users content-wrapper py-3 px-3">
    <div class="row">
        <div class="py-3 px-3 cbg ctext rounded">
            <div class="d-flex justify-content-between">
                <div class="col-4">
                    <h1>Produktverwaltung</h1>
                </div>
                <div class="col-4 d-flex justify-content-end">
                    <form action="product.php" method="post">
                        <div>
                            <button type="submit" name="action" value="add" class="btn btn-outline-primary">Hinzufügen</button>
                        </div>
                    </form>
                </div>
            </div>
            <p><?php print($total_products); ?> Produkt<?=($total_products==1 ? '':'e')?></p>
            <div class="table-responsive">
                <table class="table align-middle table-borderless table-hover">
                    <thead>
                        <tr>
                            <div class="cbg ctext rounded">
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">#</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Name</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Producttype</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">RRP</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Preis</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase ctext">Quantity</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase ctext">Created</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase ctext">Visible</div>
                                </th>
                                <?php if ($user['modifyProduct'] == 1) {
                                    print('<th scope="col" class="border-0"></th>');
                                } ?>
                            </div>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="border-0 text-center">
                                    <strong><?=$product['id']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <a href="/product.php?id=<?=$product['id']?>"><strong class="ctext"><?=$product['name']?></strong></a>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$product['type']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$product['rrp']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$product['price']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$product['quantity']?></strong>
                                </td>
                                <td class="border-0">
                                    <strong><?=$product['created_at']?></strong>
                                </td>
                                <td class="border-0">
                                    <strong><input type="checkbox" class="form-check-input" <?=($product['visible']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 actions text-center">
                                <?php if ($user['modifyProduct'] == 1) {?>
                                    <form action="product.php" method="post" class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <div>
                                            <input type="number" value="<?=$product['id']?>" name="productid" style="display: none;" required>
                                            <button type="submit" name="action" value="mod" class="btn btn-outline-primary">Editieren</button>
                                        </div>
                                    </form>
                                <?php }?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>         
        </div>
    </div>
</div> 

<?php
include_once("templates/footer.php")
?>