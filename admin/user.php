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
if ($user['showUser'] != 1) {
    error('Unzureichende Berechtigungen!');
}
// Wenn "action" gesetzt ist
if(isset($_POST['action'])) {
    // Wenn action "deleteconfirm" ist
    if ($_POST['action'] == 'deleteconfirm') {
        // Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['deleteUser'] != 1 || $_POST['userid'] == 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Wenn die User ID gesetzt ist
        if(isset($_POST['userid']) and !empty($_POST['userid'])) {
            // Lösche alle security tokens mit gegebener user ID
            $stmt = $pdo->prepare('DELETE FROM securitytokens WHERE user_id = ?');
            $stmt->bindValue(1, $_POST['userid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Setzt alle Bestellungen des gegebenen Users auf den Admin user um
            $stmt = $pdo->prepare('UPDATE orders SET kunden_id = ? WHERE kunden_id = ?');
            $stmt->bindValue(1, 1, PDO::PARAM_INT);
            $stmt->bindValue(2, $_POST['userid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Frage die ID der Adresse ab.
            $stmt = $pdo->prepare('SELECT id FROM address where user_id = ?');
            $stmt->bindValue(1, $_POST['userid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            $addressid = $stmt->fetch();
            // Umschreiben der Adresse der Bestellungen
            $stmt = $pdo->prepare('UPDATE orders SET rechnungsadresse = ? WHERE rechnungsadresse = ?');
            $stmt->bindValue(1, 1, PDO::PARAM_INT);
            $stmt->bindValue(2, $addressid[0], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Umschreiben der Adresse der Bestellungen
            $stmt = $pdo->prepare('UPDATE orders SET lieferadresse = ? WHERE lieferadresse = ?');
            $stmt->bindValue(1, 1, PDO::PARAM_INT);
            $stmt->bindValue(2, $addressid[0], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Löscht die Adresse des Users
            $stmt = $pdo->prepare('DELETE FROM address WHERE user_id = ?');
            $stmt->bindValue(1, $_POST['userid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Löscht den gegebenen User
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bindValue(1, $_POST['userid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            echo("<script>location.href='user.php'</script>");
            exit;
        }
    }
    // Wenn action "mod" ist
    if($_POST['action'] == 'mod') {
        // Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['modifyUser'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Ziehe alle Daten zu gegebenen User aus der Datenbank
        $stmt = $pdo->prepare('SELECT * FROM users where users.id = ?');
        $stmt->bindValue(1, $_POST['userid'], PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        $user1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Rufe alle Berechtigungsgruppen ab
        $stmt = $pdo->prepare('SELECT * FROM permission_group');
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Wenn alle benötigten Felder für den User gesetzt und nicht leer sind
        if(isset($_POST['vorname']) and isset($_POST['nachname']) and isset($_POST['email']) and isset($_POST['passwortNeu']) and isset($_POST['passwortNeu2']) and !empty($_POST['vorname']) and !empty($_POST['nachname']) and !empty($_POST['email'])) {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, vorname = ?, nachname = ?, updated_at = now() WHERE users.id = ?");
            $stmt->bindValue(1, $_POST['email']);
            $stmt->bindValue(2, $_POST['vorname']);
            $stmt->bindValue(3, $_POST['nachname']);
            $stmt->bindValue(4, $_POST['userid'], PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
            // Überprüfe ob die eingegebenen Passwörter übereinstimmen
            if($_POST['passwortNeu'] == $_POST['passwortNeu2']) {
                // überprüft das die Passwörter nicht leer sind
                if (!empty($_POST['passwortNeu']) and !empty($_POST['passwortNeu2'])) {
                    $stmt = $pdo->prepare("UPDATE users SET passwort = ?, updated_at = now() WHERE users.id = ?");
                    $stmt->bindValue(1, password_hash($_POST['passwortNeu'], PASSWORD_DEFAULT));
                    $stmt->bindValue(2, $_POST['userid'], PDO::PARAM_INT);
                    $result = $stmt->execute();
                    if (!$result) {
                        error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                    }                    
                }
            } else {
                error('Passwörter stimmen nicht überein!');
            }
            // Wenn der Admin die Berechtigungen hat User Perms anzupassen
            if ($user['modifyUserPerms'] == 1) {
                // Wenn eine Berechtigungsgruppe für den User gesetzt ist und diese nicht nichts ist
                if (isset($_POST['permissions']) and !empty($_POST['permissions'])) {
                    $stmt = $pdo->prepare("UPDATE users SET permission_group = ?, updated_at = now() WHERE users.id = ?");
                    $stmt->bindValue(1, $_POST['permissions'], PDO::PARAM_INT);
                    $stmt->bindValue(2, $_POST['userid'], PDO::PARAM_INT);
                    $result = $stmt->execute();
                    if (!$result) {
                        error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                    }                    
                }
            }
            echo("<script>location.href='user.php'</script>");
            exit;
        } else {
        require_once("templates/header.php");
        ?>
        <!-- Formular zur Bearbeitung des Users anzeigen -->
        <div class="minheight100 px-3 py-3">
            <h1>Einstellungen</h1>
            <div>
                <form action="user.php" method="post">
                    <div class="row d-flex justify-content-between">
                        <div class="col-6">
                            <div class="input-group py-2">
                                <span class="input-group-text" for="inputVorname" style="min-width: 150px;">Vorname</span>
                                <input class="form-control" id="inputVorname" name="vorname" type="text" value="<?=$user1[0]['vorname']?>" required>
                            </div>
                            <div class="input-group py-2">
                                <span class="input-group-text" for="inputNachname" style="min-width: 150px;">Nachname</span>
                                <input class="form-control" id="inputNachname" name="nachname" type="text" value="<?=$user1[0]['nachname']?>" required>
                            </div>
                            <div class="input-group py-2">    
                                <span class="input-group-text" for="inputEmail" style="min-width: 150px;">E-Mail</span>
                                <input class="form-control" id="inputEmail" name="email" type="email" value="<?=$user1[0]['email']?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group py-2">
                                <span class="input-group-text" for="inputPasswortNeu" style="min-width: 300px;">Neues Passwort</span>
                                <input class="form-control" id="inputPasswortNeu" name="passwortNeu" type="password">
                            </div>
                            <div class="input-group py-2">
                                <span class="input-group-text" for="inputPasswortNeu2" style="min-width: 300px;">Neues Passwort (wiederholen)</span>
                                <input class="form-control" id="inputPasswortNeu2" name="passwortNeu2" type="password">
                            </div>
                            <?php if ($user['modifyUserPerms'] == 1) {?>
                                <div class="input-group py-2">
                                    <span class="input-group-text" for="permissions" style="min-width: 300px;">Permissions</span>
                                    <select class="form-select" id="permissions" name="permissions">
                                        <?php foreach ($permissions as $permission) {
                                            if ($permission['id'] == $user1[0]['permission_group']) {
                                                print('<option class="text-dark" value="' . $permission['id'] . '" selected>' . $permission['name'] . '</option>');
                                            } else { 
                                                print('<option class="text-dark" value="' . $permission['id'] . '">' . $permission['name'] . '</option>');
                                            }
                                        }?>
                                    </select>
                                </div>
                            <?php }?>
                        </div>
                    </div>
                    <input type="number" value="<?=$_POST['userid']?>" name="userid" style="display: none;" required>
                    <button type="submit" name="action" value="mod" class="me-2 btn btn-outline-success">Speichern</button>
                    <button type="submit" name="action" value="cancel" class="ms-2 btn btn-outline-danger">Abrechen</button>
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
        // Zurückleiten auf die Admin User übersicht
        echo("<script>location.href='user.php'</script>");
        exit;
    }
}
// FRage alle user und Berechtigungsgruppen ab
$stmt = $pdo->prepare('SELECT * FROM permission_group, users where users.permission_group = permission_group.id ORDER BY users.id');
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$total_users = $stmt->rowCount();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Admin user Seite -->
<div class="container minheight100 users content-wrapper py-3 px-3">
    <div class="row">
        <div class="py-3 px-3 cbg ctext rounded">
            <div class="d-flex justify-content-between">
                <div class="col-4">
                    <h1>Benutzerverwaltung</h1>
                </div>
                <div class="col-4 d-flex justify-content-end">
                    <div>
                        <!-- Weiterleiten auf die register page -->
                        <button class="btn btn-outline-primary" onclick="window.location.href = '/register.php';">Hinzufügen</button>
                    </div>
                </div>
            </div>
            <p><?php print($total_users); ?> Benutzer</p>
            <div class="table-responsive">
                <table class="table align-middle table-borderless table-hover">
                    <thead>
                        <tr>
                            <div class="cbg ctext rounded">
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">#</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Vorname</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">Nachname</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext">E-Mail</div>
                                </th>
                                <th scope="col" class="border-0 text-center">
                                    <div class="p-2 px-3 text-uppercase ctext"><a href="/admin/perms.php">Rechte</a></div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-3 text-uppercase ctext">Erstellt</div>
                                </th>
                                <th scope="col" class="border-0" style="width: 15%"></th>
                            </div>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user1): ?>
                            <tr>
                                <td class="border-0 text-center">
                                    <strong><?=$user1['id']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$user1['vorname']?></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$user1['nachname']?></strong>
                                </td>
                                <td class="border-0 bl-400 text-center"">
                                    <strong><a href="mailto:<?=$user1['email']?>"><?=$user1['email']?></a></strong>
                                </td>
                                <td class="border-0 text-center">
                                    <strong><?=$user1['name']?></strong>
                                </td>
                                <td class="border-0">
                                    <strong><?=$user1['created_at']?></strong>
                                </td>
                                <td class="border-0 actions text-center">
                                <?php if ($user['modifyUser'] == 1 or $user['deleteUser'] == 1) {?>
                                    <form action="user.php" method="post" class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <?php if ($user['modifyUser'] == 1) {?>
                                        <div class="">
                                            <input type="number" value="<?=$user1['id']?>" name="userid" style="display: none;" required>
                                            <button type="submit" name="action" value="mod" class="btn btn-outline-primary">Editieren</button>
                                        </div>
                                        <?php }?>
                                        <?php if ($user['deleteUser'] == 1) {?>
                                        <div class="">
                                            <input type="number" value="<?=$user1['id']?>" name="userid" style="display: none;" required>
                                            <button class="btn btn-outline-danger" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas<?=$user1['id']?>" aria-controls="offcanvas<?=$user1['id']?>">Löschen</button>
                                            <div class="offcanvas offcanvas-end cbg" data-bs-scroll="true" tabindex="-1" id="offcanvas<?=$user1['id']?>" aria-labelledby="offcanvas<?=$user1['id']?>Label">
                                                <div class="offcanvas-header">
                                                    <h2 class="offcanvas-title ctext" id="offcanvas<?=$user1['id']?>Label">Wirklich Löschen?</h2>
                                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                                </div>
                                                <div class="offcanvas-body">
                                                    <button class="btn btn-outline-success mx-2" type="submit" name="action" value="deleteconfirm">Ja</button>
                                                    <button class="btn btn-outline-danger mx-2" type="button" data-bs-dismiss="offcanvas" aria-label="Close">Nein</button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php }?>
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