<?php
require_once("php/functions.php");
$num_products_on_each_page = 4;
// Leite auf die Produktübersicht weiter, wenn keine Produkt ID in der URL gesetzt ist
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if (!isset($_GET["id"])) {
    header("location: products.php");
}
// Datenbankabfrage zum Produkt mit entsprechender ID
$stmt = $pdo->prepare('SELECT * FROM products where id = ?');
$stmt->bindValue(1, $_GET["id"], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
if ($stmt->rowCount() != 1) {
    header("location: products.php");
}
$product = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Abfrage der Produkt Bilder zum Produkt
$stmt = $pdo->prepare('SELECT * FROM product_images where product_id = ?');
$stmt->bindValue(1, $product[0]['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Abfrage der Häufig zusammengekauften Produkte
$stmt = $pdo->prepare('SELECT *, (SELECT img From product_images WHERE product_images.product_id=products.id ORDER BY id LIMIT 1) AS image, COUNT(*) as counter FROM product_list, products WHERE product_list.list_id IN (SELECT product_list.list_id FROM product_list WHERE product_list.product_id = ?) AND NOT product_list.product_id = ? and product_list.product_id = products.id GROUP BY product_list.product_id ORDER BY counter DESC LIMIT 3;');
$stmt->bindValue(1, $product[0]['id'], PDO::PARAM_INT);
$stmt->bindValue(2, $product[0]['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once("templates/header.php");
?>
<div class="container-fluid minheight100 px-3 py-3 row row-cols-1 row-cols-md-2 gx-0 product content-wrapper">
    <div class="col">
        <div class="card cbg mx-2">
            <div class="card-body px-3 py-3">
                <div id="carouselExampleDark" class="carousel carousel-dark slide" data-bs-ride="carousel">
                    <?php if($images == null):?>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="images/image-not-found.png" class="img-fluid mx-auto d-block rounded" alt="<?=$product[0]['name']?>">
                            </div>
                        </div>
                    <?php elseif (count($images) == 1):?>
                        <div class="carousel-inner">
                            <?php foreach ($images as $image) {
                                print('<div class="carousel-item active">');
                                    print('<img src="product_img/'.$image['img'].'" class="img-fluid mx-auto d-block rounded" alt="'.$product[0]['name'].'">');
                                print('</div>');
                            } ?>
                        </div>
                    <?php elseif (count($images) != 1):?>
                        <div class="carousel-indicators">
                            <?php $i = 0; foreach ($images as $image) {
                                if ($i == 0) {
                                    print('<button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Img 1"></button>');
                                }
                                else {
                                    print('<button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="'.$i.'" aria-label="Img'.$i.'"></button>');
                                }
                                $i++;
                            } ?>
                        </div>
                        <div class="carousel-inner">
                            <?php $i = 1; foreach ($images as $image) {
                                if ($i == 1) {
                                    print('<div class="carousel-item active" data-bs-interval="10000">');
                                        print('<img src="product_img/'.$image['img'].'" class="img-fluid mx-auto d-block rounded" alt="'.$product[0]['name'].'">');
                                    print('</div>');
                                }
                                else {
                                    print('<div class="carousel-item" data-bs-interval="10000">');
                                        print('<img src="product_img/'.$image['img'].'" class="img-fluid mx-auto d-block rounded" alt="'.$product[0]['name'].'">');
                                    print('</div>');
                                }
                                $i++;
                            } ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif;?>              
                </div>
            </div>
        </div>
        <div class="card cbg mx-2 my-3">
            <div class="card-body px-3 py-3">
                <h2 class="fw-blod">Wird oft zusammen gekauft</h2>
                <div class="row row-cols-<?php if (isMobile()) print("1"); else print("3");?>">
                    <?php foreach ($products as $product1): ?>
                        <div class="col my-2">
                            <div class="card prodcard cbg2">
                                <a href="product.php?id=<?=$product1['id']?>" class="stretched-link">
                                    <div class="card-body">
                                        <?php if (empty($product1['image'])) {
                                            print('<img src="images/image-not-found.png" class="card-img-top rounded mb-3" alt="' . $product1['name'] . '">');
                                        } else {
                                            print('<img src="product_img/' . $product1['image'] . '" class="card-img-top rounded mb-3" alt="' . $product1['name'] . '">');
                                        }?>
                                        <h4 class="card-title name"><?=$product1['name']?></h4>
                                        <p class="card-text ctext price">Preis: 
                                            <?=$product1['price']?>&euro;
                                            <?php if ($product1['rrp'] > 0): ?>
                                            <span class="rrp ctext"><br>UVP: <?=$product1['rrp']?> &euro;</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card cbg mx-2">
            <div class="card-body px-3 py-3">
                <div class="row">
                    <div>
                        <h1 class="ctext"><?=$product[0]['name']?></h1>
                        <span class="ctext col">Preis: <?=$product[0]['price']?>&euro;</span> 
                        <?php if ($product[0]['rrp'] > 0): ?>
                            <span class="ctext col">UVP <?=$product[0]['rrp']?>&euro;</span>
                        <?php endif; ?>
                            <p class="ctext">Artikelnummer: <?=$product[0]['id']?></p>
                        <?php if ($product[0]['visible'] == 0):?>
                            <h2 class="text-danger my-2">Das Produkt aktuell nicht bestellbar!</h2>
                        <?php elseif ($product[0]['quantity'] >= 20):?>
                            <h2 class="text-success my-2">Auf Lager</h2>
                        <?php elseif ($product[0]['quantity'] > 5 && $product[0]['quantity'] < 20):?>
                            <h2 class="text-warning my-2">Nur noch <?=$product[0]['quantity']?> auf Lager!</h2>
                        <?php elseif ($product[0]['quantity'] <= 0):?>
                            <h2 class="text-danger my-2">Das Produkt ist ausverkauft!</h2>
                        <?php else: ?>
                            <h2 class="text-danger my-2">Nur noch <?=$product[0]['quantity']?> auf Lager!</h2>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($product[0]['visible'] == 1 && $product[0]['quantity'] > 0):?>
                <div class="row">
                    <div class="cart">
                        <form action="cart.php" method="post">
                            <div class="input-group">
                                <span class="input-group-text">Anzahl:</span>
                                <input type="number" value="<?=$product[0]['id']?>" name="productid" style="display: none;" required>
                                <input type="number" value="1" min="1" max="<?=$product[0]['quantity']?>" class="form-control" name="quantity" required>
                                <button class="btn btn-outline-primary" type="submit" name="action" value="add">Hinzufügen</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card cbg mx-2 my-3">
            <div class="card-body px-3 py-3">
                <div class="row">
                    <h2 class="fw-blod">Beschreibung</h2>
                    <p class="ctext mb-0"><?=$product[0]['desc']?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once("templates/footer.php")
?>
