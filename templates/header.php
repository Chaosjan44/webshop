<?php
// Fügt die php-Funktionen hinzu
require_once("php/functions.php");
// Startet eine PHP-Session
session_start();
// Überprüft ob der Benutzer eingeloogt ist 
$user1 = check_user(FALSE);
?>

<!DOCTYPE html>

<html lang="en">
<!-- Im Head werden Meta-Tags sowie Verlinkungen festgelegt, UTF-8 bedeutet, dass bspw. Umlaute verwendet werden können -->
<!-- "Viewport" sorgt dafür, dass die Website responsiv ist und sich dem Bildschirm anpasst -->
<!-- CSS & JS Dateien Verlinkt (Alles lokal, da es schneller lädt) -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/styles.css?v=<?php print(date("Y.m.d.H.i.s")); ?>">
    <link rel="stylesheet" href="/css/dark.css" disabled>
    <link rel="stylesheet" href="/css/light.css">
    <link rel="icon" type="image/png" href="/favicon.png" sizes="1024x1024" />
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <script src="/js/bootstrap.bundle.min.js"></script>
    <script src="/js/3386a0b16e.js"></script>
    <script src="/js/custom.js"></script>
    <link rel="stylesheet" href="/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/cookiebanner.css">
    <title>Delta-Hardware</title>
</head>

<!-- Eröffnung des Body Tags (Hauptteils) -->
<body>


<!-- Navigationsleiste die auf jeder einzelnen Seite zu sehen ist -->
<nav class="navbar header-header navbar-expand-lg navbar-dark cbg ctext sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <?php include_once('favicon.svg') ?>
        </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php
                    //Der folgende Code holt sich die Produktkategorien sowie Subkategorisieren, ID's und Quantität der Produkte aus der Datenbank per SQL Befehl und fügt diese im Dropdown-Menü ein
                    $stmt = $pdo->prepare("SELECT * FROM products_types WHERE parent_id = 0");
                    $result = $stmt->execute();
                    if (!$result) {
                        error('Database error', pdo_debugStrParams($stmt));
                    }
                    $roottypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    // Loopt durch alle Eltern Kategorien durch und ruft die Unterkategorien ab
                    foreach ($roottypes as $roottype) {
                        $stmt = $pdo->prepare("SELECT *, (SELECT COUNT(*) FROM products WHERE products_types.id = products.product_type_id and visible = 1) as quantity FROM products_types WHERE parent_id = ?");
                        $stmt->bindValue(1, $roottype['id'], PDO::PARAM_INT);
                        $result = $stmt->execute();
                        if (!$result) {
                            error('Database error', pdo_debugStrParams($stmt));
                        }
                        $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (isset($subtypes[0])) {
                            ?>
                                <li class="nav-item dropdown">
                                <a class="nav-link ctext dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?=$roottype['type']?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php 
                        } else { 
                            ?>
                            <li class="nav-item"><?=$roottype['type']?></li>
                            <?php
                        }
                        // Geht durch alle Unterkategorien und gibt sie aus
                        foreach ($subtypes as $subtype) {
                        ?>
                            <?php if ($subtype['type'] == "line"):?>
                                <li><hr class="dropdown-divider"></li>
                            <?php else:?>
                                <li>
                                        <a class="dropdown-item text-start" href="/products.php?type=<?=$subtype['id']?>"> <?=$subtype['type']?> (<?=$subtype['quantity']?>) </a>
                                        
                                </li>
                            <?php endif; ?>
                        <?php
                        }
                        if (isset($subtypes[0])) {
                        ?>
                            </ul>
                            </li>
                        <?php
                        }
                    }
                ?>
                <li class="nav-item"><a class="nav-link ctext" href="/products.php">Alle Produkte</a></li>
            </ul> 

            <!-- Userinput + Button mit Suchfunktion um das Navigieren zu vereinfachen -->
            <form class="d-flex" action="/products.php">
                <div class="input-group">
                    <input class="form-control" name="search" type="search" placeholder="Suchen" aria-label="Search" required>
                    <button class="btn btn-primary me-2" type="submit"><i class="fa-solid fa-search"></i></button>
                </div>
            </form>
            <!-- Der PHP Code überprüft, ob der user angemeldet ist, ist dies so dann wir dem User ein Warenkorb Icon angezeigt -->
            <?php if(isset($user1['id'])): ?>
            <a class="icon-navbar-a" href="/cart.php"><i class="fa-solid fa-cart-shopping me-2 ms-2 mt-2" id="user-icon-navbar"></i></a>
            <?php endif; if(!isset($user1['id'])): ?>
                <!-- Überprüft, ob der User angemeldet ist, wenn ja verweist das User-Icon nicht mehr auf den Login sondern auf die Einstellungen bzw öffnet das Dropdown menü-->
                <a class="icon-navbar-a" href="/<?php if(isset($user1['id'])) {print("settings.php");} else {print("login.php");} ?>"><i class="fa-solid fa-user ms-2 me-2 mt-2" id="user-icon-navbar"></i></a>
            <?php endif; if(isset($user1['id'])): ?>
            <ul class="navbar-nav mb-2 mb-lg-0">
            <li class="nav-item dropdown">
                <a class="nav-link ctext dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-user ms-2 me-2 mt-2" id="user-icon-navbar"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item " href="/internal.php">Intern</a></li>
                    <li><a class="dropdown-item" href="/settings.php">Einstellungen</a></li>
                    <li><a class="dropdown-item" href="/logout.php">Abmelden</a></li>
                </ul>
            </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php
// gibt die Benutzer Informationen an die Hauptseite weiter
return $user1;
?>