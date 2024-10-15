<?php 
require_once("php/functions.php");
$user = require_once("templates/header.php");
?>

<?php
//Variable ob das Registrierungsformular angezeigt werden soll
$showFormular = true;
 
// Wenn register gesetzt ist
if(isset($_GET['register'])) {
	// Setzen und befüllen der Variablen
	$error = false;
	$vorname = trim($_POST['vorname']);
	$nachname = trim($_POST['nachname']);
	$email = trim($_POST['email']);
	$passwort = $_POST['passwort'];
	$passwort2 = $_POST['passwort2'];
	
	// Vorname, Nachname und E-Mail werden nach Ausfüllung überprüft
	if(empty($vorname) || empty($nachname) || empty($email)) {
		echo 'Bitte alle Felder ausfüllen<br>';
		$error = true;
	}

	// es wird überprüft ob die E-Mail wirklich gültig ist
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo 'Bitte eine gültige E-Mail-Adresse eingeben<br>';
		$error = true;
	} 	

	// Es wird überprüft ob das Passwort Feld gefüllt ist
	if(strlen($passwort) == 0) {
		echo 'Bitte ein Passwort angeben<br>';
		$error = true;
	}

	// Überprüft ob die 2 eingegebenen Passwörter überein Stimmen
	if($passwort != $passwort2) {
		echo 'Die Passwörter müssen übereinstimmen<br>';
		$error = true;
	}
	
	// Überprüfe, dass die E-Mail-Adresse noch nicht registriert wurde
	if(!$error) { 
		$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
		$stmt->bindValue(1, $email);
		$result = $stmt->execute();
		if (!$result) {
			error('Datenbank Fehler!', pdo_debugStrParams($stmt));
		}
		$user = $stmt->fetch();
		
		if($user !== false) {
			echo 'Diese E-Mail-Adresse ist bereits vergeben<br>';
			$error = true;
		}	
	}
	
	// Überprüfe, ob die DSGVO akzeptiert wurde
	if(!$error) { 
		if(!(isset($_POST['dsgvo']))) {
			echo 'Sie müssen die Datenschutzerklärung akzeptieren!<br>';
			$error = true;
		}	
	}
	
	// Überprüfe, ob die AGBs akzeptiert wurden
	if(!$error) { 
		if(!(isset($_POST['agb']))) {
			echo 'Sie müssen die AGBs akzeptieren!<br>';
			$error = true;
		}	
	}
	// Keine Fehler, wir können den Nutzer registrieren
	if(!$error) {	
		// Hashen des Passworts
		$passwort_hash = password_hash($passwort, PASSWORD_DEFAULT);
		
		// Insert des Users in die Datenbank
		$stmt = $pdo->prepare("INSERT INTO users (email, passwort, vorname, nachname) VALUES (?, ?, ?, ?)");
		$stmt->bindValue(1, $email);
		$stmt->bindValue(2, $passwort_hash);
		$stmt->bindValue(3, $vorname);
		$stmt->bindValue(4, $nachname);
		$result = $stmt->execute();
		if (!$result) {
			error('Datenbank Fehler!', pdo_debugStrParams($stmt));
		}
		
		// Warenkorb wird für den User erstellt
		$stmt = $pdo->prepare("INSERT INTO `orders` (`kunden_id`, `ordered`, `sent`) VALUES ((select id from users where email = ?), '0', '0')");
		$stmt->bindValue(1, $email);
		$result = $stmt->execute();
		if (!$result) {
			error('Datenbank Fehler!', pdo_debugStrParams($stmt));
		}
		

		// Wenn das einfügen erfolgreich war wird eine Erfolgsmeldung angezeigt
		if($result) {
			$showFormular = false;
			?>
			<div class="container minheight100">
				<div class="row">
					<div class="col-lg-10 col-xl-7 mx-auto my-5 py-3 px-5 text-center rounded cbg">
						<h1 class="text-success">REGISTRIERUNG ERFOLGREICH<i class="fa-solid fa-check"></i></h1>
						<p class="ctext">
						Du wirst automatisch in 5 Sekunden zum Login geleitet, solltest du nicht weitergeleitet werden klicke <a href="login.php">hier</a>.
						
						</p>
					</div>
				</div>
			</div>
			<meta http-equiv="refresh" content="5;url=login.php">
		<?php
		}
	} 
}
if($showFormular) {
?>

<div class="container-fluid minheight100">
	<div class="row no-gutter">
		<div class="ctext">
			<div class="register-register d-flex align-items-center py-5">
				<div class="container">
					<div class="row">
						<div class="col-lg-10 col-xl-7 mx-auto cbg rounded">
							<h3 class="display-4 ctext">Registrierung</h3>

							<?php 
							if(isset($error_msg) && !empty($error_msg)) {
								echo $error_msg;
							}
							?>
							<p class="ctext mb-4">Herzlich Willkommen!</p>

							<form action="?register=1" method="post">
								<div class="form-floating mb-3">
									<input placeholder="Max" type="text" value="<?=(isset($_POST["vorname"]) ? $_POST["vorname"]:'')?>" id="inputVorname" size="40" maxlength="250" name="vorname" class="form-control border-0 px-4 text-dark fw-bold" required>
									<label for="inputVorname" class="text-dark fw-bold">Vorname</label>
								</div>
								<div class="form-floating mb-3">
									<input placeholder="Mustermann" type="text" value="<?=(isset($_POST["nachname"]) ? $_POST["nachname"]:'')?>" id="inputNachname" size="40" maxlength="250" name="nachname" class="form-control border-0 px-4 text-dark fw-bold" required>
									<label for="inputNachname" class="text-dark fw-bold">Nachname</label>
								</div>
								<div class="form-floating mb-3">
									<input placeholder="max@mustermann.de" type="email" value="<?=(isset($_POST["email"]) ? $_POST["email"]:'')?>" id="inputEmail" size="40" maxlength="250" name="email" class="form-control border-0 px-4 text-dark fw-bold" required>
									<label for="inputEmail" class="text-dark fw-bold">E-Mail</label>
								</div>
								<div class="form-floating mb-3">
									<input placeholder="Passwort" type="password" value="<?=(isset($_POST["passwort"]) ? $_POST["passwort"]:'')?>" id="inputPasswort" size="40"  maxlength="250" name="passwort" class="form-control border-0 px-4 text-dark fw-bold" required>
									<label for="inputPasswort" class="text-dark fw-bold">Dein Passwort</label>
								</div>
								<div class="form-floating mb-3">
									<input placeholder="Passwort wiederholen" type="password" id="inputPasswort2" size="40" maxlength="250" name="passwort2" class="form-control border-0 px-4 text-dark fw-bold" required>
									<label for="inputPasswort2" class="text-dark fw-bold">Passwort wiederholen</label>
								</div>

								<div class="custom-control custom-checkbox mb-3">
									<input type="checkbox" id="customCheck1" name="dsgvo" value="gelesen" class="custom-control-input" required> 
									<label for="customCheck1" class="custom-control-label ctext">Ich habe die <a href="dsgvo.php">Datenschutzerklärung</a> gelesen und akzeptiere diese.</label>
								</div>
								<div class="custom-control custom-checkbox mb-3">
									<input type="checkbox" id="customCheck2" name="agb" value="gelesen" class="custom-control-input" required> 
									<label for="customCheck2" class="custom-control-label ctext"> Ich habe die <a href="agb.php">AGBs</a> gelesen und akzeptiere diese.</label>		
								</div>
								<button type="submit" class="btn btn-outline-primary btn-block text-uppercase mb-2 shadow-sm">Registrieren</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
 
<?php
} include_once("templates/footer.php")
?>
