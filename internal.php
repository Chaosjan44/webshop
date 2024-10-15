<?php
require_once("php/functions.php");
$user = require_once("templates/header.php");
// Überprüfe, dass der User eingeloggt ist
// Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
if (!isset($user['id'])) {
    require_once("login.php");
    exit;
}
// Wenn der unser die (Admin) Berechtigungen hat die Bestellungen zu sehen
if ($user['showOrders'] == 1) {
	// Frage alle Bestellungen sortiert nach Bestelldatum ab
	$stmt = $pdo->prepare('SELECT *, COUNT(product_list.id) as products FROM product_list, users, orders where product_list.list_id = orders.id AND orders.kunden_id = users.id AND ordered = 1 and sent = 0 group by orders.id order by orders.ordered_date; ');
	$result = $stmt->execute();
	if (!$result) {
		error('Datenbank Fehler!', pdo_debugStrParams($stmt));
	}
	$total_orders = $stmt->rowCount();
	$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Frage alle Bestellungen die ein user Getätigt hat ab
$stmt = $pdo->prepare('SELECT *, COUNT(product_list.id) as products FROM product_list, orders where product_list.list_id = orders.id AND orders.kunden_id = ? AND ordered = 1 group by orders.id; ');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Datenbank Fehler!', pdo_debugStrParams($stmt));
}
$total_orders1 = $stmt->rowCount();
$orders1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container minheight100 py-3 px-3">
	<div class="row no-gutter">
		<div class="card cbg ctext my-3 mx-auto">
			<div class="card-body text-center">
				<h1 class="card-title name">Herzlich Willkommen!</h1>
				<span class="card-text">
					Hallo <?=$user['vorname']?>,<br>
					Herzlich Willkommen im internen Bereich!<br>
				</span>
				<div class="card-text">
					<a href="logout.php"><button type="button" class="btn btn-outline-primary mx-2 my-2">Abmelden</button></a>
					<a href="settings.php"><button type="button" class="btn btn-outline-primary mx-2 my-2">Einstellungen</button></a>
				</div>
			</div>
		</div>
		<?php if ($user['showUser'] == 1 or $user['showUserPerms'] == 1 or $user['showCategories'] == 1 or $user['showProduct'] == 1) { ?>
		<div class="card cbg ctext my-3 mx-auto">
			<div class="card-body text-center">
				<h1 class="card-title">Adminbereich</h1>
				<div class="card-text">
					<!-- Butons nur mit benötigten Berechtigungen anzeigen -->
					<?php
						if ($user['showUser'] == 1) {
							print('<a href="admin/user.php"><button class="btn btn-outline-primary mx-2 my-2" type="button">Benutzer</button></a>');
						} 
						if ($user['showUserPerms'] == 1) {
							print('<a href="admin/perms.php"><button class="btn btn-outline-primary mx-2 my-2" type="button">Berechtigungen</button></a>');
						}
						if ($user['showCategories'] == 1) {
							print('<a href="admin/categories.php"><button class="btn btn-outline-primary mx-2 my-2" type="button">Kategorien</button></a>');
						}
						if ($user['showProduct'] == 1) {
							print('<a href="admin/product.php"><button class="btn btn-outline-primary mx-2 my-2" type="button">Produkte</button></a>');
						}
					?>
				</div>
			</div>
		</div>
		<?php } ?>
		<?php if ($user['showOrders'] == 1) { ?>
			<div class="card cbg ctext my-3 mx-auto">
				<div class="card-body text-center">
					<h1 class="card-title">Offene Bestellungen</h1>
					<div class="card-text">
						<p><?=$total_orders?> Bestellung<?=($total_orders==1 ? '':'en')?></p>
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<div class="ctext rounded">
											<th scope="col" class="border-0">
												<div class="p-2 px-3 text-uppercase ctext">#</div>
											</th>
											<th scope="col" class="border-0 text-center">
												<div class="p-2 px-3 text-uppercase ctext">Name</div>
											</th>
											<th scope="col" class="border-0 text-center">
												<div class="p-2 px-3 text-uppercase ctext">Bestelldatum</div>
											</th>
											<th scope="col" class="border-0 text-center">
												<div class="p-2 px-3 text-uppercase ctext">Produkte</div>
											</th>
										</div>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $order): ?>
										<tr>
											<td class="border-0 align-middle text-center ctext">
												<span><a href="/admin/order.php?id=<?=$order['id']?>"><?=$order['id']?></a></span>
											</td>
											<td class="border-0 align-middle text-center ctext">
												<span><?=$order['vorname']?> <?=$order['nachname']?></span>
											</td>
											<td class="border-0 align-middle text-center ctext">
												<span><?=date('d.m.Y', strtotime($order['ordered_date']))?></span>
											</td>
											<td class="border-0 align-middle text-center ctext">
												<span><?=$order['products']?></span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>         
					</div>
				</div>
			</div>
		<?php } ?>
		<div class="card cbg ctext my-3 mx-auto">
			<div class="card-body text-center">
				<h1 class="card-title">Getätigte Bestellungen</h1>
				<div class="card-text">
					<p><?=$total_orders1?> Bestellung<?=($total_orders1==1 ? '':'en')?></p>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<div class="ctext rounded">
										<th scope="col" class="border-0">
											<div class="p-2 px-3 text-uppercase ctext">#</div>
										</th>
										<th scope="col" class="border-0 text-center">
											<div class="p-2 px-3 text-uppercase ctext">Bestelldatum</div>
										</th>
										<th scope="col" class="border-0 text-center">
											<div class="p-2 px-3 text-uppercase ctext">Versanddatum</div>
										</th>
										<th scope="col" class="border-0 text-center">
											<div class="p-2 px-3 text-uppercase ctext">Produkte</div>
										</th>
									</div>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($orders1 as $order): ?>
									<tr>
										<td class="border-0 align-middle text-center ctext">
											<span><a href="/order.php?id=<?=$order['id']?>"><?=$order['id']?></a></span>
										</td>
										<td class="border-0 align-middle text-center ctext">
											<span><?=date('d.m.Y', strtotime($order['ordered_date']))?></span>
										</td>
										<td class="border-0 align-middle text-center ctext">
											<span><?=($order['sent']==1 ? date('d.m.Y', strtotime($order['sent_date'])):'-')?></span>
										</td>
										<td class="border-0 align-middle text-center ctext">
											<span><?=$order['products']?></span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>         
				</div>
			</div>
		</div>
	</div>
</div>
<?php 
include_once("templates/footer.php")
?>
