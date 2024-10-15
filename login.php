<?php 
// Bindet die php-Funktionen ein
require_once("php/functions.php");
// Initialisiert error_msg
$error_msg = "";
// Überprüft ob das Formular bereits bestätigt wurde
if(isset($_POST['email']) && isset($_POST['passwort'])) {
	$email = $_POST['email'];
	$passwort = $_POST['passwort'];
	// Fragt den Benutzer mit der angegebenen E-Mail in der Datenbank ab
	$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
	$stmt->bindValue(1, $email);
	$result = $stmt->execute();
	if (!$result) {
		error('Database error', pdo_debugStrParams($stmt));
	}
	$user = $stmt->fetch();
	//Überprüfung des Passworts
	if ($user !== false && password_verify($passwort, $user['passwort'])) {
		$_SESSION['userid'] = $user['id'];
		//Möchte der Nutzer angemeldet beleiben?
		if(isset($_POST['angemeldet_bleiben'])) {
			$identifier = md5(uniqid());
			$securitytoken = md5(uniqid());
			// Setzt security tokens in der Datenbank und in den Cookies
			$stmt = $pdo->prepare("INSERT INTO securitytokens (user_id, identifier, securitytoken) VALUES (?, ?, ?)");
			$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
			$stmt->bindValue(2, $identifier);
			$stmt->bindValue(3, sha1($securitytoken));
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}
			setcookie("identifier",$identifier,time()+(3600*24*365)); //Valid for 1 year
			setcookie("securitytoken",$securitytoken,time()+(3600*24*365)); //Valid for 1 year
		}
		// leitet bei Erfolgreicher Anmeldung auf die internal.php weiter
		echo("<script>location.href='internal.php'</script>");
		exit;
	} else {
		// Setzt den Fehler in die Variable
		$error_msg =  "E-Mail oder Passwort war ungültig<br><br>";
	}

}
// setzt die Email in eine Variable um das Formular vor auszufüllen
$email_value = "";
if(isset($_POST['email'])) {
	$email_value = htmlentities($_POST['email']); 
}
// Fügt den header hinzu und liest die evtl. die Benutzer Infos ein
$user = require_once("templates/header.php");
if (isset($user['id'])) {
    echo("<script>location.href='internal.php'</script>");
    exit;
}
?>
<div class="container-fluid">
	<div class="row no-gutter">
		<div class="ctext">
			<div class="minheight100 d-flex align-items-center py-5">
				<div class="container">
					<div class="row">
						<div class="col-lg-10 col-xl-7 mx-auto cbg rounded">
							<h3 class="display-4 ">Anmelden</h3>
							<!-- Zeigt eine Error-nachricht an, wenn es einen Fehler gibt -->
							<?php 
							// Gibt evtl. die Fehlermeldung aus
							if(isset($error_msg) && !empty($error_msg)) {
								echo $error_msg;
							}
							?>
							<p class="text-muted mb-4">Schön, dass du wieder da bist!</p>
							<!-- Login-Input boxen für E-Mail, passwort und ide Abfrage ob man angemeldet bleiben möchte
							Die Auswahl wird dann als Cookie gespeichert -->
							<form action="/login.php" method="post">
								<div class="form-floating mb-3">
									<input id="inputEmail" type="email" name="email" placeholder="E-Mail" value="<?php echo $email_value; ?>" autofocus class="form-control border-0 ps-4 text-dark fw-bold" required>
									<label for="inputEmail" class="text-dark fw-bold">E-Mail</label>
								</div>
								<div class="form-floating mb-3">
                                    <input id="inputPassword" type="password" name="passwort" placeholder="Passwort" class="form-control border-0 ps-4 text-dark fw-bold" required>
									<label for="inputPassword" class="text-dark fw-bold">Passwort</label>
								</div>

								<div class="custom-control custom-checkbox mb-3">
									<input value="remember-me" id="customCheck1" type="checkbox" name="angemeldet_bleiben" value="1" checked class="custom-control-input">
									<label for="customCheck1" class="custom-control-label">Angemeldet bleiben</label>
								</div>
								
								<button type="submit" class="btn btn-primary btn-block text-uppercase mb-2 shadow-sm">Anmelden</button>
								<div class="text-center d-flex justify-content-between mt-4 "><p>Noch kein Kunde? <a href="register.php" class="font-italic text-muted"> 
									<u>Registrieren</u></a></p>
								</div>
							</form>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php 
// fügt den Footer hinzu
include_once("templates/footer.php")
?>
