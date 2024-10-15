<?php
// Aufgrund des Unterordners muss hier erst wieder auf den DOCUMENT ROOT gewechselt werden
chdir ($_SERVER['DOCUMENT_ROOT']);
// Einbindung der functions.php
require_once("php/functions.php");
// Einbindung des Headers, gleichzeitig werden wenn der User angemeldet ist die User Daten übergeben
$user = require_once("templates/header.php");
// zeigt die Login Seite an wenn der User nicht angemeldet ist
if (!isset($user['id'])) {
    require_once("login.php");
    exit;
}
// Zeit die Error Seite wenn der User keine Berechtigungen hat die Kategorien zu sehen
if ($user['showCategories'] != 1) {
    error('Unzureichende Berechtigungen!');
}
// Überprüft ob eine Action zu erledigen ist 
if(isset($_POST['action'])) {
    // überprüft ob die aktion "add" ist
    if($_POST['action'] == 'add') {
        // überprüft ob der User die Berechtigungen hat Kategorien zu bearbeiten
        if ($user['modifyCategories'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Überprüft ob benötigten Variablen gesetzt sind
        if (isset($_POST['categoriesname']) and isset($_POST['parentcategorie'])) {
            // SQL INSERT für unsere Produkt Typen
            $stmt = $pdo->prepare('INSERT INTO products_types (type, parent_id) VALUES (?,?)');
            $stmt->bindValue(1, $_POST['categoriesname']);
            $stmt->bindValue(2, $_POST['parentcategorie']);
            $result = $stmt->execute();
            // Zeige Error wenn ein Fehler beim Einfügen auftritt
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // zurückleiten auf die Seite zur Bearbeitung
            echo("<script>location.href='categories.php'</script>");
        // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
    // überprüft ob die action "del" (also delete) ist
    if($_POST['action'] == 'del') {
        // überprüft ob der User die Berechtigungen hat Kategorien zu bearbeiten
        if ($user['modifyCategories'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Überprüft ob benötigten Variablen gesetzt sind
        if(isset($_POST['categoriesid']) and !empty($_POST['categoriesid'])) {
            // überprüfe ob die Confirm gesetzt und nicht lehr ist
            if (isset($_POST['confirm']) and !empty($_POST['confirm'])) {
                // überprüfe ob der User den Ja button gedrückt hat
                if ($_POST['confirm'] == 'yes') {
                    // SQL UPDATE für unsere Produkte (hier wird der Produkt Typ (Kategorie) auf eine andere Kategorie gesetzt)
                    $stmt = $pdo->prepare('UPDATE products SET product_type_id = ? WHERE product_type_id = ?');
                    $stmt->bindValue(1, $_POST['newparentcategorie'], PDO::PARAM_INT);
                    $stmt->bindValue(2, $_POST['categoriesid'], PDO::PARAM_INT);
                    $result = $stmt->execute();
                    // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
                    if (!$result) {
                        error('Datenbank Fehler', pdo_debugStrParams($stmt));
                    }
                    // Produkt Typ wird gelöscht
                    $stmt = $pdo->prepare('DELETE FROM products_types WHERE id = ?');
                    $stmt->bindValue(1, $_POST['categoriesid'], PDO::PARAM_INT);
                    $result = $stmt->execute();
                    // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
                    if (!$result) {
                        error('Datenbank Fehler', pdo_debugStrParams($stmt));
                    }
                    // zurückleiten auf die Seite zur Bearbeitung
                    echo("<script>location.href='categories.php'</script>");
                    exit;
                // Wenn der User nicht Ja Klickt
                } else {
                    // zurückleiten auf die Seite zur Bearbeitung
                    echo("<script>location.href='categories.php'</script>");
                    exit;
                }
            // Bestätigungs Formular anzeigen
            } else {
                // Ruft alle nicht root Kategorien der Datenbank ab
                $stmt = $pdo->prepare('SELECT * from products_types WHERE NOT parent_id = 0');
                $result = $stmt->execute();
                // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
                if (!$result) {
                    error('Datenbank Fehler', pdo_debugStrParams($stmt));
                }
                // Alle Kategorien werden in das array "root_cats" geschrieben (mit der Abkürzung root_cats sind nicht Wurzel Katzen gemeint)
                $root_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Abfrage aller Produkt Kategorien
                $stmt = $pdo->prepare('SELECT * from products_types WHERE id = ?');
                $stmt->bindValue(1, $_POST['categoriesid'], PDO::PARAM_INT);
                $result = $stmt->execute();
                // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
                if (!$result) {
                    error('Datenbank Fehler', pdo_debugStrParams($stmt));
                }
                // Abfrage wird in eine Temporäre Variable gespeichert
                $tmp = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Bestätigungs Formular
                ?>
                    <div class="container-fluid">
                        <div class="row no-gutter">
                            <div class="minheight100 col py-4 px-3">
                                <div class="card cbg text-center mx-auto" style="width: 75%;">
                                    <div class="card-body">
                                        <h1 class="card-title mb-2 text-center">Wirklich Löschen?</h1>
                                        <?php if ($tmp[0]['parent_id'] != 0) { ?>
                                            <h2 class="card-title mb-2 text-center">Alle Produkte werden in folgende Gruppe verschoben!</h2>
                                        <?php } ?>
                                        <p class="text-center">
                                            <form action="categories.php" method="post">
                                                <?php if ($tmp[0]['parent_id'] != 0) { ?>
                                                    <select class="form-select mb-3" id="newparentcategorie" name="newparentcategorie">
                                                        <?php foreach ($root_cats as $root_cat) {
                                                            print('<option class="text-dark" value="' . $root_cat['id'] . '">' . $root_cat['type'] . '</option>');
                                                        }
                                                        ?>
                                                    </select>
                                                <?php } else {
                                                    print('<input type="number" value="0" name="newparentcategorie" style="display: none;" required>');
                                                } ?>
                                                <input type="number" value="<?=$_POST['categoriesid']?>" name="categoriesid" style="display: none;" required>
                                                <input type="text" value="del" name="action" style="display: none;" required>
                                                <button class="btn btn-outline-success mx-2" type="submit" name="confirm" value="yes">Ja</button>
                                                <a href="categories.php"><button class="btn btn-outline-danger mx-2" type="button">Nein</button></a>
                                            </form>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <!-- Einbindung des Footers -->
                <?php
                require_once("templates/footer.php");
                exit;
            }
        // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
    // überprüft ob die action "add" ist
    if($_POST['action'] == 'mod') {
        // überprüft ob der User die Berechtigungen hat Kategorien zu bearbeiten
        if ($user['modifyCategories'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Update der Elternkategorie & des Namens
        $stmt = $pdo->prepare("UPDATE products_types SET type = ?, parent_id = ? WHERE id = ?");
        $stmt->bindValue(1, $_POST['categoriesname']);
        $stmt->bindValue(2, $_POST['parentcategories'], PDO::PARAM_INT);
        $stmt->bindValue(3, $_POST['categoriesid'], PDO::PARAM_INT);
        $result = $stmt->execute();
        // Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
        if (!$result) {
            error('Datenbank Fehler', pdo_debugStrParams($stmt));
        }
        // zurückleiten auf die Seite zur Bearbeitung
        echo("<script>location.href='categories.php'</script>");
        exit;
    }
    // Wenn die action "cancel" ist
    if ($_POST['action'] == 'cancel') {
        // zurückleiten auf die Seite zur Bearbeitung
        echo("<script>location.href='categories.php'</script>");
        exit;
    }
}
// Abfrage der Kategorien und Zählung der Produkte
$stmt = $pdo->prepare('SELECT *,(SELECT COUNT(*) FROM products WHERE products_types.id = products.product_type_id) as products from products_types');
$result = $stmt->execute();
// Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
if (!$result) {
    error('Datenbank Fehler', pdo_debugStrParams($stmt));
}
// Ergebnis der Abfrage wird in categories eingefügt
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Abfrage aller ROOT Kategorien
$stmt = $pdo->prepare('SELECT * from products_types where parent_id = 0');
$result = $stmt->execute();
// Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
if (!$result) {
    error('Datenbank Fehler', pdo_debugStrParams($stmt));
}
// Alle Kategorien werden in das array "root_cats" geschrieben (mit der Abkürzung root_cats sind nicht Wurzel Katzen gemeint)
$root_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container minheight100 users content-wrapper py-3 px-3">
    <div class="row">
        <div class="py-3 px-3 cbg rounded">
            <div class="d-flex justify-content-between">
                <div class="col-4">
                    <h1>Menüverwaltung</h1>
                </div>
                <div class="col-7 d-flex justify-content-end">
                    <form action="categories.php" method="post" class="">
                        <div class="input-group">
                            <input type="text" name="categoriesname" class="form-control" required>
                            <select class="form-select" id="parentcategorie" name="parentcategorie">
                                <?php foreach ($root_cats as $root_cat) {
                                    print('<option class="text-dark" value="' . $root_cat['id'] . '">' . $root_cat['type'] . '</option>');
                                }
                                print('<option class="text-dark" value="0">ROOT</option>');
                                ?>
                            </select>
                            <button type="submit" name="action" value="add" class="btn btn-outline-primary">Hinzufügen</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <div class="cbg rounded">
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase">#</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase">Name</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase">Eltern Kategorie</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase text-center">Products</div>
                                </th>
                                <?php if ($user['modifyCategories'] == 1) {?>
                                <th scope="col" class="border-0" style="width: 15%">
                                </th>
                                <?php }?>
                            </div>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $categorie): ?>
                            <?php if ($user['modifyCategories'] == 1) {?> 
                                <tr>
                                    <form action="categories.php" method="post" class="">
                                        <td class="border-0 align-middle">
                                            <strong><?=$categorie['id']?></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <input class="form-control" id="categoriesname" name="categoriesname" type="text" value="<?=$categorie['type']?>" required>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <select class="form-select" id="parentcategories" name="parentcategories">
                                                <?php foreach ($root_cats as $root_cat) {
                                                    if ($root_cat['id'] == $categorie['parent_id']) {
                                                        print('<option class="text-dark" value="' . $root_cat['id'] . '" selected>' . $root_cat['type'] . '</option>');
                                                    } else {
                                                        print('<option class="text-dark" value="' . $root_cat['id'] . '">' . $root_cat['type'] . '</option>');
                                                    }
                                                }
                                                if ($categorie['parent_id'] == 0) {
                                                    print('<option class="text-dark" value="0" selected>ROOT</option>');
                                                } else {
                                                    print('<option class="text-dark" value="0">ROOT</option>');
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><a><?=$categorie['products']?></a></strong>
                                        </td>
                                        
                                        <td class="border-0 align-middle actions">
                                            <div class="px-1 py-1">
                                                <input type="number" value="<?=$categorie['id']?>" name="categoriesid" style="display: none;" required>
                                                <button type="submit" name="action" value="mod" class="btn btn-outline-success">Speichern</button>
                                            </div>
                                            <div class="px-1 py-1">
                                                <button type="submit" name="action" value="del" class="btn btn-outline-danger">Löschen</button>
                                            </div>
                                        </td>
                                    </form>
                                </tr>

                            <?php } else {?>
                            <tr>
                                <td class="border-0 align-middle">
                                    <strong><?=$categorie['id']?></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><?=$categorie['type']?></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <?php foreach ($root_cats as $root_cat) {
                                        if ($root_cat['id'] == $categorie['parent_id']) {
                                            print('<a value="' . $root_cat['id'] . '">' . $root_cat['type'] . '</a>');
                                        }
                                    }
                                    if ($categorie['parent_id'] == 0) {
                                        print('<a value="0">ROOT</a>');
                                    }
                                    ?>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><a><?=$categorie['products']?></a></strong>
                                </td>

                            </tr>
                            <?php }?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>         
        </div>
    </div>
</div> 
<!-- Footer Einbindung -->
<?php
include_once("templates/footer.php")
?>