<?php
require_once 'includes/header.php';

// Verifico si el usuario está logueado
if (!isLoggedIn()) {
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

if (!$usuario) {
    header('Location: index.php');
    exit;
}

// Obtengo publicaciones del usuario
$stmt = $pdo->prepare("
    SELECT p.id, p.titulo, p.contenido, p.imagen, p.fecha_publicacion, p.fecha_actualizacion,
           c.nombre as categoria, c.slug as categoria_slug
    FROM publicaciones p
    JOIN categorias c ON p.id_categoria = c.id
    WHERE p.id_usuario = ?
    ORDER BY p.fecha_publicacion DESC
");
$stmt->execute([$_SESSION['user_id']]);
$publicaciones = $stmt->fetchAll();

// Obtengo facturas del usuario
$stmt = $pdo->prepare("
    SELECT id, nivel_mecenas, monto, fecha, pdf_path
    FROM facturas
    WHERE id_usuario = ?
    ORDER BY fecha DESC
");
$stmt->execute([$_SESSION['user_id']]);
$facturas = $stmt->fetchAll();

// Determino la pestaña activa
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'perfil';
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-lg-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h1 class="mb-4">Mi Perfil</h1>
            
            <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($tab == 'perfil') ? 'active' : ''; ?>" id="perfil-tab" data-bs-toggle="tab" data-bs-target="#perfil-tab-pane" type="button" role="tab" aria-controls="perfil-tab-pane" aria-selected="<?php echo ($tab == 'perfil') ? 'true' : 'false'; ?>">
                        <i class="bi bi-person"></i> Perfil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($tab == 'mis-publicaciones') ? 'active' : ''; ?>" id="publicaciones-tab" data-bs-toggle="tab" data-bs-target="#publicaciones-tab-pane" type="button" role="tab" aria-controls="publicaciones-tab-pane" aria-selected="<?php echo ($tab == 'mis-publicaciones') ? 'true' : 'false'; ?>">
                        <i class="bi bi-file-text"></i> Mis Publicaciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($tab == 'mecenas') ? 'active' : ''; ?>" id="mecenas-tab" data-bs-toggle="tab" data-bs-target="#mecenas-tab-pane" type="button" role="tab" aria-controls="mecenas-tab-pane" aria-selected="<?php echo ($tab == 'mecenas') ? 'true' : 'false'; ?>">
                        <i class="bi bi-star"></i> Mecenazgo
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabContent">
                <!-- Pestaña de Perfil -->
                <div class="tab-pane fade <?php echo ($tab == 'perfil') ? 'show active' : ''; ?>" id="perfil-tab-pane" role="tabpanel" aria-labelledby="perfil-tab" tabindex="0">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Nombre:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Correo Electrónico:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($usuario['email']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Fecha de Registro:</div>
                                <div class="col-md-8"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Nivel de Mecenas:</div>
                                <div class="col-md-8">
                                    <?php if ($usuario['nivel_mecenas'] == 'ninguno'): ?>
                                        <span class="badge bg-secondary">No eres mecenas</span>
                                    <?php elseif ($usuario['nivel_mecenas'] == 'hoplita'): ?>
                                        <span class="badge bg-secondary">Hoplita</span>
                                        <small class="text-muted ms-2">Desde <?php echo date('d/m/Y', strtotime($usuario['fecha_mecenas'])); ?></small>
                                    <?php elseif ($usuario['nivel_mecenas'] == 'centurion'): ?>
                                        <span class="badge bg-warning text-dark">Centurión</span>
                                        <small class="text-muted ms-2">Desde <?php echo date('d/m/Y', strtotime($usuario['fecha_mecenas'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil"></i> Editar Perfil
                            </button>
                            <button type="button" class="btn btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="bi bi-key"></i> Cambiar Contraseña
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Pestaña de Mis Publicaciones -->
                <div class="tab-pane fade <?php echo ($tab == 'mis-publicaciones') ? 'show active' : ''; ?>" id="publicaciones-tab-pane" role="tabpanel" aria-labelledby="publicaciones-tab" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>Mis Publicaciones</h3>
                        <a href="crear-publicacion.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Publicación
                        </a>
                    </div>
                    
                    <?php if (empty($publicaciones)): ?>
                        <div class="alert alert-info">
                            No has creado ninguna publicación todavía.
                            <a href="crear-publicacion.php" class="alert-link">¡Crea tu primera publicación!</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Categoría</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publicaciones as $publicacion): ?>
                                        <tr>
                                            <td>
                                                <a href="publicacion.php?id=<?php echo $publicacion['id']; ?>">
                                                    <?php echo htmlspecialchars($publicacion['titulo']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="categoria.php?slug=<?php echo $publicacion['categoria_slug']; ?>">
                                                    <?php echo htmlspecialchars($publicacion['categoria']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($publicacion['fecha_publicacion'])); ?>
                                                <?php if ($publicacion['fecha_actualizacion']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Actualizada: <?php echo date('d/m/Y', strtotime($publicacion['fecha_actualizacion'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="editar-publicacion.php?id=<?php echo $publicacion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $publicacion['id']; ?>">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                </div>
                                                
                                                <!-- Modal de confirmación para eliminar -->
                                                <div class="modal fade" id="deleteModal<?php echo $publicacion['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $publicacion['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $publicacion['id']; ?>">Confirmar eliminación</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                ¿Estás seguro de que deseas eliminar la publicación "<?php echo htmlspecialchars($publicacion['titulo']); ?>"? Esta acción no se puede deshacer.
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="button" class="btn btn-danger delete-btn" data-id="<?php echo $publicacion['id']; ?>">Eliminar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pestaña de Mecenazgo -->
                <div class="tab-pane fade <?php echo ($tab == 'mecenas') ? 'show active' : ''; ?>" id="mecenas-tab-pane" role="tabpanel" aria-labelledby="mecenas-tab" tabindex="0">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Estado de Mecenazgo</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($usuario['nivel_mecenas'] == 'ninguno'): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> No eres mecenas todavía. Conviértete en mecenas para apoyar nuestro proyecto y obtener beneficios exclusivos.
                                </div>
                                <button class="btn btn-mecenas" data-bs-toggle="modal" data-bs-target="#mecenasModal">HAZTE MECENAS AHORA</button>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Eres mecenas nivel <strong><?php echo ucfirst($usuario['nivel_mecenas']); ?></strong> desde el <?php echo date('d/m/Y', strtotime($usuario['fecha_mecenas'])); ?>.
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3 <?php echo ($usuario['nivel_mecenas'] == 'hoplita') ? 'border-secondary' : ''; ?>">
                                            <div class="card-header bg-secondary text-white">
                                                <h5 class="mb-0">Hoplita</h5>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>5€/mes</strong></p>
                                                <ul>
                                                    <li>Nombre destacado en gris</li>
                                                    <li>Acceso a foros exclusivos</li>
                                                    <li>Contenido histórico premium</li>
                                                    <li>Descuentos en eventos</li>
                                                </ul>
                                                <?php if ($usuario['nivel_mecenas'] == 'hoplita'): ?>
                                                    <span class="badge bg-success">Tu nivel actual</span>
                                                <?php elseif ($usuario['nivel_mecenas'] == 'centurion'): ?>
                                                    <button class="btn btn-outline-secondary select-mecenas" data-level="hoplita" data-price="5">Cambiar a este nivel</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3 <?php echo ($usuario['nivel_mecenas'] == 'centurion') ? 'border-warning' : ''; ?>">
                                            <div class="card-header bg-warning">
                                                <h5 class="mb-0">Centurión</h5>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>10€/mes</strong></p>
                                                <ul>
                                                    <li>Nombre destacado en dorado</li>
                                                    <li>Todo lo incluido en Hoplita</li>
                                                    <li>Acceso anticipado a contenido</li>
                                                    <li>Webinars exclusivos con historiadores</li>
                                                    <li>Insignia especial en el perfil</li>
                                                </ul>
                                                <?php if ($usuario['nivel_mecenas'] == 'centurion'): ?>
                                                    <span class="badge bg-success">Tu nivel actual</span>
                                                <?php elseif ($usuario['nivel_mecenas'] == 'hoplita'): ?>
                                                    <button class="btn btn-warning select-mecenas" data-level="centurion" data-price="10">Cambiar a este nivel</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h5>Facturas</h5>
                                    <?php if (empty($facturas)): ?>
                                        <div class="alert alert-info">No hay facturas disponibles.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Nº Factura</th>
                                                        <th>Fecha</th>
                                                        <th>Nivel</th>
                                                        <th>Importe</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($facturas as $factura): ?>
                                                        <tr>
                                                            <td><?php echo sprintf('%06d', $factura['id']); ?></td>
                                                            <td><?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></td>
                                                            <td><?php echo ucfirst($factura['nivel_mecenas']); ?></td>
                                                            <td><?php echo number_format($factura['monto'], 2); ?> €</td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="factura-pdf.php?id=<?php echo $factura['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                        <i class="bi bi-file-pdf"></i> Ver PDF
                                                                    </a>
                                                                    <a href="enviar-email.php?id=<?php echo $factura['id']; ?>" class="btn btn-sm btn-outline-success">
                                                                        <i class="bi bi-envelope"></i> Enviar por Email
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Perfil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" action="api/auth.php" method="post">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="editName" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editName" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="editEmail" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="alert alert-danger d-none" id="editProfileError"></div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Cambiar Contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" action="api/auth.php" method="post">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="alert alert-danger d-none" id="changePasswordError"></div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Envio email de factura
        const sendEmailBtns = document.querySelectorAll('.send-email-btn');
        if (sendEmailBtns.length > 0) {
            sendEmailBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const facturaId = this.getAttribute('data-factura-id');
                    
                    fetch(this.href)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Email enviado correctamente. Por favor, revisa tu bandeja de entrada.');
                            } else {
                                alert('Error al enviar el email: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al enviar el email. Por favor, inténtalo de nuevo más tarde.');
                        });
                });
            });
        }
        
        // Edito perfil
        const editProfileForm = document.getElementById('editProfileForm');
        if (editProfileForm) {
            editProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const errorElement = document.getElementById('editProfileError');
                
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        errorElement.textContent = data.message;
                        errorElement.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorElement.textContent = 'Error al procesar la solicitud. Por favor, inténtalo de nuevo más tarde.';
                    errorElement.classList.remove('d-none');
                });
            });
        }
        
        // Cambio contraseña
        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const errorElement = document.getElementById('changePasswordError');
                
                // Valido que las contraseñas coincidan
                if (newPassword !== confirmPassword) {
                    errorElement.textContent = 'Las contraseñas no coinciden';
                    errorElement.classList.remove('d-none');
                    return;
                }
                
                const formData = new FormData(this);
                
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        errorElement.textContent = data.message;
                        errorElement.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorElement.textContent = 'Error al procesar la solicitud. Por favor, inténtalo de nuevo más tarde.';
                    errorElement.classList.remove('d-none');
                });
            });
        }

        document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
        const publicacionId = this.dataset.id;

        fetch('api/publicaciones.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'delete',
                id: publicacionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Error al eliminar la publicación.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al enviar la solicitud. Inténtalo más tarde.');
        });
    });
});

    });
</script>

<?php require_once 'includes/footer.php'; ?>