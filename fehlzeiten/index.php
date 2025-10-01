<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('config.php');
require('mail_functions.php');
require('auth.php'); // Session-Check
define('MAX_FILESIZE', 500000);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>FeMeSy - Fehlzeitenmeldung</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggle(source) {
            let checkboxes = document.querySelectorAll('input[type="checkbox"]');
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i] !== source)
                    checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body>
<header>
    <div class="header-left">
        <?php echo HEADER_IMAGE; ?>
        <div class="header-text">
            <?php echo HEADER_TEXT; ?>
        </div>
    </div>

    <form method="post" action="logout.php">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</header>

<main>
    <h2>Fehlzeitenmeldung</h2>

<?php
if (!isset($_POST['stage'])) {
    // Upload-Formular
    ?>
    <form method="post" enctype="multipart/form-data">
        <label>CSV-Datei hochladen:</label>
        <input type="file" name="fileToUpload" required>
        <button type="submit" name="submit">Fehlzeitenmeldung starten</button>
        <input type="hidden" name="stage" value="0">
    </form>
    <div class="info-box">
        <strong>Ausbilder-Email-Liste:</strong><br>
        Aktuelle Datei:
        <?php
        if (file_exists(AUSBILDER_FILE)) {
            echo date("d.m.Y H:i:s ", filemtime(AUSBILDER_FILE)) . ", " . filesize(AUSBILDER_FILE) . " bytes";
        } else {
            echo "<span class='error-msg'>FEHLT!</span>";
        }
        ?>
        <br>Neue Datei wird automatisch erzeugt, wenn die ASV-WebUntis-Datei abgelegt wird.
        <br>Test-Mail senden: <a href="test_email.php">hier</a>
    </div>
    <?php
} else {
    switch ($_POST['stage']) {
        case 0:
            // Upload-Verarbeitung
            $target_dir = UPLOAD_DIR;
            $target_file = $target_dir . basename($_FILES['fileToUpload']['name']);

            if ($_FILES['fileToUpload']['size'] > MAX_FILESIZE || $_FILES['fileToUpload']['size'] == 0) {
                die('<p class="error-msg">Datei ungültig (zu groß oder leer).</p>');
            }

            move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file);

            // Bash-Script aufrufen und direkt die Tabelle ausgeben
            system(ABSENCE_SHELL_SCRIPT . " '$target_file' u " . AUSBILDER_FILE . " " . SENT_DB);

            // Hidden Input für Mailversand
            echo "<input type='hidden' name='file' value='$target_file'>";
            echo "<input type='hidden' name='stage' value='1'>";
            break;

        case 1:
            // Mailversand
            $sent_db = SENT_DB;
            if (!isset($_POST['file'])) {
                echo "<p class='error-msg'>Keine Datei übergeben!</p>";
                break;
            }

            $target_file = $_POST['file'];
            echo "<div class='info-box'><strong>Prüfen Sie die Entschuldigungen</strong></div>";
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

                $vonbis = ($von == $bis) ?
                    "hat am $datum in der $von. Stunde" :
                    "hat am $datum von der $von. bis zur $bis. Stunde";

                $message = sprintf(MAIL_TEXT, $name, $klasse, $vonbis);

                if ($send) {
                    $result = send_mail($ausbilder, $message, $cc);
                    if (strpos($result, 'ok') !== false) {
                        file_put_contents($sent_db, "$datum-$id\n", FILE_APPEND | LOCK_EX);
                    } else {
                        echo "<p class='error-msg'>Fehler beim Senden an $ausbilder: $result</p>";
                    }
                }
            }

            echo "<p>Fehlzeitenmeldung abgeschlossen.<br><a href='index.php'>Zur Startseite</a></p>";
            break;
    }
}
?>
</main>
</body>
</html>
