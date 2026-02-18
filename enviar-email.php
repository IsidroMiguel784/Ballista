<?php
session_start();
require_once 'config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargo PHPMailer
require 'lib/phpmailer/src/Exception.php';
require 'lib/phpmailer/src/PHPMailer.php';
require 'lib/phpmailer/src/SMTP.php';

// Verifico si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Debes iniciar sesión para realizar esta acción.';
    $_SESSION['message_type'] = 'warning';
    header('Location: index.php');
    exit;
}

// Obtengo el ID de la factura
$id_factura = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_factura <= 0) {
    $_SESSION['message'] = 'ID de factura no válido.';
    $_SESSION['message_type'] = 'danger';
    header('Location: perfil.php?tab=mecenas');
    exit;
}

try {
    // Obtengo información de la factura y del usuario
    $stmt = $pdo->prepare("
        SELECT f.id, f.nivel_mecenas, f.monto, f.fecha,
               u.id as id_usuario, u.nombre, u.email
        FROM facturas f
        JOIN usuarios u ON f.id_usuario = u.id
        WHERE f.id = ? AND u.id = ?
    ");
    $stmt->execute([$id_factura, $_SESSION['user_id']]);
    $factura = $stmt->fetch();

    if (!$factura) {
        $_SESSION['message'] = 'La factura no existe o no tienes permiso para acceder a ella.';
        $_SESSION['message_type'] = 'danger';
        header('Location: perfil.php?tab=mecenas');
        exit;
    }

    // Creo instancia de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->SMTPDebug = 0; // 0 = sin depuración, 2 = depuración completa
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  
        $mail->SMTPAuth = true;
        $mail->Username = 'dawisidro@gmail.com';
        $mail->Password = 'xxx';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom('dawisidro@gmail.com', 'Ballista');
        
        // Uso el email del usuario de la base de datos
        $mail->addAddress($factura['email']);

        // Configuración del contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Información sobre tu factura de Mecenazgo';
        
        // Contenido HTML
        $mail->Body = '
        <html>
        <body>
            <h2>Información de Factura</h2>
            <p>Hola ' . $factura['nombre'] . ',</p>
            <p>Te informamos que tu factura de mecenazgo está disponible en tu perfil.</p>
            <p>Detalles de la factura:</p>
            <ul>
                <li>Número: ' . sprintf('%06d', $factura['id']) . '</li>
                <li>Fecha: ' . date('d/m/Y', strtotime($factura['fecha'])) . '</li>
                <li>Nivel: ' . ucfirst($factura['nivel_mecenas']) . '</li>
                <li>Importe: ' . number_format($factura['monto'], 2) . ' €</li>
            </ul>
            <p>Puedes descargar tu factura iniciando sesión en tu cuenta y visitando la sección de Mecenazgo.</p>
            <p>Gracias por tu apoyo.</p>
            <p>El equipo de Ballista</p>
        </body>
        </html>
        ';
        
        // Versión de texto plano
        $mail->AltBody = 'Hola ' . $factura['nombre'] . ',

Te informamos que tu factura de mecenazgo está disponible en tu perfil.

Detalles de la factura:
- Número: ' . sprintf('%06d', $factura['id']) . '
- Fecha: ' . date('d/m/Y', strtotime($factura['fecha'])) . '
- Nivel: ' . ucfirst($factura['nivel_mecenas']) . '
- Importe: ' . number_format($factura['monto'], 2) . ' €

Puedes descargar tu factura iniciando sesión en tu cuenta y visitando la sección de Mecenazgo.

Gracias por tu apoyo.

El equipo de Ballista';

        // Envio el email
        $mail->send();

        // Registro el envío en la base de datos (opcional)
        $stmt = $pdo->prepare("
            INSERT INTO emails_enviados (id_factura, id_usuario, fecha_envio, email_destino)
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$factura['id'], $factura['id_usuario'], $factura['email']]);

        // Mensaje de éxito
        $_SESSION['message'] = 'Email informativo enviado correctamente a ' . $factura['email'];
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        // Guardo el error en un archivo de registro
        error_log('Error PHPMailer: ' . $mail->ErrorInfo);
        
        $_SESSION['message'] = 'Error al enviar el email: ' . $mail->ErrorInfo;
        $_SESSION['message_type'] = 'danger';
    }
    
} catch (PDOException $e) {
    $_SESSION['message'] = 'Error en la base de datos: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirijo de vuelta a la página de perfil
header('Location: perfil.php?tab=mecenas');
exit;
?>