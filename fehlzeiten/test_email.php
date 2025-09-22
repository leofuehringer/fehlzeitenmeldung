<?php
// Überprüfen, ob die Konfiguration bereits geladen wurde
if (!defined('CONFIG_LOADED')) {
    require('config.php');
    define('CONFIG_LOADED', true);
}

// Einbindung der send_mail Funktion aus mail_functions.php
require('mail_functions.php');

// Funktion zum Anzeigen des Test-Email-Formulars
function show_test_email_form() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test-Email-Versand</title>
        <meta charset='utf-8'>
        <script>
            function sendTestEmail() {
                var testEmail = document.getElementById('testEmail').value;
                var testSubject = document.getElementById('testSubject').value;
                var testMessage = document.getElementById('testMessage').value;

                fetch('test_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'testEmail=' + encodeURIComponent(testEmail) +
                          '&testSubject=' + encodeURIComponent(testSubject) +
                          '&testMessage=' + encodeURIComponent(testMessage)
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    alert('Test-Email wurde versendet: ' + data);
                })
                .catch(error => {
                    console.error('Fehler beim Senden der Test-Email:', error);
                    alert('Fehler beim Senden der Test-Email. Siehe Console für Details.');
                });
            }
        </script>
    </head>
    <body>
        <h1>Test-Email-Versand</h1>
        <form onsubmit="event.preventDefault(); sendTestEmail();">
            <label for="testEmail">Empfänger-Email:</label><br>
            <input type="email" id="testEmail" name="testEmail" required><br><br>

            <label for="testSubject">Betreff:</label><br>
            <input type="text" id="testSubject" name="testSubject" value="Test-Email von Fehlzeitenmeldungssystem" required><br><br>

            <label for="testMessage">Nachricht:</label><br>
            <textarea id="testMessage" name="testMessage" rows="10" cols="50" required>Dies ist eine Test-Email vom Fehlzeitenmeldungssystem. Bitte ignorieren.</textarea><br><br>

            <input type="submit" value="Test-Email senden">
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Hauptlogik für die Test-Email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['testEmail'];
    $testSubject = $_POST['testSubject'];
    $testMessage = $_POST['testMessage'];

    // Versuche, die Test-Email zu senden
    $result = send_mail($testEmail, $testMessage, false);

    if (strpos($result, 'ok') !== false) {
        echo "Test-Email erfolgreich versendet an: " . $testEmail;
    } else {
        echo "Fehler beim Versenden der Test-Email: " . $result;
    }

    // Hier können wir die SMTP-Logs ausgeben, wenn vorhanden
    global $smtp_log;
    if (!empty($smtp_log)) {
        echo "<h3>SMTP Debug-Ausgaben:</h3>";
        echo "<pre>" . htmlspecialchars($smtp_log) . "</pre>";
    }
} else {
    show_test_email_form();
}
?>
