<?php
// fügt die php-Funktionen hinzu
require_once("php/functions.php");
// fügt den header hinzu und liest die Benutzer-Infos hinzu
$user = require_once("templates/header.php");
//Überprüfe, dass der User eingeloggt ist und bindet evtl. die login.php ein
if (!isset($user['id'])) {
    require_once("login.php");
    exit;
}
// Überprüft ob ein Feld gespeichert werden soll
if(isset($_GET['save'])) {
	$save = $_GET['save'];
	// Speichert die Persönlichen Daten
	if($save == 'personal_data') {
		$vorname = trim($_POST['vorname']);
		$nachname = trim($_POST['nachname']);
		
		if($vorname == "" || $nachname == "") {
			$error_msg = "Bitte Vor- und Nachname ausfüllen.";
		} else {
			$stmt = $pdo->prepare("UPDATE users SET vorname = ?, nachname = ?, updated_at=NOW() WHERE id = ?");
			$stmt->bindValue(1, $vorname);
			$stmt->bindValue(2, $nachname);
			$stmt->bindValue(3, $user['id'], PDO::PARAM_INT);
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}
			$user['vorname'] = $vorname;
			$user['nachname'] = $nachname;

			echo("<script>location.href='settings.php'</script>");
		}
	// Speichert die E-Mail
	// erfordert ein Password da die E-Mail in anderen Anwendungen zum zurücksetzen des Password genutzt werden könnte
	} else if($save == 'email') {
		$passwort = $_POST['passwort'];
		$email = trim($_POST['email']);
		$email2 = trim($_POST['email2']);
		
		if($email != $email2) {
			$error_msg = "Die eingegebenen E-Mail-Adressen stimmten nicht überein.";
		} else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_msg = "Bitte eine gültige E-Mail-Adresse eingeben.";
		} else if(!password_verify($passwort, $user['passwort'])) {
			$error_msg = "Bitte korrektes Passwort eingeben.";
		} else {
			$stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
			$stmt->bindValue(1, $email);
			$stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}
			$user['email'] = $email;
			
			echo("<script>location.href='settings.php'</script>");
		}
	// Speiche das Password
	} else if($save == 'passwort') {
		$passwortAlt = $_POST['passwortAlt'];
		$passwortNeu = trim($_POST['passwortNeu']);
		$passwortNeu2 = trim($_POST['passwortNeu2']);
		
		if($passwortNeu != $passwortNeu2) {
			$error_msg = "Die eingegebenen Passwörter stimmten nicht überein.";
		} else if($passwortNeu == "") {
			$error_msg = "Das Passwort darf nicht leer sein.";
		} else if(!password_verify($passwortAlt, $user['passwort'])) {
			$error_msg = "Bitte korrektes Passwort eingeben.";
		} else {
			$passwort_hash = password_hash($passwortNeu, PASSWORD_DEFAULT);
				
			$stmt = $pdo->prepare("UPDATE users SET passwort = ? WHERE id = ?");
			$stmt->bindValue(1, $passwort_hash);
			$stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}
			echo("<script>location.href='settings.php'</script>");
		}
	// Speichert die Standard Aderessen
	} else if($save == 'address') {
		if(isset($_POST['standardaddresse']) && !empty($_POST['standardaddresse'])) {
			$stmt = $pdo->prepare("UPDATE `address` SET `default` = 0, updated_at=NOW() WHERE `default` = 1 and user_id = ?");
			$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}
			$stmt = $pdo->prepare("UPDATE `address` SET `default` = 1, updated_at=NOW() WHERE id = ? and user_id = ?");
			$stmt->bindValue(1, $_POST['standardaddresse'], PDO::PARAM_INT);
			$stmt->bindValue(2, $user['id'], PDO::PARAM_INT);
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}

			echo("<script>location.href='settings.php'</script>");
		} else {
			$error_msg = "Bitte Addresse auswählen.";
		}
	}
}
// Fragt die Aderessen für die Dropdown Menu
$stmt = $pdo->prepare('SELECT * FROM `citys`, `address` where address.citys_id = citys.id and user_id = ?');
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$result = $stmt->execute();
if (!$result) {
    error('Database error', pdo_debugStrParams($stmt));
}
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container minheight100 py-2 px-2">
	<div class="row no-gutter">
		<?php if(isset($error_msg) && !empty($error_msg)) {echo $error_msg;}?>
		<!-- Desktop Design -->
		<?php if (!isMobile()): ?>
			<!-- Persönliche Daten Card -->
			<div class="card cbg ctext my-2 mx-auto">
				<div class="card-body text-center">
					<h1 class="card-title">Persönliche Daten</h1>
					<div class="card-text">
						<div class="row justify-content-between">
							<!-- Name -->
							<div class="cvl col-6">
								<h3 class="ctext">Name</h3>
								<form action="?save=personal_data" method="post">
									<div class="form-floating mb-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputVorname" placeholder="Vorname" name="vorname" type="text" value="<?=$user['vorname']?>" required>
										<label class="text-dark fw-bold" for="inputVorname">Vorname</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputNachname" placeholder="Nachname" name="nachname" type="text" value="<?=$user['nachname']?>" required>
										<label class="text-dark fw-bold" for="inputNachname">Nachname</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
							<!-- Adresse -->
							<div class="col-6">
								<h3 class="ctext">Adresse</h3>
									<button class="btn btn-primary mb-2" type="button" onclick="window.location.href = '/address.php';">Bearbeiten</button>
									<form action="?save=address" method="post">
									<div class="form-floating mb-2">
										<select class="form-select border-0 ps-4 text-dark fw-bold" id="inputStandardaddresse" name="standardaddresse">
											<!-- Fügt alle Aderessen in die Dropdown hinzu -->
											<?php foreach ($addresses as $address): ?>
												<?php if ($address['default'] == 1): ?>
													<option class="text-dark" value="<?=$address['id']?>" selected><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
												<?php else:?>
													<option class="text-dark" value="<?=$address['id']?>" ><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
												<?php endif; ?>
											<?php endforeach; ?>
										</select>
										<label class="text-dark fw-bold" for="inputStandardaddresse">Standard Adresse</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- E-Mail und Password Card -->
			<div class="card cbg ctext my-2 mx-auto">
				<div class="card-body text-center">
					<h1 class="card-title">Sicherheit</h1>
					<div class="card-text">
						<div class="row justify-content-between">
							<div class="cvl col-6">
								<!-- E-Mail -->
								<h3 class="ctext">E-Mail-Adresse</h3>
								<form action="?save=email" method="post">
									<div class="form-floating mb-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswort" placeholder="Passwort" name="passwort" type="password" required>
										<label class="text-dark fw-bold" for="inputPasswort">Passwort</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputEmail" placeholder="E-Mail" name="email" type="email" value="<?=$user['email']?>" required>
										<label class="text-dark fw-bold" for="inputEmail">E-Mail</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputEmail2" placeholder="E-Mail wiederholen" name="email2" type="email" required>
										<label class="text-dark fw-bold" for="inputEmail2">E-Mail wiederholen</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
							<!-- Passwort -->
							<div class="col-6">
								<h3 class="ctext">Passwort</h3>
								<form>
									<div class="form-floating mb-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswort" placeholder="Altes Passwort" name="passwortAlt" type="password" required>
										<label class="text-dark fw-bold" for="inputPasswort">Altes Passwort</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswortNeu" placeholder="Neues Passwort" name="passwortNeu" type="password" required>
										<label class="text-dark fw-bold" for="inputPasswortNeu">Neues Passwort</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswortNeu2" placeholder="Neues Passwort wiederholen" name="passwortNeu2" type="password"  required>
										<label class="text-dark fw-bold" for="inputPasswortNeu2">Neues Passwort wiederholen</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		<!-- Mobile Design -->
		<?php else: ?>
			<!-- Persönliche Daten Card -->
			<div class="card cbg ctext my-2 mx-auto">
				<div class="card-body text-center">
					<h1 class="card-title">Persönliche Daten</h1>
					<div class="card-text">
						<div class="row justify-content-between row-cols-1">
							<!-- Name -->
							<div class="col my-3">
								<h3 class="ctext">Name</h3>
								<form action="?save=personal_data" method="post">
									<div class="form-floating mb-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputVorname" placeholder="Vorname" name="vorname" type="text" value="<?=$user['vorname']?>" required>
										<label class="text-dark fw-bold" for="inputVorname">Vorname</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputNachname" placeholder="Nachname" name="nachname" type="text" value="<?=$user['nachname']?>" required>
										<label class="text-dark fw-bold" for="inputNachname">Nachname</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
							<hr class="hr-light">
							<!-- Adresse -->
							<div class="col my-3">
								<h3 class="ctext">Adresse</h3>
									<button class="btn btn-primary mb-2" type="button" onclick="window.location.href = '/address.php';">Bearbeiten</button>
									<form action="?save=address" method="post">
									<div class="form-floating mb-2">
										<select class="form-select border-0 ps-4 text-dark fw-bold" id="inputStandardaddresse" name="standardaddresse">
											<?php foreach ($addresses as $address): ?>
												<?php if ($address['default'] == 1): ?>
													<option class="text-dark" value="<?=$address['id']?>" selected><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
												<?php else:?>
													<option class="text-dark" value="<?=$address['id']?>" ><?=$address['street']?> <?=$address['number']?> - <?=$address['PLZ']?>, <?=$address['city']?></option>
												<?php endif; ?>
											<?php endforeach; ?>
										</select>
										<label class="text-dark fw-bold" for="inputStandardaddresse">Standard Adresse</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- E-Mail und Password Card -->
			<div class="card cbg ctext my-2 mx-auto">
				<div class="card-body text-center">
					<h1 class="card-title">Sicherheit</h1>
					<div class="card-text">
						<div class="row justify-content-between row-cols-1">
							<div class="col my-3">
								<!-- E-Mail -->
								<h3 class="ctext">E-Mail-Adresse</h3>
								<form action="?save=email" method="post">
									<div class="form-floating mb-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswort" placeholder="Passwort" name="passwort" type="password" required>
										<label class="text-dark fw-bold" for="inputPasswort">Passwort</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputEmail" placeholder="E-Mail" name="email" type="email" value="<?=$user['email']?>" required>
										<label class="text-dark fw-bold" for="inputEmail">E-Mail</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputEmail2" placeholder="E-Mail wiederholen" name="email2" type="email" required>
										<label class="text-dark fw-bold" for="inputEmail2">E-Mail wiederholen</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
							<hr class="hr-light">
							<!-- Passwort -->
							<div class="col my-3">
								<h3 class="ctext">Passwort</h3>
								<form>
									<div class="form-floating mb-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputOldPasswort" placeholder="Altes Passwort" name="passwortAlt" type="password" required>
										<label class="text-dark fw-bold" for="inputOldPasswort">Altes Passwort</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswortNeu" placeholder="Neues Passwort" name="passwortNeu" type="password" required>
										<label class="text-dark fw-bold" for="inputPasswortNeu">Neues Passwort</label>
									</div>
									<div class="form-floating my-2">
										<input class="form-control border-0 ps-4 text-dark fw-bold" id="inputPasswortNeu2" placeholder="Neues Passwort wiederholen" name="passwortNeu2" type="password"  required>
										<label class="text-dark fw-bold" for="inputPasswortNeu2">Neues Passwort wiederholen</label>
									</div>
									<button class="btn btn-outline-primary" type="submit">Speichern</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php 
// Bindet den Footer ein
include_once("templates/footer.php")
?>
