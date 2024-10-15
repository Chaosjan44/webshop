/*
 * Cookie code von:
 * https://github.com/Wruczek/Bootstrap-Cookie-Alert
 */
(function() {
    var cookieAlert = document.querySelector(".cookiealert");
    var acceptCookies = document.querySelector(".acceptcookies");

    if (!cookieAlert) {
        return;
    }

    cookieAlert.offsetHeight; // Force browser to trigger reflow (https://stackoverflow.com/a/39451131)

    // Zeigt das Popup wenn der Cookie "acceptCookies" nicht gefunden wird
    if (!getCookie("acceptCookies")) {
        cookieAlert.classList.add("show");
    }

    // Wenn den Cookies zu gestimmt wird, wird ein Cookie für 1 Jahr erstellt und das Popup geschlossen
    acceptCookies.addEventListener("click", function() {
        setCookie("acceptCookies", true, 365);
        cookieAlert.classList.remove("show");

        // des Event wird gelöscht nach dem Zustimmen gelöscht
        window.dispatchEvent(new Event("cookieAlertAccept"))
    });
})();