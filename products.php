<?php
// bindet die PHP-Funktionen ein
require_once("php/functions.php");
$type = "";
$search = "";
$type = "";
$sortsql = "";

// generiere SQL für die Sortierung
if (isset($_GET["sortby"])) {
    $order = "";
    if ($_GET["order"] == "Absteigend"){
        $order = " DESC";
    }
    $sortsql = "ORDER BY products." . $_GET["sortby"] . $order;
}
// generiere SQL für Typenbeziehung
if (isset($_GET["type"])) {
    $type = "and products.product_type_id = '" . $_GET["type"] . "' ";
}
// generiere SQL für die Suche
if (isset($_GET["search"])) {
    
    if (is_numeric($_GET["search"])) {
        $search .= 'and products.id = ' . $_GET["search"] . ' ';
    } else {
        $search = 'and lower(products.name) like lower("%' . $_GET["search"] . '%")';
        $stmt = $pdo->prepare('SELECT * FROM products where visible = 1 ' . $type . $search);
        $result = $stmt->execute();
        if (!$result) {
            error('Datenbank Fehler', pdo_debugStrParams($stmt));
        }
        if ($stmt->rowCount() < 1) {
            $search = '';
            $search_pieces = explode(" ", $_GET["search"]);
            foreach ($search_pieces as $search_piece) {
                if (!empty($search_piece) and $search_piece != '') {
                    $search_piece = trim($search_piece);
                    $search .= 'and lower(products.name) like lower("%' . $search_piece . '%") ';
                }
            }
        }
    }
}
// Suche Produkte aus der Datenbank und sortiere nach oben generiertem SQL
$stmt = $pdo->prepare('SELECT * ,(SELECT img From product_images WHERE product_images.product_id=products.id ORDER BY id LIMIT 1) AS image FROM products where visible = 1 ' . $type . $search . $sortsql);
$result = $stmt->execute();
// Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
if (!$result) {
    error('Datenbank Fehler', pdo_debugStrParams($stmt));
}
// Zähle Zeilen für maximale Anzahl an Produkten
$total_products = $stmt->rowCount();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Header Einbindung
require_once("templates/header.php");
?>

<div class="container-fluid minheight100 py-3 products content-wrapper">
    <h1 class="ctext my-2">Produkte</h1>
    <form action="products.php" method="get" class="my-2">
        <div class="input-group">
            <select class="form-select" name="sortby">
                <option class="text-dark" value="name" <?php if (isset($_GET["sortby"]) and $_GET["sortby"] == 'name') { print('selected="selected"');} ?>>Name</option>
                <option class="text-dark" value="price" <?php if (isset($_GET["sortby"]) and $_GET["sortby"] == 'price') { print('selected="selected"');} ?>>Preis</option>
                <option class="text-dark" value="rrp" <?php if (isset($_GET["sortby"]) and $_GET["sortby"] == 'rrp') { print('selected="selected"');} ?>>UVP</option>
                <option class="text-dark" value="created_at" <?php if (isset($_GET["sortby"]) and $_GET["sortby"] == 'created_at') { print('selected="selected"');} ?>>Date</option>
            </select>
            <?php foreach (array_keys($_GET) as $getindex) {
                if ($getindex != "order" && $getindex != "sortby") {
                    print('<input type=text name="' . $getindex . '" value="' . $_GET[$getindex] . '" hidden>');
            } } ?>
            <input class="btn btn-primary" type="Submit" value="Aufsteigend" name="order"></input>
            <input class="btn btn-primary" type="Submit" value="Absteigend" name="order"></input>
        </div>
    </form>
    <p class="my-2"><?php print($total_products); ?> Produkte</p>
    <div class="products-wrapper row row-cols-1 row-cols-md-4 g-4">
        <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card prodcard cbg">
                    <a href="product.php?id=<?=$product['id']?>" class="product stretched-link">
                        <div class="card-body">
                            <?php if (empty($product['image'])) {
                                print('<img src="images/image-not-found.png" class="card-img-top rounded mb-3" alt="' . $product['name'] . '">');
                            } else {
                                print('<img src="product_img/' . $product['image'] . '" class="card-img-top rounded mb-3" alt="' . $product['name'] . '">');
                            }?>
                            <h4 class="card-title name"><?=$product['name']?></h4>
                            <p class="card-text ctext price">Preis: 
                                <?=$product['price']?>&euro;
                                <?php if ($product['rrp'] > 0): ?>
                                <span class="rrp ctext"><br>UVP: <?=$product['rrp']?> &euro;</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
// Bindet den Footer ein
include_once("templates/footer.php")
?>
