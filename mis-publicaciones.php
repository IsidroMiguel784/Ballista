<?php
session_start();
require_once 'config/db.php';

// Verifico si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Debes iniciar sesión para acceder a esta página.';
    $_SESSION['message_type'] = 'warning';
    header('Location: index.php');
    exit;
}

// Obtengo las publicaciones del usuario
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM publicaciones p 
    JOIN categorias c ON p.id_categoria = c.id 
    WHERE p.id_usuario = ? 
    ORDER BY p.fecha_creacion DESC
");
$stmt->execute([$_SESSION['user_id']]);
$publicaciones = $stmt->fetchAll();

// Proceso eliminación
if (isset($_POST['eliminar_publicacion']) && isset($_POST['id_publicacion'])) {
    $id_publicacion = (int)$_POST['id_publicacion'];
    
    // Verifico que la publicación pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM publicaciones WHERE id = ? AND id_usuario = ?");
    $stmt->execute([$id_publicacion, $_SESSION['user_id']]);
    $publicacion = $stmt->fetch();
    
    if ($publicacion) {
        try {
            // Elimino la publicación
            $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
            $stmt->execute([$id_publicacion]);
            
            $_SESSION['message'] = 'Publicación eliminada correctamente.';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error al eliminar la publicación: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'No tienes permiso para eliminar esta publicación o no existe.';
        $_SESSION['message_type'] = 'danger';
    }
    
    // Redirecciono para evitar reenvío del formulario
    header('Location: mis-publicaciones.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Publicaciones - Ballista</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5 mb-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Mis Publicaciones</h1>
            </div>
            <div class="col-md-4 text-end">
                <a href="publicacion.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva Publicación
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <?php if (empty($publicaciones)): ?>
            <div class="alert alert-info">
                No tienes publicaciones. ¡Crea tu primera publicación ahora!
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($publicaciones as $publicacion): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($publicacion['categoria_nombre']); ?></span>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($publicacion['fecha_creacion'])); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($publicacion['titulo']); ?></h5>
                                <p class="card-text">
                                    <?php 
                                    // Muestro un extracto del contenido
                                    $contenido_limpio = strip_tags($publicacion['contenido']);
                                    echo strlen($contenido_limpio) > 150 ? 
                                        htmlspecialchars(substr($contenido_limpio, 0, 150)) . '...' : 
                                        htmlspecialchars($contenido_limpio); 
                                    ?>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between">
                                <a href="ver-publicacion.php?id=<?php echo $publicacion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <div>
                                    <a href="publicacion.php?id=<?php echo $publicacion['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" data-bs-target="#eliminarModal<?php echo $publicacion['id']; ?>">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmación para eliminar -->
                    <div class="modal fade" id="eliminarModal<?php echo $publicacion['id']; ?>" tabindex="-1" aria-labelledby="eliminarModalLabel<?php echo $publicacion['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="eliminarModalLabel<?php echo $publicacion['id']; ?>">Confirmar eliminación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Estás seguro de que deseas eliminar la publicación "<?php echo htmlspecialchars($publicacion['titulo']); ?>"?
                                    Esta acción no se puede deshacer.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id']; ?>">
                                        <button type="submit" name="eliminar_publicacion" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



