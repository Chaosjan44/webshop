<?php
require_once("php/mysql.php");

// überprüft ob der Benutzer angemeldet ist und gibt die Benutzerdaten zurück und sonst False oder leite auf die Login weiter
function check_user($redirect = TRUE) {
	global $pdo;
	if(!isset($_SESSION['userid']) && isset($_COOKIE['identifier']) && isset($_COOKIE['securitytoken'])) {
		$identifier = $_COOKIE['identifier'];
		$securitytoken = $_COOKIE['securitytoken'];
		$stmt = $pdo->prepare("SELECT * FROM securitytokens WHERE identifier = ?");
		$stmt->bindValue(1, $identifier);
		$result = $stmt->execute();
		if (!$result) {
			error('Database error', pdo_debugStrParams($stmt));
		}
		$securitytoken_row = $stmt->fetch();
		if(sha1($securitytoken) !== $securitytoken_row['securitytoken']) {
			//Vermutlich wurde der Security Token gestohlen
			header("location: /logout.php");
		} else { //Token war korrekt
			//Setze neuen Token
			$neuer_securitytoken = md5(uniqid());
			$stmt = $pdo->prepare("UPDATE securitytokens SET securitytoken = ? WHERE identifier = ?");
			$stmt->bindValue(1, sha1($neuer_securitytoken));
			$stmt->bindValue(2, $identifier);
			$result = $stmt->execute();
			if (!$result) {
				error('Database error', pdo_debugStrParams($stmt));
			}
			setcookie("identifier",$identifier,time()+(3600*24*90),'/'); //90 Tage Gültigkeit
			setcookie("securitytoken",$neuer_securitytoken,time()+(3600*24*90),'/'); //90 Tage Gültigkeit
			//Logge den Benutzer ein
			$_SESSION['userid'] = $securitytoken_row['user_id'];
		}
	}
	// gibt False zurück oder leitet auf die login weiter
	if(!isset($_SESSION['userid'])) {
		if($redirect) {
			header("location: login.php");
			exit();
		} else {
			return FALSE;
		}
	// ruft die Benutzerdaten ab und gibt das zurück 
	} else {
		$stmt = $pdo->prepare("SELECT * FROM permission_group, users WHERE users.permission_group = permission_group.id and users.id = ?");
		$stmt->bindValue(1, $_SESSION['userid'], PDO::PARAM_INT);
		$result = $stmt->execute();
		if (!$result) {
			error('Database error', pdo_debugStrParams($stmt));
		}
		$user = $stmt->fetch();
		return $user;
	}
}

// Fehler Seite anzeigen (wenn ein Fehler aufgetreten ist)
function error($error_msg, $error_log = "") {
	global $pdo;
	// Gibt eine detaillierte Fehlermeldung in das error_log
	$backtrace = debug_backtrace();
	if (!empty($error_log)) {
		error_log($backtrace[count($backtrace)-1]['file'] . ':' . $backtrace[count($backtrace)-1]['line'] . ': ' . $error_msg . ': ' . $error_log);
	} else {
		error_log($backtrace[count($backtrace)-1]['file'] . ':' . $backtrace[count($backtrace)-1]['line'] . ':' . $error_msg);
	}
	include_once("templates/header.php");
	include_once("templates/error.php");
	include_once("templates/footer.php");
	exit();
}

// Überprüft ob das verwendete Gerät ein Handy ist
function isMobile () {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// fängt die Debug Ausgabe ab und gibt das zurück
function pdo_debugStrParams($stmt) {
	ob_start();
	$stmt->debugDumpParams();
	$r = ob_get_contents();
	ob_end_clean();
	return $r;
}

// Überprüft die Cookies welches Design verwendet werden soll
function check_style() {
	if(isset($_COOKIE['style'])) {
		if ($_COOKIE['style'] == 'dark') {
			return 'dark';
		} else if ($_COOKIE['style'] == 'light') {
			return 'light';
		}
	} else {
		return 'light';
	}
}