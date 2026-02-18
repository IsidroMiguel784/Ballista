<?php
require_once 'includes/header.php';

// Verifico si el usuario está logueado
if (!isLoggedIn()) {
    // Redirigir a la página de inicio con un mensaje
    $_SESSION['message'] = 'Debes iniciar sesión para acceder a esta página.';
    $_SESSION['message_type'] = 'warning';
    header('Location: index.php');
    exit;
}

// Obtengo información del usuario
$stmt = $pdo->prepare("
    SELECT id, nombre, email, nivel_mecenas, fecha_registro, fecha_mecenas
    FROM usuarios
    WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

// Proceso la selección o cambio de nivel de mecenas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nivel_mecenas'])) {
    $nivel_mecenas = $_POST['nivel_mecenas'];
    $precio = ($nivel_mecenas === 'hoplita') ? 5 : 10;
    
    try {
        // Inicio transacción
        $pdo->beginTransaction();
        
        // Actualizo el nivel de mecenas del usuario
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nivel_mecenas = ?, fecha_mecenas = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$nivel_mecenas, $_SESSION['user_id']]);
        
        // Genero factura
        $stmt = $pdo->prepare("
            INSERT INTO facturas (id_usuario, nivel_mecenas, monto, fecha) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $nivel_mecenas, $precio]);
        
        $factura_id = $pdo->lastInsertId();
        
        // Genero PDF de factura
        require_once 'lib/tcpdf/tcpdf.php';
        
        // Creo nuevo documento PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Establezco información del documento
        $pdf->SetCreator('Ballista');
        $pdf->SetAuthor('Ballista');
        $pdf->SetTitle('Factura #' . sprintf('%06d', $factura_id));
        $pdf->SetSubject('Factura de Mecenazgo');
        
        // Elimino cabecera y pie de página predeterminados
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Establezco márgenes
        $pdf->SetMargins(15, 15, 15);
        
        // Agrego una página
        $pdf->AddPage();
        
        // Establezco fuente
        $pdf->SetFont('helvetica', '', 10);
        
        // Logo y datos de la empresa
        $pdf->Image('img/logo-ballista.png', 15, 15, 40, 0, 'PNG');
        $pdf->SetXY(120, 15);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'FACTURA', 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(120, 25);
        $pdf->Cell(0, 6, 'Nº: ' . sprintf('%06d', $factura_id), 0, 1, 'R');
        $pdf->SetXY(120, 31);
        $pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
        
        // Datos de la empresa
        $pdf->SetXY(15, 40);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'BALLISTA HISTORIA MILITAR, S.L.', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'CIF: B12345678', 0, 1, 'L');
        $pdf->Cell(0, 6, 'Calle Historia, 123', 0, 1, 'L');
        $pdf->Cell(0, 6, '28001 Madrid, España', 0, 1, 'L');
        $pdf->Cell(0, 6, 'Email: info@ballista.com', 0, 1, 'L');
        $pdf->Cell(0, 6, 'Web: www.ballista.com', 0, 1, 'L');
        
        // Datos del cliente
        $pdf->SetXY(120, 40);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'FACTURAR A:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(120, 46);
        $pdf->Cell(0, 6, $usuario['nombre'], 0, 1, 'L');
        $pdf->SetXY(120, 52);
        $pdf->Cell(0, 6, 'Email: ' . $usuario['email'], 0, 1, 'L');
        $pdf->SetXY(120, 58);
        $pdf->Cell(0, 6, 'ID Cliente: ' . sprintf('%06d', $usuario['id']), 0, 1, 'L');
        
        // Título de la factura
        $pdf->SetXY(15, 80);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'DETALLE DE FACTURA', 0, 1, 'L');
        
        // Cabecera de la tabla
        $pdf->SetXY(15, 90);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(100, 8, 'DESCRIPCIÓN', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'CANTIDAD', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'PRECIO', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'TOTAL', 1, 1, 'C', true);
        
        // Contenido de la tabla
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(100, 8, 'Suscripción Mecenas - Nivel ' . ucfirst($nivel_mecenas), 1, 0, 'L');
        $pdf->Cell(30, 8, '1', 1, 0, 'C');
        $pdf->Cell(30, 8, $precio . ' €', 1, 0, 'C');
        $pdf->Cell(30, 8, $precio . ' €', 1, 1, 'C');
        
        // Total
        $pdf->SetXY(145, 106);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 8, 'TOTAL:', 1, 0, 'R', true);
        $pdf->Cell(30, 8, $precio . ' €', 1, 1, 'C', true);
        
        // Información adicional
        $pdf->SetXY(15, 130);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'INFORMACIÓN DE PAGO:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Método de pago: Tarjeta de crédito', 0, 1, 'L');
        $pdf->Cell(0, 6, 'Fecha de pago: ' . date('d/m/Y'), 0, 1, 'L');
        
        // Notas
        $pdf->SetXY(15, 160);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'NOTAS:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, 'Gracias por convertirte en mecenas de Ballista. Tu apoyo es fundamental para nuestro proyecto. Esta suscripción se renovará automáticamente cada mes hasta que decidas cancelarla.', 0, 'L');
        
        // Pie de página
        $pdf->SetY(-40);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Este documento es una factura electrónica válida según la normativa vigente.', 0, 1, 'C');
        $pdf->Cell(0, 10, 'Ballista Historia Militar, S.L. - Todos los derechos reservados ' . date('Y'), 0, 1, 'C');
        
        // Guardo PDF en el servidor
        $pdf_path = 'facturas/factura_' . sprintf('%06d', $factura_id) . '.pdf';
        $pdf->Output(__DIR__ . '/' . $pdf_path, 'F');
        
        // Actualizo la ruta del PDF en la base de datos
        $stmt = $pdo->prepare("UPDATE facturas SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$pdf_path, $factura_id]);
        
        // Envio email con la factura
        require_once 'lib/phpmailer/PHPMailer.php';
        require_once 'lib/phpmailer/SMTP.php';
        require_once 'lib/phpmailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración del servidor (cosa que en este caso no me ha dado tiempo a pulirlo)
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'info@ballista.com'; 
            $mail->Password = 'password';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // Destinatarios
            $mail->setFrom('info@ballista.com', 'Ballista Historia Militar');
            $mail->addAddress($usuario['email'], $usuario['nombre']);
            
            // Adjuntos
            $mail->addAttachment(__DIR__ . '/' . $pdf_path);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Factura de Mecenazgo - Ballista';
            $mail->Body = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .content { margin-bottom: 30px; }
                        .footer { font-size: 12px; text-align: center; color: #777; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>¡Gracias por tu apoyo como Mecenas!</h2>
                        </div>
                        <div class="content">
                            <p>Estimado/a ' . $usuario['nombre'] . ',</p>
                            <p>Gracias por convertirte en mecenas de Ballista con el nivel <strong>' . ucfirst($nivel_mecenas) . '</strong>. Tu apoyo es fundamental para nuestro proyecto.</p>
                            <p>Adjunto encontrarás la factura correspondiente a tu suscripción.</p>
                            <p>Detalles de la suscripción:</p>
                            <ul>
                                <li><strong>Nivel:</strong> ' . ucfirst($nivel_mecenas) . '</li>
                                <li><strong>Precio:</strong> ' . $precio . ' € / mes</li>
                                <li><strong>Fecha de inicio:</strong> ' . date('d/m/Y') . '</li>
                            </ul>
                            <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos respondiendo a este email.</p>
                            <p>¡Bienvenido a la comunidad de mecenas de Ballista!</p>
                            <p>Saludos cordiales,<br>El equipo de Ballista</p>
                        </div>
                        <div class="footer">
                            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                            <p>© ' . date('Y') . ' Ballista Historia Militar. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            $mail->send();
            
            // Confirmo transacción
            $pdo->commit();
            
            // Mensaje de éxito
            $_SESSION['message'] = '¡Gracias por convertirte en mecenas! Se ha enviado un email con la factura a tu correo electrónico.';
            $_SESSION['message_type'] = 'success';
            
            // Redirijo a la página de perfil
            header('Location: perfil.php?tab=mecenas');
            exit;
            
        } catch (Exception $e) {
            // Revierto transacción en caso de error
            $pdo->rollBack();
            
            // Mensaje de error
            $_SESSION['message'] = 'Error al procesar la solicitud: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    } catch (PDOException $e) {
        // Revierto transacción en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Mensaje de error
        $_SESSION['message'] = 'Error al procesar la solicitud: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-lg-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h1 class="mb-4">Hazte Mecenas</h1>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Beneficios de ser Mecenas</h5>
                </div>
                <div class="card-body">
                    <p class="lead">Apoya a Ballista y obtén beneficios exclusivos</p>
                    <p>Como mecenas, no solo contribuyes al crecimiento de nuestra comunidad, sino que también obtienes acceso a contenido exclusivo, eventos especiales y reconocimiento dentro de la plataforma.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">Beneficios Generales</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Nombre destacado en el foro</li>
                                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Acceso a foros exclusivos</li>
                                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Contenido histórico premium</li>
                                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Descuentos en eventos</li>
                                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> Soporte prioritario</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0">¿Por qué ser mecenas?</h5>
                                </div>
                                <div class="card-body">
                                    <p>Tu apoyo nos permite:</p>
                                    <ul>
                                        <li>Mantener y mejorar la plataforma</li>
                                        <li>Crear contenido de calidad</li>
                                        <li>Organizar eventos y webinars</li>
                                        <li>Colaborar con historiadores y expertos</li>
                                        <li>Preservar y difundir el conocimiento sobre historia militar</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 mecenas-card hoplita" id="hoplitaCard">
                        <div class="card-header">
                            Hoplita
                        </div>
                        <div class="card-body">
                            <div class="price">5€/mes</div>
                            <ul class="feature-list">
                                <li><i class="bi bi-check-circle-fill"></i> Nombre destacado en gris</li>
                                <li><i class="bi bi-check-circle-fill"></i> Acceso a foros exclusivos</li>
                                <li><i class="bi bi-check-circle-fill"></i> Contenido histórico premium</li>
                                <li><i class="bi bi-check-circle-fill"></i> Descuentos en eventos</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <?php if (!isLoggedIn()): ?>
                                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#authModal">Iniciar sesión para seleccionar</button>
                            <?php elseif ($usuario['nivel_mecenas'] === 'hoplita'): ?>
                                <span class="badge bg-success p-2">Tu nivel actual</span>
                            <?php elseif ($usuario['nivel_mecenas'] === 'centurion'): ?>
                                <form action="mecenas.php" method="post">
                                    <input type="hidden" name="nivel_mecenas" value="hoplita">
                                    <button type="submit" class="btn btn-outline-secondary">Cambiar a este nivel</button>
                                </form>
                            <?php else: ?>
                                <form action="mecenas.php" method="post">
                                    <input type="hidden" name="nivel_mecenas" value="hoplita">
                                    <button type="submit" class="btn btn-outline-secondary">Seleccionar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 mecenas-card centurion" id="centurionCard">
                        <div class="card-header">
                            Centurión
                        </div>
                        <div class="card-body">
                            <div class="price">10€/mes</div>
                            <ul class="feature-list">
                                <li><i class="bi bi-check-circle-fill"></i> Nombre destacado en dorado</li>
                                <li><i class="bi bi-check-circle-fill"></i> Todo lo incluido en Hoplita</li>
                                <li><i class="bi bi-check-circle-fill"></i> Acceso anticipado a contenido</li>
                                <li><i class="bi bi-check-circle-fill"></i> Webinars exclusivos con historiadores</li>
                                <li><i class="bi bi-check-circle-fill"></i> Insignia especial en el perfil</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <?php if (!isLoggedIn()): ?>
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#authModal">Iniciar sesión para seleccionar</button>
                            <?php elseif ($usuario['nivel_mecenas'] === 'centurion'): ?>
                                <span class="badge bg-success p-2">Tu nivel actual</span>
                            <?php elseif ($usuario['nivel_mecenas'] === 'hoplita'): ?>
                                <form action="mecenas.php" method="post">
                                    <input type="hidden" name="nivel_mecenas" value="centurion">
                                    <button type="submit" class="btn btn-warning">Cambiar a este nivel</button>
                                </form>
                            <?php else: ?>
                                <form action="mecenas.php" method="post">
                                    <input type="hidden" name="nivel_mecenas" value="centurion">
                                    <button type="submit" class="btn btn-warning">Seleccionar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Preguntas Frecuentes</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    ¿Cómo funciona el sistema de mecenazgo?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    El sistema de mecenazgo de Ballista te permite apoyar nuestro proyecto mediante una suscripción mensual. Puedes elegir entre dos niveles: Hoplita (5€/mes) o Centurión (10€/mes). Cada nivel ofrece diferentes beneficios, como nombre destacado, acceso a contenido exclusivo y más.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    ¿Puedo cambiar mi nivel de mecenazgo?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sí, puedes cambiar tu nivel de mecenazgo en cualquier momento. Si ya eres mecenas, simplemente selecciona el nuevo nivel que deseas y se aplicará el cambio inmediatamente. El nuevo precio se aplicará en tu próxima facturación.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    ¿Cómo puedo cancelar mi suscripción?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Puedes cancelar tu suscripción en cualquier momento desde tu perfil, en la sección de Mecenazgo. La cancelación se hará efectiva al final del período de facturación actual, y no se te cobrará más a partir de entonces. Mantendrás tus beneficios hasta el final del período pagado.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    ¿Qué métodos de pago aceptan?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Actualmente aceptamos pagos con tarjeta de crédito/débito (Visa, Mastercard, American Express) y PayPal. Todos los pagos se procesan de forma segura a través de pasarelas de pago certificadas.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    ¿Recibiré una factura por mi suscripción?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sí, recibirás automáticamente una factura en formato PDF por email cada vez que se realice un cargo. También puedes acceder a todas tus facturas desde tu perfil, en la sección de Mecenazgo.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>