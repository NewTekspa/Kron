<?php
// Página simple para testear envío de correo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = 'jose.barrios.q@gmail.com';
    $subject = 'Prueba de notificación desde KRON';
    $message = isset($_POST['mensaje']) ? $_POST['mensaje'] : 'Este es un correo de prueba.';
    $headers = "From: notificaciones@kron.local\r\n" .
               "Reply-To: notificaciones@kron.local\r\n" .
               "X-Mailer: PHP/" . phpversion();
    $enviado = mail($to, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Envío de Correo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .result { margin-top: 1em; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Test de Envío de Correo</h1>
    <form method="post">
        <label for="mensaje">Mensaje a enviar:</label><br>
        <textarea name="mensaje" id="mensaje" rows="4" cols="50">Hola, este es un correo de prueba desde KRON.</textarea><br><br>
        <button type="submit">Enviar correo</button>
    </form>
    <?php if (isset($enviado)): ?>
        <div class="result" style="color:<?= $enviado ? 'green' : 'red' ?>;">
            <?= $enviado ? '¡Correo enviado correctamente!' : 'Error al enviar el correo.' ?>
        </div>
    <?php endif; ?>
</body>
</html>
