<?php
/* Zum Fehler anzeigen # entfernen */
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

require __DIR__ . '/config/config.php';

// Design-Einstellungen laden
$design_settings = json_decode(file_get_contents('design_settings.json'), true);

$file_path = 'applications.json';

if (!file_exists($file_path)) {
    file_put_contents($file_path, '[]');
}

$form_submitted = false; // Variable, um den Status der Formulareinreichung zu verfolgen

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = json_decode(file_get_contents('fields.json'), true);

    $application = [];
    foreach ($fields as $field) {
        $unique_name = $field['unique_name'];
        if ($field['field_type'] == 'checkbox') {
            $application[$field['field_name']] = isset($_POST[$unique_name]) ? 'Ja' : 'Nein';
        } else {
            $application[$field['field_name']] = $_POST[$unique_name] ?? '';
        }
    }

    $applications = json_decode(file_get_contents($file_path), true);
    $applications[] = $application;
    file_put_contents($file_path, json_encode($applications, JSON_PRETTY_PRINT));

    $email_subject = 'Neue Bewerbung eingegangen';
    $email_body = "<h2>Neue Bewerbung eingegangen</h2>";
    foreach ($application as $key => $value) {
        $email_body .= "<p><strong>{$key}:</strong> " . htmlspecialchars($value) . "</p>";
    }

    $email_api_url = "https://minetools.de/email_api.php";
    $email_data = [
        'api_key' => API_KEY,
        'subject' => $email_subject,
        'message' => $email_body
    ];

    $ch = curl_init($email_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $form_submitted = true; // Formular erfolgreich abgeschickt
    } else {
        echo "Bewerbung erfolgreich eingereicht, aber E-Mail konnte nicht versendet werden.";
    }
}

$fields = json_decode(file_get_contents('fields.json'), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bewerbungsformular</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($design_settings['font_family']) ?>&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: <?= htmlspecialchars($design_settings['theme'] === 'dark' ? '#121212' : '#f8f9fa') ?>;
            color: <?= htmlspecialchars($design_settings['text_color']) ?>;
            font-family: '<?= htmlspecialchars($design_settings['font_family']) ?>', sans-serif;
            padding: 2rem;
        }
        .application-container {
            background-color: <?= htmlspecialchars($design_settings['theme'] === 'dark' ? '#1e1e1e' : '#ffffff') ?>;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            max-width: <?= htmlspecialchars($design_settings['container_width']) ?>;
            margin: 0 auto;
        }
        .form-control {
            background-color: <?= htmlspecialchars($design_settings['input_background_color']) ?>;
            border-color: <?= htmlspecialchars($design_settings['input_border_color']) ?>;
            color: <?= htmlspecialchars($design_settings['text_color']) ?>;
        }
        .form-control::placeholder {
            color: <?= htmlspecialchars($design_settings['input_placeholder_color']) ?>;
        }
        .btn-primary {
            background-color: <?= htmlspecialchars($design_settings['button_color']) ?>;
            color: <?= htmlspecialchars($design_settings['button_text_color']) ?>;
            border-color: <?= htmlspecialchars($design_settings['button_color']) ?>;
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
                </ul>
            </div>
        </div>
    </nav>
	<br>
    <!-- Bewerbungsformular -->
    <div class="application-container">
        <?php if ($form_submitted): ?>
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Bewerbung erfolgreich abgeschickt!</h4>
                <p>Vielen Dank für deine Bewerbung. Unser Team wird sich in Kürze bei dir melden.</p>
            </div>
        <?php else: ?>
            <h2 class="mb-4"><?= htmlspecialchars(SERVER_NAME) ?> - Bewerbungsformular</h2>
            <form method="POST">
                <?php foreach ($fields as $field): ?>
                    <div class="mb-3">
                        <label for="<?= htmlspecialchars($field['unique_name']) ?>" class="form-label"><?= htmlspecialchars($field['field_name']) ?></label>
                        <?php if (in_array($field['field_type'], ['text', 'email', 'number', 'tel', 'url', 'date'])): ?>
                            <input type="<?= htmlspecialchars($field['field_type']) ?>" id="<?= htmlspecialchars($field['unique_name']) ?>" name="<?= htmlspecialchars($field['unique_name']) ?>" class="form-control" placeholder="<?= htmlspecialchars($field['placeholder']) ?>" value="<?= htmlspecialchars($field['default_value']) ?>" <?= $field['required'] ? 'required' : '' ?>>
                        <?php elseif ($field['field_type'] == 'textarea'): ?>
                            <textarea id="<?= htmlspecialchars($field['unique_name']) ?>" name="<?= htmlspecialchars($field['unique_name']) ?>" class="form-control" placeholder="<?= htmlspecialchars($field['placeholder']) ?>" <?= $field['required'] ? 'required' : '' ?>><?= htmlspecialchars($field['default_value']) ?></textarea>
                        <?php elseif ($field['field_type'] == 'select'): ?>
                            <select id="<?= htmlspecialchars($field['unique_name']) ?>" name="<?= htmlspecialchars($field['unique_name']) ?>" class="form-select" <?= $field['required'] ? 'required' : '' ?>>
                                <?php foreach ($field['field_options'] as $option): ?>
                                    <option value="<?= htmlspecialchars(trim($option)) ?>"><?= htmlspecialchars(trim($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['field_type'] == 'checkbox'): ?>
                            <div class="form-check">
                                <input type="checkbox" id="<?= htmlspecialchars($field['unique_name']) ?>" name="<?= htmlspecialchars($field['unique_name']) ?>" class="form-check-input" <?= $field['required'] ? 'required' : '' ?>>
                                <label for="<?= htmlspecialchars($field['unique_name']) ?>" class="form-check-label"><?= htmlspecialchars($field['field_name']) ?></label>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Bewerbung absenden</button>
            </form>
        <?php endif; ?>
    </div>

    <footer class="mt-4 text-center">
        &copy; <?= htmlspecialchars(SERVER_NAME) ?> <?= date('Y') ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
