<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $server_name = $_POST['server_name'];
    $website_url = $_POST['website_url'];
    $api_key = $_POST['api_key'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Überprüfen, ob die Passwörter übereinstimmen
    if ($password !== $confirm_password) {
        $error_message = "Passwörter stimmen nicht überein!";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error_message = "Das Passwort erfüllt nicht die Sicherheitsanforderungen!";
    } else {
        // Passwort hashen und in der config.php speichern
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Konfigurationsdatei erstellen
        $config_content = "<?php\n";
        $config_content .= "define('SERVER_NAME', '" . addslashes($server_name) . "');\n";
        $config_content .= "define('WEBSITE_URL', '" . addslashes($website_url) . "');\n";
        $config_content .= "define('API_KEY', '" . addslashes($api_key) . "');\n";
        $config_content .= "define('ADMIN_PASSWORD', '" . addslashes($hashed_password) . "');\n";
        file_put_contents(__DIR__ . '/../config/config.php', $config_content);

        // Installationsverzeichnis löschen
        function deleteDirectory($dir) {
            if (!file_exists($dir)) return true;
            if (!is_dir($dir) || is_link($dir)) return unlink($dir);
            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') continue;
                if (!deleteDirectory($dir . "/" . $item)) {
                    chmod($dir . "/" . $item, 0777);
                    if (!deleteDirectory($dir . "/" . $item)) return false;
                };
            }
            return rmdir($dir);
        }
        deleteDirectory(__DIR__);

        // Weiterleitung zur Admin-Seite
        header("Location: ../admin.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation - Minecraft-Server Bewerbungsformular</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .install-container {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        .password-requirements {
            font-size: 0.9em;
            color: #6c757d;
        }
        .password-requirements li {
            margin-bottom: 0.2rem;
        }
        .password-requirements .valid {
            color: #28a745;
        }
        .password-requirements .invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h2 class="mb-4 text-center">Installation</h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="server_name" class="form-label">Servername</label>
                <input type="text" id="server_name" name="server_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="website_url" class="form-label">Webseitenlink der Hauptwebseite</label>
                <input type="url" id="website_url" name="website_url" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="api_key" class="form-label">API-Schlüssel</label>
                <input type="text" id="api_key" name="api_key" class="form-control" required>
                <small class="form-text text-muted">
                    Wenn du noch keinen API-Schlüssel hast, erstelle einen auf <a href="https://minetools.de/dashboard.php" target="_blank">https://minetools.de/dashboard.php</a>.
                </small>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Admin-Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <ul class="password-requirements">
                    <li id="length" class="invalid">Mindestens 8 Zeichen</li>
                    <li id="uppercase" class="invalid">Mindestens ein Großbuchstabe</li>
                    <li id="lowercase" class="invalid">Mindestens ein Kleinbuchstabe</li>
                    <li id="number" class="invalid">Mindestens eine Zahl</li>
                    <li id="special" class="invalid">Mindestens ein Sonderzeichen</li>
                </ul>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <div id="password-match" class="invalid">Passwörter stimmen nicht überein</div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Installation abschließen</button>
        </form>
    </div>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const length = document.getElementById('length');
        const uppercase = document.getElementById('uppercase');
        const lowercase = document.getElementById('lowercase');
        const number = document.getElementById('number');
        const special = document.getElementById('special');
        const passwordMatch = document.getElementById('password-match');

        password.addEventListener('input', () => {
            const value = password.value;
            length.classList.toggle('valid', value.length >= 8);
            length.classList.toggle('invalid', value.length < 8);

            uppercase.classList.toggle('valid', /[A-Z]/.test(value));
            uppercase.classList.toggle('invalid', !/[A-Z]/.test(value));

            lowercase.classList.toggle('valid', /[a-z]/.test(value));
            lowercase.classList.toggle('invalid', !/[a-z]/.test(value));

            number.classList.toggle('valid', /\d/.test(value));
            number.classList.toggle('invalid', !/\d/.test(value));

            special.classList.toggle('valid', /[\W_]/.test(value));
            special.classList.toggle('invalid', !/[\W_]/.test(value));

            checkPasswordMatch();
        });

        confirmPassword.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const match = password.value === confirmPassword.value;
            passwordMatch.classList.toggle('valid', match);
            passwordMatch.classList.toggle('invalid', !match);
            passwordMatch.textContent = match ? 'Passwörter stimmen überein' : 'Passwörter stimmen nicht überein';
        }
    </script>
</body>
</html>
