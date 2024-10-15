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
if ($user['showUserPerms'] != 1) {
    error('Unzureichende Berechtigungen!');
}
// Überprüfe ob die POST Action "action" gesetzt ist
if(isset($_POST['action'])) {
    // Überprüfe ob die POST Action "action" auf "add" gesetzt ist
    if($_POST['action'] == 'add') {
		// Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['modifyUserPerms'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Wenn "permsname" gesetzt ist
        if (isset($_POST['permsname'])) {
            // Hinzufügen einer Berechtigungsgruppe
            $stmt = $pdo->prepare('INSERT INTO permission_group (name) VALUES (?)');
            $stmt->bindValue(1, $_POST['permsname']);
            $result = $stmt->execute();
            // Zeige die Error Page mit der Meldung "Datenbank Fehler!"
            if (!$result) {
                error('Datenbank Fehler!', pdo_debugStrParams($stmt));
            }
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
    // Überprüfe ob die POST Action "action" auf "del" gesetzt ist
    if($_POST['action'] == 'del') {
        // Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['modifyUserPerms'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Wenn "permsid" gesetzt und nicht leer ist
        if(isset($_POST['permsid']) and !empty($_POST['permsid'])) {
            // Wenn POST "confirm" gesetzt und nicht leer ist
            if (isset($_POST['confirm']) and !empty($_POST['confirm'])) {
                // Wenn "confirm" auf "yes" gesetzt ist
                if ($_POST['confirm'] == 'yes') {
                    // Wenn die Berechtigungsgruppe nicht die ID 1 oder 2 hat (um sicherzustellen das die default und Admin Gruppen nicht gelöscht werden können)
                    if (!(( $perms['id'] == 1 ) || ( $perms['id'] == 2 ))) {
                        // Setze die Berechtigungsgruppe der user welche in der zu Löschenden Gruppe waren auf die default Gruppe
                        $stmt = $pdo->prepare('UPDATE users SET permission_group = ? WHERE permission_group = ?');
                        $stmt->bindValue(1, 1, PDO::PARAM_INT);
                        $stmt->bindValue(2, $_POST['permsid'], PDO::PARAM_INT);
                        $result = $stmt->execute();
                        // Zeige die Error Page mit der Meldung "Datenbank Fehler!"
                        if (!$result) {
                            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                        }
                        // Lösche die Berechtigungsgruppe
                        $stmt = $pdo->prepare('DELETE FROM permission_group WHERE id = ?');
                        $stmt->bindValue(1, $_POST['permsid'], PDO::PARAM_INT);
                        $result = $stmt->execute();
                        // Zeige die Error Page mit der Meldung "Datenbank Fehler!"
                        if (!$result) {
                            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
                        }
                        // Weiterleitung auf die perms.php
                        echo("<script>location.href='perms.php'</script>");
                        exit;
                    } 	
                    // Zeit die Error Seite wenn der User keine Berechtigungen hat
                    else {
                        error('Unzureichende Berechtigungen!');
                    }
                } else {
                    echo("<script>location.href='perms.php'</script>");
                    exit;
                }
            // Zeige Bestätigungsanfrage
            } else {
                ?>
                    <div class="container-fluid">
                        <div class="row no-gutter">
                            <div class="minheight100 col py-4 px-3">
                                <div class="card cbg text-center mx-auto" style="width: 75%;">
                                    <div class="card-body">
                                        <h1 class="card-title mb-2 text-center">Wirklich Löschen?</h1>
                                        <h2 class="card-title mb-2 text-center">Alle Benutzer in dieser Gruppe werden in Default verschoben!</h2>
                                        <p class="text-center">
                                            <form action="perms.php" method="post">
                                                <input type="number" value="<?=$_POST['permsid']?>" name="permsid" style="display: none;" required>
                                                <input type="text" value="del" name="action" style="display: none;" required>
                                                <button class="btn btn-outline-primary mx-2" type="submit" name="confirm" value="yes">Ja</button>
                                                <a href="perms.php"><button class="btn btn-outline-primary mx-2" type="button">Nein</button></a>
                                            </form>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                require_once("templates/footer.php");
                exit;
            }
        } else {
            error('Fehlende Informationen! Bitte erneut versuchen.');
        }
    }
    // Überprüfe ob die POST Action "action" auf "mod" gesetzt ist
    if($_POST['action'] == 'mod') {
        // Zeit die Error Seite wenn der User keine Berechtigungen hat
        if ($user['modifyUserPerms'] != 1) {
            error('Unzureichende Berechtigungen!');
        }
        // Update der ausgewählten Gruppe
        $stmt = $pdo->prepare("UPDATE permission_group SET showUser = ?, modifyUser = ?, deleteUser = ?, modifyUserPerms = ?, showUserPerms = ?, showProduct = ?, createProduct = ?, modifyProduct = ?, showCategories = ?, modifyCategories = ?, deleteCategories = ?, createCategories = ?, showOrders = ?, markOrders = ? WHERE permission_group.id = ?");
        // if "showUser" is set then value = 1 else 0
        $stmt->bindValue(1, (isset($_POST['showUser']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(2, (isset($_POST['modifyUser']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(3, (isset($_POST['deleteUser']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(4, (isset($_POST['modifyUserPerms']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(5, (isset($_POST['showUserPerms']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(6, (isset($_POST['showProduct']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(7, (isset($_POST['createProduct']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(8, (isset($_POST['modifyProduct']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(9, (isset($_POST['showCategories']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(10, (isset($_POST['modifyCategories']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(11, (isset($_POST['deleteCategories']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(12, (isset($_POST['createCategories']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(13, (isset($_POST['showOrders']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(14, (isset($_POST['markOrders']) ? "1" : "0"), PDO::PARAM_INT);
        $stmt->bindValue(15, $_POST['permsid'], PDO::PARAM_INT);
        $result = $stmt->execute();
        // Zeige die Error Page mit der Meldung "Datenbank Fehler!"
        if (!$result) {
            error('Datenbank Fehler!', pdo_debugStrParams($stmt));
        }
        echo("<script>location.href='perms.php'</script>");
        exit;
    }
    // Wenn die action "cancel" ist
    if ($_POST['action'] == 'cancel') {
        echo("<script>location.href='perms.php'</script>");
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM permission_group');
$result = $stmt->execute();
// Zeige die Error Page mit der Meldung "Datenbank Fehler!"
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container minheight100 py-3 px-3 mx-auto">
    <div class="row">
        <div class="py-3 px-3 cbg rounded perms">
            <div class="d-flex justify-content-between">
                <div class="col-4">
                    <h1>Rechteverwaltung</h1>
                </div>
                <div class="col-4">
                    <form action="perms.php" method="post" class="input-group">
                        <input type="text" name="permsname" class="form-control" required>
                        <button type="submit" name="action" value="add" class="btn btn-outline-primary">Hinzufügen</button>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <div class="cbg rounded">
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">#</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Name</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Show User</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Modify User</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Delete User</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Show User Permission</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Modify User Permission</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Show Product</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Create Product</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Modify Product</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Show Kategorien</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Modify Kategorien</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Delete Kategorien</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Create Kategorien</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Show Order</div>
                                </th>
                                <th scope="col" class="border-0">
                                    <div class="p-2 px-1 text-uppercase">Mark Order</div>
                                </th>
                                <?php if ($user['modifyUserPerms'] == 1) {?>
                                    <th scope="col" class="border-0" style="width: 15%">
                                    </th>
                                <?php }?>
                            </div>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $perms): ?>
                            <?php if ($user['modifyUserPerms'] == 1) {?>
                                <tr>
                                    <form action="perms.php" method="post" class="">
                                        <td class="border-0 align-middle">
                                            <strong><?=$perms['id']?></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><?=$perms['name']?></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="showUser" <?=($perms['showUser']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="modifyUser" <?=($perms['modifyUser']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="deleteUser" <?=($perms['deleteUser']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="showUserPerms" <?=($perms['showUserPerms']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="modifyUserPerms" <?=($perms['modifyUserPerms']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="showProduct" <?=($perms['showProduct']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="createProduct" <?=($perms['createProduct']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="modifyProduct" <?=($perms['modifyProduct']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="showCategories" <?=($perms['showCategories']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="modifyCategories" <?=($perms['modifyCategories']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="deleteCategories" <?=($perms['deleteCategories']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="createCategories" <?=($perms['createCategories']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="showOrders" <?=($perms['showOrders']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle text-center">
                                            <strong><input type="checkbox" class="form-check-input" name="markOrders" <?=($perms['markOrders']==1 ? 'checked':'')?>></strong>
                                        </td>
                                        <td class="border-0 align-middle actions">
                                            <div class="px-1 py-1">
                                                <input type="number" value="<?=$perms['id']?>" name="permsid" style="display: none;" required>
                                                <button type="submit" name="action" value="mod" class="btn btn-outline-success">Speichern</button>
                                            </div>
                                            <?php if (!(( $perms['id'] == 1 ) || ( $perms['id'] == 2 ))) {?>
                                            <div class="px-1 py-1">
                                                <button type="submit" name="action" value="del" class="btn btn-outline-danger">Löschen</button>
                                            </div>
                                            <?php }?>
                                        </td>
                                    </form>
                                </tr>

                            <?php } else {?>
                            <tr>
                                <td class="border-0 align-middle">
                                    <strong><?=$perms['id']?></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><?=$perms['name']?></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="showUser" <?=($perms['showUser']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="modifyUser" <?=($perms['modifyUser']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="deleteUser" <?=($perms['deleteUser']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="showUserPerms" <?=($perms['showUserPerms']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="modifyUserPerms" <?=($perms['modifyUserPerms']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="showProduct" <?=($perms['showProduct']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="createProduct" <?=($perms['createProduct']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="modifyProduct" <?=($perms['modifyProduct']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="showCategories" <?=($perms['showCategories']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="modifyCategories" <?=($perms['modifyCategories']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="deleteCategories" <?=($perms['deleteCategories']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="createCategories" <?=($perms['createCategories']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="showOrders" <?=($perms['showOrders']==1 ? 'checked':'')?> disabled></strong>
                                </td>
                                <td class="border-0 align-middle text-center">
                                    <strong><input type="checkbox" class="form-check-input" name="markOrders" <?=($perms['markOrders']==1 ? 'checked':'')?> disabled></strong>
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

<?php
include_once("templates/footer.php")
?>