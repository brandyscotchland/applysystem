<?php
/* Zum Fehler anzeigen # entfernen */
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

session_start();
require __DIR__ . '/config/config.php';

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Wenn der Benutzer nicht eingeloggt ist, zeige das Login-Formular
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        // Prüfe das Passwort
        if (password_verify($_POST['password'], ADMIN_PASSWORD)) {
            $_SESSION['logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = "Falsches Passwort.";
        }
    }

    // Login-Formular anzeigen
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .login-container {
                background-color: white;
                padding: 2rem;
                border-radius: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
                max-width: 400px;
                width: 100%;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2 class="mb-4 text-center">Admin Login</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Falls der Benutzer eingeloggt ist, zeige die Admin-Seite
$design_file = __DIR__ . '/design_settings.json';
$field_file = __DIR__ . '/fields.json';

// Design-Einstellungen laden
if (file_exists($design_file)) {
    $design_settings = json_decode(file_get_contents($design_file), true);
} else {
    $design_settings = [
        'theme' => 'light',
        'text_color' => '#000000',
        'font_family' => 'Arial, sans-serif',
        'input_background_color' => '#ffffff',
        'input_border_color' => '#ced4da',
        'input_placeholder_color' => '#6c757d',
        'button_color' => '#007bff',
        'button_text_color' => '#ffffff',
        'navbar_color' => '#007bff',
        'navbar_text_color' => '#ffffff',
        'container_width' => '600px'
    ];
}

// Formularfelder laden
if (file_exists($field_file)) {
    $fields = json_decode(file_get_contents($field_file), true);
} else {
    $fields = [];
}

// Formularfeld hinzufügen oder aktualisieren
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_field') {
        $unique_name = 'field_' . bin2hex(random_bytes(4));
        $new_field = [
            "field_name" => $_POST['field_name'],
            "field_type" => $_POST['field_type'],
            "unique_name" => $unique_name,
            "placeholder" => $_POST['placeholder'] ?? '',
            "default_value" => $_POST['default_value'] ?? '',
            "required" => isset($_POST['required']) ? true : false
        ];
        if ($_POST['field_type'] == 'select') {
            $new_field['field_options'] = explode(',', $_POST['field_options']);
        }
        $fields[] = $new_field;
        file_put_contents($field_file, json_encode($fields, JSON_PRETTY_PRINT));
    } elseif ($_POST['action'] === 'delete') {
        array_splice($fields, $_POST['index'], 1);
        file_put_contents($field_file, json_encode($fields, JSON_PRETTY_PRINT));
    } elseif ($_POST['action'] === 'move_up') {
        if ($_POST['index'] > 0) {
            $tmp = $fields[$_POST['index']];
            $fields[$_POST['index']] = $fields[$_POST['index'] - 1];
            $fields[$_POST['index'] - 1] = $tmp;
            file_put_contents($field_file, json_encode($fields, JSON_PRETTY_PRINT));
        }
    } elseif ($_POST['action'] === 'move_down') {
        if ($_POST['index'] < count($fields) - 1) {
            $tmp = $fields[$_POST['index']];
            $fields[$_POST['index']] = $fields[$_POST['index'] + 1];
            $fields[$_POST['index'] + 1] = $tmp;
            file_put_contents($field_file, json_encode($fields, JSON_PRETTY_PRINT));
        }
    } elseif ($_POST['action'] === 'update_design') {
        $design_settings = [
            'theme' => $_POST['theme'],
            'text_color' => $_POST['text_color'],
            'font_family' => $_POST['font_family'],
            'input_background_color' => $_POST['input_background_color'],
            'input_border_color' => $_POST['input_border_color'],
            'input_placeholder_color' => $_POST['input_placeholder_color'],
            'button_color' => $_POST['button_color'],
            'button_text_color' => $_POST['button_text_color'],
            'navbar_color' => $_POST['navbar_color'],
            'navbar_text_color' => $_POST['navbar_text_color'],
            'container_width' => $_POST['container_width']
        ];
        file_put_contents($design_file, json_encode($design_settings, JSON_PRETTY_PRINT));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Bewerbungsformular</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem;
        }
        .admin-container {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .field-list {
            margin-bottom: 2rem;
        }
        .field-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .field-actions {
            display: flex;
            gap: 0.5rem;
        }
        .footer {
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .navbar {
            background-color: <?= htmlspecialchars($design_settings['navbar_color']) ?>;
        }
        .navbar-brand,
        .nav-link {
            color: <?= htmlspecialchars($design_settings['navbar_text_color']) ?> !important;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><?= htmlspecialchars(SERVER_NAME) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars(WEBSITE_URL) ?>" target="_blank">Hauptseite</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apply.php" target="_blank">Apply</a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Design-Einstellungen -->
    <div class="container admin-container">
        <h4>Design-Einstellungen</h4>
        <form method="POST">
            <input type="hidden" name="action" value="update_design">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="theme" class="form-label">Theme</label>
                    <select id="theme" name="theme" class="form-select">
                        <option value="light" <?= $design_settings['theme'] == 'light' ? 'selected' : '' ?>>Hell</option>
                        <option value="dark" <?= $design_settings['theme'] == 'dark' ? 'selected' : '' ?>>Dunkel</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="text_color" class="form-label">Textfarbe</label>
                    <input type="color" id="text_color" name="text_color" class="form-control" value="<?= htmlspecialchars($design_settings['text_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="font_family" class="form-label">Schriftart</label>
                    <input type="text" id="font_family" name="font_family" class="form-control" value="<?= htmlspecialchars($design_settings['font_family']) ?>">
                    <small class="form-text text-muted">Geben Sie eine Google Font an (z.B. 'Roboto').</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="input_background_color" class="form-label">Eingabefeld-Hintergrundfarbe</label>
                    <input type="color" id="input_background_color" name="input_background_color" class="form-control" value="<?= htmlspecialchars($design_settings['input_background_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="input_border_color" class="form-label">Eingabefeld-Rahmenfarbe</label>
                    <input type="color" id="input_border_color" name="input_border_color" class="form-control" value="<?= htmlspecialchars($design_settings['input_border_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="input_placeholder_color" class="form-label">Eingabefeld-Platzhalterfarbe</label>
                    <input type="color" id="input_placeholder_color" name="input_placeholder_color" class="form-control" value="<?= htmlspecialchars($design_settings['input_placeholder_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="button_color" class="form-label">Button-Farbe</label>
                    <input type="color" id="button_color" name="button_color" class="form-control" value="<?= htmlspecialchars($design_settings['button_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="button_text_color" class="form-label">Button-Textfarbe</label>
                    <input type="color" id="button_text_color" name="button_text_color" class="form-control" value="<?= htmlspecialchars($design_settings['button_text_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="navbar_color" class="form-label">Navbar-Farbe</label>
                    <input type="color" id="navbar_color" name="navbar_color" class="form-control" value="<?= htmlspecialchars($design_settings['navbar_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="navbar_text_color" class="form-label">Navbar-Textfarbe</label>
                    <input type="color" id="navbar_text_color" name="navbar_text_color" class="form-control" value="<?= htmlspecialchars($design_settings['navbar_text_color']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="container_width" class="form-label">Container-Breite</label>
                    <input type="text" id="container_width" name="container_width" class="form-control" value="<?= htmlspecialchars($design_settings['container_width']) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Design speichern</button>
        </form>
    </div>

    <!-- Formularfelder verwalten -->
    <div class="container admin-container">
        <h4>Formularfelder verwalten</h4>
        <form method="POST">
            <input type="hidden" name="action" value="add_field">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="field_name" class="form-label">Feldname</label>
                    <input type="text" id="field_name" name="field_name" class="form-control" required>
                    <small class="form-text text-muted">Der Name, der als Label für das Feld angezeigt wird.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="field_type" class="form-label">Feldtyp</label>
                    <select id="field_type" name="field_type" class="form-select" required>
                        <option value="text">Text</option>
                        <option value="email">E-Mail</option>
                        <option value="number">Nummer</option>
                        <option value="tel">Telefon</option>
                        <option value="url">URL</option>
                        <option value="date">Datum</option>
                        <option value="textarea">Textbereich</option>
                        <option value="select">Dropdown</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                    <small class="form-text text-muted">Wählen Sie den Typ des Eingabefeldes aus.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="placeholder" class="form-label">Platzhalter</label>
                    <input type="text" id="placeholder" name="placeholder" class="form-control">
                    <small class="form-text text-muted">Ein Text, der im Feld angezeigt wird, bevor der Benutzer etwas eingibt. (Optional)</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="default_value" class="form-label">Standardwert</label>
                    <input type="text" id="default_value" name="default_value" class="form-control">
                    <small class="form-text text-muted">Der voreingestellte Wert, der im Feld angezeigt wird. (Optional)</small>
                </div>
                <div class="col-md-12 mb-3" id="field-options-container" style="display: none;">
                    <label for="field_options" class="form-label">Optionen (für Dropdown, durch Komma getrennt)</label>
                    <input type="text" id="field_options" name="field_options" class="form-control">
                    <small class="form-text text-muted">Optionen für Dropdown-Felder, getrennt durch Kommas. (Nur für Dropdowns)</small>
                </div>
                <div class="col-md-12 mb-3 form-check">
                    <input type="checkbox" id="required" name="required" class="form-check-input">
                    <label for="required" class="form-check-label">Pflichtfeld</label>
                    <small class="form-text text-muted">Markieren Sie dies, wenn das Feld obligatorisch ausgefüllt werden muss.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Feld hinzufügen</button>
        </form>
        <br>
        <hr>
        <br>

        <h4>Aktuelle Formularfelder</h4>
        <div class="field-list">
            <?php foreach ($fields as $index => $field): ?>
                <div class="field-item">
                    <div>
                        <strong><?= htmlspecialchars($field['field_name']) ?></strong> (<?= htmlspecialchars($field['field_type']) ?>)
                    </div>
                    <div class="field-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="index" value="<?= $index ?>">
                            <input type="hidden" name="action" value="move_up">
                            <button type="submit" class="btn btn-secondary btn-sm">↑</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="index" value="<?= $index ?>">
                            <input type="hidden" name="action" value="move_down">
                            <button type="submit" class="btn btn-secondary btn-sm">↓</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="index" value="<?= $index ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="footer">
        &copy; <?= htmlspecialchars(SERVER_NAME) ?> <?= date('Y') ?>
    </footer>

    <script>
        document.getElementById('field_type').addEventListener('change', function() {
            const fieldOptionsContainer = document.getElementById('field-options-container');
            const placeholderInput = document.getElementById('placeholder');
            const defaultValueInput = document.getElementById('default_value');
            if (this.value === 'select') {
                fieldOptionsContainer.style.display = 'block';
                placeholderInput.disabled = true;
                defaultValueInput.disabled = true;
            } else if (this.value === 'checkbox') {
                placeholderInput.disabled = true;
                defaultValueInput.disabled = true;
                fieldOptionsContainer.style.display = 'none';
            } else {
                placeholderInput.disabled = false;
                defaultValueInput.disabled = false;
                fieldOptionsContainer.style.display = 'none';
            }
        });
    </script>
</body>
</html>
