// Triggert das neues setzten des Themes
setStyle();
// Initialisiert die Variablen
var pressedc = false;
var pressedr = false;
// fügt ein Event für eine gedrückte Taste hinzu
// und setzt die Variable auf true
document.onkeydown = function (e) {
  if (e['key'] == 'c') {
    console.log('pressed');
    pressedc = true;
  }
  if (e['key'] == 'r') {
    pressedr = true;
  }
};
// fügt ein Event für eine losgelassene Taste hinzu 
// und setzt die Variable auf false
document.onkeyup = function (e) {
  if (e['key'] == 'c') {
    console.log('not pressed');
    pressedc = false;
  }
  if (e['key'] == 'r') {
    pressedr = false;
  }
};
// wird aufgerufen wenn der Switch im Footer betätigt wird
// es wird ein Cookie für das gewählte theme gesetzt
function toggleStyle() {
  if (getCookie("style") == "light") {
    setCookie("style", "dark", 365);
  } else {
    setCookie("style", "light", 365);
  }
  if (pressedc) {
    setCookie("style", "custom", 365);
  }
  if (pressedr) {
    setCookie("style", "rainbow", 365);
  }
  setStyle();
}
// Deaktiviert und Aktiviert die entsprechenden Stylesheets
function setStyle() {
  // Überprüft den Cookie und stzt den style
  switch (getCookie("style")) {
    case ("custom"):
      // Setzt/Aktualisiert den Cookie
      setCookie("style", "custom", 365);
      // Überprüft ob das Stylesheet bereits vorhanden ist
      if (document.querySelectorAll("link[href='/css/custom.css']").length > 0) {
        // Aktiviert das Stylesheet
        document.querySelectorAll("link[href='/css/custom.css']")[0].disabled = false;
      } else {
        // Függt das Stylesheet zum Header hinzu
        var head = document.getElementsByTagName('head')[0];
        var style = document.createElement('link');
        style.href = '/css/custom.css';
        style.type = 'text/css';
        style.rel = 'stylesheet';
        head.append(style);
      }
      // Deaktiviert die Übrigen Stylesheets
      document.querySelectorAll("link[href='/css/dark.css']")[0].disabled = true;
      document.querySelectorAll("link[href='/css/light.css']")[0].disabled = true;
      // überprüft ob das Stylesheet existiert
      if (document.querySelectorAll("link[href='/css/rainbow.css']").length > 0) {
        // Deaktiviert das Stylesheet
        document.querySelectorAll("link[href='/css/rainbow.css']")[0].disabled = true;
      }
      break;
    case ("rainbow"):
      setCookie("style", "rainbow", 365);
      if (document.querySelectorAll("link[href='/css/rainbow.css']").length > 0) {
        document.querySelectorAll("link[href='/css/rainbow.css']")[0].disabled = false;
      } else {
        var head = document.getElementsByTagName('head')[0];
        var style = document.createElement('link');
        style.href = '/css/rainbow.css';
        style.type = 'text/css';
        style.rel = 'stylesheet';
        head.append(style);
      }
      document.querySelectorAll("link[href='/css/dark.css']")[0].disabled = true;
      document.querySelectorAll("link[href='/css/light.css']")[0].disabled = true;
      if (document.querySelectorAll("link[href='/css/custom.css']").length > 0) {
        document.querySelectorAll("link[href='/css/custom.css']")[0].disabled = true;
      }
      break;
    case ("dark"):
      setCookie("style", "dark", 365);
      document.querySelectorAll("link[href='/css/dark.css']")[0].disabled = false;
      document.querySelectorAll("link[href='/css/light.css']")[0].disabled = true;
      if (document.querySelectorAll("link[href='/css/custom.css']").length > 0) {
        document.querySelectorAll("link[href='/css/custom.css']")[0].disabled = true;
      }
      if (document.querySelectorAll("link[href='/css/rainbow.css']").length > 0) {
        document.querySelectorAll("link[href='/css/rainbow.css']")[0].disabled = true;
      }
      break;
    default:
      setCookie("style", "light", 365);
      document.querySelectorAll("link[href='/css/dark.css']")[0].disabled = true;
      document.querySelectorAll("link[href='/css/light.css']")[0].disabled = false;
      if (document.querySelectorAll("link[href='/css/custom.css']").length > 0) {
        document.querySelectorAll("link[href='/css/custom.css']")[0].disabled = true;
      }
      if (document.querySelectorAll("link[href='/css/rainbow.css']").length > 0) {
        document.querySelectorAll("link[href='/css/rainbow.css']")[0].disabled = true;
      }
      break;
  }
}
// setzt ein Cookie
function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
// gibt den Cookie-Content
function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
// Zeigt die hochzuladende Bilde live an
function showPreview(event){
	var files = event.target.files;
	var preview = document.getElementById('preview');
	preview.innerHTML = '';
	for (var i = 0, f; f = files[i]; i++) { 
		preview.innerHTML += ['<div class="col"><div class="card prodcard bg-dark"><img src="', URL.createObjectURL(f), '" class="card-img-top img-fluid rounded" title="', escape(f.name), '" alt="', escape(f.name), '"></div></div>'].join('');
	}
}
