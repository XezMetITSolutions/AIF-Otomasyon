<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $iban = $_POST['iban'];
    $total = $_POST['total'];

    // Hier könntest du die Daten in einer Datenbank speichern oder weiter verarbeiten
    // Zum Beispiel: INSERT INTO spesen (name, iban, total, status) VALUES ...

    echo 'Spesenformular erfolgreich eingereicht!';
} else {
    echo 'Ungültige Anfrage!';
}
?>
