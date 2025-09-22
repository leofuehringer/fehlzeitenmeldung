<?php

require('config.php');
require('mail_functions.php');

define('MAX_FILESIZE', 500000);

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

// Überprüfen, ob die LDAP-Erweiterung installiert ist, wenn LDAP verwendet wird
if (AUTH_METHOD == 1 && !extension_loaded('ldap')) {
    die('Die LDAP-Erweiterung ist nicht installiert oder nicht aktiviert. Bitte installiere und aktiviere die LDAP-Erweiterung.');
}

// Session-Start für Authentifizierung
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fehlzeitenmeldung</title>
    <meta charset='utf-8'>
    <!-- Externes CSS einbinden -->
    <link rel="stylesheet" type="text/css" href="style.css">
    <script language="JavaScript">
        function toggle(source) {
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i] != source)
                    checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body>
    <div>
        <div id="header-logo-image">
            <?php echo HEADER_IMAGE; ?>
        </div>
        <div id="header-text" class="">
            <?php echo HEADER_TEXT; ?>
        </div>
    </div>
    <div style="width: 100%; clear: both; margin-left: 130px;">
        <h1 style="width: 100%; clear: both; color: #89c9eb; text-shadow: 2px 2px 4px #444; font-weight: bold">FeMeSy</h1>
        <b>Online Fehlzeiten-Melde-System</b><br /><br />

        <?php
        if (!isset($_SESSION['authenticated'])) {
            if (!isset($_POST['pw'])) {
                ?>
                <form method="post">
                    <p>Bitte melden Sie sich an:</p>
                    <?php if (AUTH_METHOD == 0): ?>
                        <input type="hidden" name="loginType" value="simple">
                        Passwort: <input type="password" name="pw" /><br />
                    <?php elseif (AUTH_METHOD == 1): ?>
                        <input type="hidden" name="loginType" value="ldap">
                        Benutzername: <input type="text" name="username" /><br />
                        Passwort: <input type="password" name="ldap_pw" /><br />
                    <?php else: ?>
                        <p>Ungültige Authentifizierungsmethode konfiguriert.</p>
                    <?php endif; ?>

                    <input type="submit" name="submit" value="Anmelden" />
                    <?php if (isset($_GET['error']) && $_GET['error'] == 1) echo '<br /><span style="color: red">Falsches Passwort oder Benutzername.</span>'; ?>
                </form>
                <?php
            } else {
                $authenticated = false;

                if ($_POST['loginType'] === 'simple') {
                    // Einfache Passwort-Authentifizierung
                    if ($_POST['pw'] == PASSWORD) {
                        $authenticated = true;
                    }
                } elseif ($_POST['loginType'] === 'ldap' && isset($_POST['username']) && isset($_POST['ldap_pw'])) {
                    // LDAPS-Authentifizierung
                    $username = $_POST['username'];
                    $ldap_pass = $_POST['ldap_pw'];

                    $ldap_conn = ldap_connect(LDAP_SERVER, LDAP_PORT);

                    if (!$ldap_conn) {
                        error_log("LDAP Verbindung fehlgeschlagen: " . ldap_error($ldap_conn));
                        echo "LDAP Verbindung fehlgeschlagen";
                        exit;
                    }

                    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

                    // Bind-DN und Passwort für die LDAP-Verbindung
                    $bind_dn = LDAP_BIND_DN;
                    $bind_password = LDAP_BIND_PASSWORD;

                    // Bind als Administrator oder privilegiertes Konto
                    $ldap_bind = ldap_bind($ldap_conn, $bind_dn, $bind_password);

                    if (!$ldap_bind) {
                        error_log("LDAP Bind fehlgeschlagen: " . ldap_error($ldap_conn));
                        echo "LDAP Bind fehlgeschlagen";
                        ldap_unbind($ldap_conn);
                        exit;
                    }

                    // Dynamischer DN basierend auf LDAP_BIND_ADDRESS und Benutzername
                    $ldap_filter = "(" . LDAP_FILTER_ATTRIBUTE . "=$username)";
                    $search = ldap_search($ldap_conn, LDAP_BIND_ADDRESS, $ldap_filter);
                    $entries = ldap_get_entries($ldap_conn, $search);

                    if ($entries['count'] == 0) {
                        error_log("Benutzer nicht gefunden in der BIND-ADresse.");
                        echo "Benutzer nicht gefunden in der BIND-ADresse.";
                        ldap_unbind($ldap_conn);
                        exit;
                    }

                    $user_dn = $entries[0]['dn'];

                    // Bind als Benutzer
                    $ldap_bnd = @ldap_bind($ldap_conn, $user_dn, $ldap_pass);

                    if (!$ldap_bnd) {
                        error_log("LDAP Benutzer-Bind fehlgeschlagen: " . ldap_error($ldap_conn) . " (Code: " . ldap_errno($ldap_conn) . ")");
                        echo "LDAP Benutzer-Bind fehlgeschlagen";
                        ldap_unbind($ldap_conn);
                        exit;
                    }

                    $authenticated = true;

                    ldap_unbind($ldap_conn);
                }

                if ($authenticated) {
                    // Setze eine Session-Variable, um den Authentifizierungsstatus zu speichern
                    $_SESSION['authenticated'] = true;

                    // Weiterleitung zur nächsten Seite
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    // Zurück zum Anmeldeformular mit Fehlermeldung
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?error=1');
                    exit;
                }
            }
        } else {
            // Hier kommt der Rest des Codes, der nur für authentifizierte Benutzer sichtbar ist
            // Beispielsweise die Datei hochladen und Fehlzeiten melden
            $ausbilder = AUSBILDER_DB;
            if (!isset($_POST['stage'])) {
                ?>
                <form method="post" enctype="multipart/form-data">
                    <table>
                        <tr></tr>
                        <tr>
                            <td>CSV-Datei hochladen:</td>
                            <td><input type="file" name="fileToUpload" id="fileToUpload"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" value="Fehlzeitenmeldung starten..." name="submit"></td>
                        </tr>
                    </table>
                    <input type="hidden" name="stage" value="0" />
                </form>
                <form method="post" enctype="multipart/form-data" style="background: #bbb; width: 80%; margin-top: 50px;">
                    <h5>Ausbilder-Email-Liste</h5>
                    Aktuelle Ausbilder-Datei: Stand
                    <?php
                    $filename = AUSBILDER_DB;
                    setlocale(LC_ALL, 'de_DE');
                    if (file_exists($ausbilder)) {
                        echo " " . date("d.m.Y H:i:s ", filemtime($ausbilder)) . ", " . filesize($ausbilder) . " bytes";
                    } else {
                        echo " <span style='color: red'>FEHLT!</span>";
                    }
                    ?>
                    <br />neue Ausbilder-Email-Datei wird <b>automatisch erzeugt</b>, wenn die ASV-WebUntis-Datei auf dem Verwaltungsserver abgelegt wird!
                    <br /><br />
                    Um die Email-Einstellungen zu testen können sie <a href="test_email.php" target="_blank" style="color: blue; text-decoration: underline; text-transform: lowercase;">hier</a> eine Testemail verschicken.
                </form>
                <?php
            } else {
                switch ($_POST['stage']) {
                    case 0:
                        $target_dir = UPLOAD_DIR;
                        $target_file = $target_dir . basename($_FILES['fileToUpload']['name']);
                        $uploadOk = 1;
                        $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
                        if (isset($_REQUEST['file'])) {
                            header_text(true);
                            $target_file = $_REQUEST['file'];
                            system(ABSENCE_SHELL_SCRIPT . " '$target_file' e");
                        } elseif (isset($_POST['submit'])) {
                            $uploadOK = 1;
                            if ($_FILES['fileToUpload']['size'] > MAX_FILESIZE) {
                                die('Datei zu groß?!');
                                $uploadOk = false;
                            }
                            if ($_FILES['fileToUpload']['size'] == 0) {
                                $uploadOk = false;
                            }
                            if ($uploadOk) {
                                move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file);
                                header_text(false);
                                system(ABSENCE_SHELL_SCRIPT . " '$target_file' u");
                                echo "<input type='hidden' name='file' value='$target_file' />";
                            } else {
                                die('Upload hat nicht funktioniert?! Eingabedatei prüfen oder möglicher Server-Fehler.');
                                $uploadOk = 0;
                            }
                        }
                        echo "<button type='submit' />Absenden</button> <input type='checkbox' name='cc' checked='checked' value='1'> ";
                        echo "Kopie an " . MAIL_FROM . " senden</input>";
                        echo "<br /><b>Legende:</b> <span style='color: #008000'>&#10004;</span> neben dem Häkchen für die Email bedeutet, ";
                        echo "dass eine Fehlzeitenmeldung für den Schüler <br />für diesen Tag bereits versandt worden ist. Bitte dann ";
                        echo "keine weitere Fehlzeitenmeldung veranlassen.";
                        echo "<input type='hidden' name='stage' value='1' />";
                        break;
                    case 1:
                        $sent_db = '/var/www/sent.db';
                        if (isset($_POST['file'])) {
                            $target_file = $_POST['file'];
                            echo "<h3>Wichtig:</h3>";
                            echo "<form method='post'>";
                            echo "<input type='hidden' name='file' value='$target_file' />";
                            echo "<input type='hidden' name='stage' value='0' />";
                            echo "Nächster Schritt: <button type='submit'>prüfen</button>, ob die als entschuldigt gemeldeten</a> stimmen.";
                            echo "</form>";
                        }
                        echo "<h3>Mailversand:</h3>";
                        for ($i = 0; $i < count($_POST['name']); $i++) {
                            $name = $_POST['name'][$i];
                            $klasse = $_POST['klasse'][$i];
                            $von = $_POST['von'][$i];
                            $bis = $_POST['bis'][$i];
                            $ausbilder = $_POST['ausbilder'][$i];
                            $id = $_POST['id'][$i];
                            $datum = $_POST['datum'][$i];
                            $send = isset($_POST['send'][$id]);
                            $cc = isset($_POST['cc']);
                            if ($von == $bis) {
                                $vonbis = "hat am $datum in der $von. Stunde in der Heinrich-";
                            } else {
                                $vonbis = "hat am $datum von der $von. Stunde bis zur $bis. Stunde in der Heinrich-";
                            }
                            $message = sprintf(MAIL_TEXT, $name, $klasse, $vonbis);
                            if ($send) {
                                $result = send_mail($ausbilder, $message, $cc);
                                if (strpos($result, 'ok') !== false) {
                                    file_put_contents($sent_db, "$datum-$id\n", FILE_APPEND | LOCK_EX);
                                } else {
                                    echo "<br />Fehler beim Senden der E-Mail an $ausbilder: $result";
                                }
                            }
                        }
                        if (!isset($_POST['file'])) {
                            echo "<br />Fehlzeitenmeldung abgeschlossen.<br />Zurück <a href='/' ";
                            echo "style='text-decoration: underline; font-weight: bold; color: #202090'>zur Startseite</a>";
                        }
                        break;
                }
            }
        }

        function header_text($e)
        {
            echo '<form method="post"><input type="hidden" name="stage" value="2" />';
            if ($e) {
                echo "<h1>Gemeldet als entschuldigt</h1>";
                echo "Prüfen, ob Entschuldigung vorliegt und diese dem Betrieb bekannt ist";
            } else {
                echo '<h1>Unentschuldigt</h1>';
            }
            echo '<table><tr><th>Name</th><th>Klasse</th><th>Text</th><th>Entschuldigungstext</th>';
            echo '<th>von</th><th>bis</th><th>senden <input type="checkbox" onclick="toggle(this);" ';
            if (!$e) {
                echo 'checked="checked" ';
            }
            echo ' />';
            echo '</th><th>Ausbilder-Email</th></tr>';
        }

        // Logout-Button hinzufügen
        if (isset($_SESSION['authenticated'])) {
            echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?logout=true" style="display:inline;">';
            echo '<input type="submit" value="Logout" />';
            echo '</form>';
        }
        ?>
    </div>
</body>
</html>

<?php
// Logout-Logik
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_start();
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
