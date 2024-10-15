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
    // Wenn die action auf "mod" gesetzt ist
    if($_POST['action'] == 'mod') {
        // Rufe die Adresse mit gegebener ID ab
        $stmt = $pdo->prepare('SELECT * FROM `citys`, `address` where `address`.`citys_id` = citys.id and `address`.`user_id` = ? and `address`.`id` = ?');
        $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
        $stmt->bindValue(2, $_POST['addressid'], PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        $address = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Wenn alle benötigten Felder gesetzt und nicht leer sind
        if(isset($_POST['addressid']) and isset($_POST['street']) and isset($_POST['number']) and isset($_POST['PLZ']) and isset($_POST['city']) and !empty($_POST['addressid']) and !empty($_POST['street']) and !empty($_POST['number']) and !empty($_POST['PLZ']) and !empty($_POST['city'])) {
            // Abfrage nach angegebener Stadt und PLZ
            $stmt = $pdo->prepare('SELECT * FROM `citys` where `PLZ` = ? and city = ?');
            $stmt->bindValue(1, $_POST['PLZ']);
            $stmt->bindValue(2, $_POST['city']);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            $total = $stmt->rowCount();
            $city = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Wenn der Eintrag bereits vorhanden ist wird die CityID zwischengespeichert
            if ($total == 1) {
                $cityid = $city[0]['id'];
            // Wenn der Eintrag noch nicht vorhanden ist wird die Stadt erstellt
            } else {
                $stmt = $pdo->prepare('INSERT INTO `citys` (PLZ, city) VALUES (?, ?)');
                $stmt->bindValue(1, $_POST['PLZ']);
                $stmt->bindValue(2, $_POST['city']);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }
                // Abfrage nach der ID welche die Stadt bekommen hat
                $stmt = $pdo->prepare('SELECT * FROM `citys` where `PLZ` = ? and city = ?');
                $stmt->bindValue(1, $_POST['PLZ']);
                $stmt->bindValue(2, $_POST['city']);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }
                $total = $stmt->rowCount();
                $city = $stmt->fetchAll(PDO::FETCH_ASSOC);
                //  ID wird zwischengespeichert
                if ($total == 1) {
                    $cityid = $city[0]['id'];
                }
            }
            // Adresse wird aktualisiert
            $stmt = $pdo->prepare("UPDATE `address` SET street = ?, `number` = ?, citys_id = ?, updated_at = now() WHERE `address`.`id` = ?");
            $stmt->bindValue(1, $_POST['street']);
            $stmt->bindValue(2, $_POST['number']);
            $stmt->bindValue(3, $cityid, PDO::PARAM_INT);
            $stmt->bindValue(4, $_POST['addressid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            echo("<script>location.href='address.php'</script>");
            exit;
        } else {
        require_once("templates/header.php");
        ?>
        <!-- Anzeigen der Anpassungs Seite -->
        <div class="container minheight100 px-3 py-3">
            <div class="card cbg ctext">
                <div class="card-body">
                    <h1 class="card-title text-center ctext">Adresse anpassen</h1>
                    <form action="address.php" method="post" enctype="multipart/form-data">
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputStreet">Straße</span>
                            <input class="form-control" pattern="[A-Za-z-]+" id="inputStreet" name="street" type="text" value="<?=$address[0]['street']?>" required>
                        </div>
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputNumber">Hausnummer</span>
                            <input class="form-control" pattern="[0-9a-zA-Z-\/]+" id="inputNumber" name="number" type="text" value="<?=$address[0]['number']?>" required>
                        </div>
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputPlz">PLZ</span>
                            <input class="form-control" minlength="3" maxlength="10" pattern="[0-9]+" id="inputPlz" name="PLZ" type="text" value="<?=$address[0]['PLZ']?>" required>
                        </div>
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputCity">Stadt</span>
                            <input class="form-control" pattern="[A-Za-z-]+" id="inputCity" name="city" type="text" value="<?=$address[0]['city']?>" required>
                        </div>
                        <div class="py-2 d-flex justify-content-center">
                                <input type="number" value="<?=$_POST['addressid']?>" name="addressid" style="display: none;" required>
                                <button class="btn btn-success mx-1" type="submit" name="action" value="mod">Speichern</button>
                                <button class="btn btn-danger mx-1" type="button" onclick="window.location.href = '/address.php';">Abbrechen</button>
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
        // Wenn alle benötigten Felder gesetzt und nicht leer sind
        if(isset($_POST['street']) and isset($_POST['number']) and isset($_POST['PLZ']) and isset($_POST['city']) and !empty($_POST['street']) and !empty($_POST['number']) and !empty($_POST['PLZ']) and !empty($_POST['city'])) {
            // Abfrage nach angegebener Stadt und PLZ
            $stmt = $pdo->prepare('SELECT * FROM `citys` where `PLZ` = ? and city = ?');
            $stmt->bindValue(1, $_POST['PLZ']);
            $stmt->bindValue(2, $_POST['city']);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            $total = $stmt->rowCount();
            $city = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Wenn der Eintrag bereits vorhanden ist wird die CityID zwischengespeichert
            if ($total == 1) {
                $cityid = $city[0]['id'];
            // Wenn der Eintrag noch nicht vorhanden ist wird die Stadt erstellt
            } else {
                $stmt = $pdo->prepare('INSERT INTO `citys` (PLZ, city) VALUES (?, ?)');
                $stmt->bindValue(1, $_POST['PLZ']);
                $stmt->bindValue(2, $_POST['city']);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }
                // Abfrage nach der ID welche die Stadt bekommen hat
                $stmt = $pdo->prepare('SELECT * FROM `citys` where `PLZ` = ? and city = ?');
                $stmt->bindValue(1, $_POST['PLZ']);
                $stmt->bindValue(2, $_POST['city']);
                $result = $stmt->execute();
                if (!$result) {
                    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                }
                $total = $stmt->rowCount();
                $city = $stmt->fetchAll(PDO::FETCH_ASSOC);
                //  ID wird zwischengespeichert
                if ($total == 1) {
                    $cityid = $city[0]['id'];
                }
            }
            // Adresse wird Hinzugefügt
            $stmt = $pdo->prepare("INSERT INTO `address` (user_id, street, `number`, citys_id, updated_at, created_at) VALUES (?, ?, ?, ?, now(), now())");
            $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $_POST['street']);
            $stmt->bindValue(3, $_POST['number']);
            $stmt->bindValue(4, $cityid, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            echo("<script>location.href='address.php'</script>");
            exit;
        } else {
        require_once("templates/header.php");
        ?>
        <!-- Anzeigen der Seite zum Hinzufügen einer Adresse -->
        <div class="container minheight100 px-3 py-3">
            <div class="card cbg ctext">
                <div class="card-body">
                    <h1 class="card-title text-center ctext">Adresse Hinzufügen</h1>
                    <form action="address.php" method="post" enctype="multipart/form-data">
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputStreet">Straße</span>
                            <input class="form-control" pattern="[A-Za-z-ßäöü ]+" id="inputStreet" name="street" type="text" required>
                        </div>
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputNumber">Hausnummer</span>
                            <input class="form-control" pattern="[0-9a-zA-Z-\/]+" id="inputNumber" name="number" type="text" required>
                        </div>
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputPlz">PLZ</span>
                            <input class="form-control" minlength="3" maxlength="10" pattern="[0-9]+" id="inputPlz" name="PLZ" type="text" required>
                        </div>
                        <div class="input-group py-2">
                            <span style="width: 150px;" class="input-group-text" for="inputCity">Stadt</span>
                            <input class="form-control" pattern="[A-Za-z-ßäöü ]+" id="inputCity" name="city" type="text" required>
                        </div>
                        <div class="d-flex justify-content-center">
                                <button class="btn btn-success mx-1" type="submit" name="action" value="add">Speichern</button>
                                <button class="btn btn-danger mx-1" type="button" onclick="window.location.href = '/address.php';">Abbrechen</button>
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
    // Wenn die action "cancel" ist
    if ($_POST['action'] == 'cancel') {
        echo("<script>location.href='address.php'</script>");
        exit;
    }
}
// Abfrage der Adressen eines Users
$stmt = $pdo->prepare('SELECT * FROM `citys`, `address` where address.citys_id = citys.id and user_id = ?');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_addresses = $stmt->rowCount();
?>
<!-- Seite zur Aderessverwaltung -->
<div class="container minheight100 users content-wrapper py-3 px-3">
    <div class="row">
        <div class="py-3 px-3 cbg ctext rounded">
            <h1>Aderessverwaltung</h1>
            <form action="address.php" method="post">
                <div>
                    <button type="submit" name="action" value="add" class="btn btn-outline-primary">Hinzufügen</button>
                </div>
            </form>
            <p><?php print($total_addresses); ?> Adresse<?=($total_addresses==1 ? '':'n')?></p>
            <div class="table-responsive">
                <table class="table align-middle table-borderless table-hover">
                    <thead>
                        <tr>
                            <div class="cbg ctext rounded">
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Straße</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Hausnummer</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">PLZ</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Ort</div>
                                </th>
                                    <th scope="col" class="border-0"></th>
                            </div>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($addresses as $address): ?>
                            <tr>
                                <td class="border-0 text-center">
                                    <strong><?=$address['street']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$address['number']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$address['PLZ']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$address['city']?></strong>
                                </td>
                                <td class="border-0 actions text-center">
                                    <form action="address.php" method="post" class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <div>
                                            <input type="number" value="<?=$address['id']?>" name="addressid" style="display: none;" required>
                                            <button type="submit" name="action" value="mod" class="btn btn-outline-primary">Editieren</button>
                                        </div>
                                    </form>
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