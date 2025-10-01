<?php
require('config.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authenticated = false;

    // Simple Login
    if (AUTH_METHOD == 0) {
        if (!empty($_POST['pw']) && $_POST['pw'] === PASSWORD) {
            $authenticated = true;
        }
    }

    // LDAP Login
    elseif (AUTH_METHOD == 1) {
        if (!extension_loaded('ldap')) {
            die('Die LDAP-Erweiterung ist nicht installiert.');
        }

        $username   = $_POST['username'] ?? '';
        $ldap_pass  = $_POST['ldap_pw'] ?? '';

        if ($username !== '' && $ldap_pass !== '') {
            $ldap_conn = ldap_connect(LDAP_SERVER, LDAP_PORT);
            if ($ldap_conn) {
                ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

                if (@ldap_bind($ldap_conn, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
                    $ldap_filter = "(" . LDAP_FILTER_ATTRIBUTE . "=$username)";
                    $search = ldap_search($ldap_conn, LDAP_BIND_ADDRESS, $ldap_filter);
                    $entries = ldap_get_entries($ldap_conn, $search);

                    if ($entries['count'] > 0) {
                        $user_dn = $entries[0]['dn'];
                        if (@ldap_bind($ldap_conn, $user_dn, $ldap_pass)) {
                            $authenticated = true;
                        }
                    }
                }
                ldap_unbind($ldap_conn);
            }
        }
    }

    if ($authenticated) {
        $_SESSION['authenticated'] = true;
        header("Location: index.php");
        exit;
    } else {
        header("Location: login.php?error=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>FeMeSy Login</h1>

    <form method="post">
        <?php if (AUTH_METHOD == 0): ?>
            Passwort: <input type="password" name="pw"><br>
        <?php elseif (AUTH_METHOD == 1): ?>
            Benutzername: <input type="text" name="username"><br>
            Passwort: <input type="password" name="ldap_pw"><br>
        <?php endif; ?>
        <button type="submit">Anmelden</button>
    </form>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red">Login fehlgeschlagen!</p>
    <?php endif; ?>
</body>
</html>
