<?php
require_once 'config/db.php';
include 'includes/header.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'ID de publicación no válido.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$id_publicacion = (int)$_GET['id'];

// Obtener la publicación
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre, u.nombre as autor_nombre, 
           u.nivel_mecenas, u.id as id_usuario
    FROM publicaciones p 
    JOIN categorias c ON p.id_categoria = c.id 
    JOIN usuarios u ON p.id_usuario = u.id
    WHERE p.id = ?
");
$stmt->execute([$id_publicacion]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    $_SESSION['message'] = 'La publicación no existe.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Verificar si el usuario es el autor
$es_autor = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $publicacion['id_usuario'];

// Obtener comentarios existentes
$stmt = $pdo->prepare("
    SELECT c.id, c.comentario, c.fecha, u.nombre, u.nivel_mecenas, u.id as id_usuario
    FROM comentarios c
    JOIN usuarios u ON c.id_usuario = u.id
    WHERE c.id_publicacion = ?
    ORDER BY c.fecha DESC
");
$stmt->execute([$id_publicacion]);
$comentarios = $stmt->fetchAll();
?>

<div class="container mt-5 mb-5">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><?php echo htmlspecialchars($publicacion['titulo']); ?></h2>
                    <?php if ($es_autor): ?>
                        <div>
                            <a href="editar-publicacion.php?id=<?php echo $publicacion['id']; ?>" class="btn btn-sm btn-outline-light">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-4">
                        <div>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($publicacion['categoria_nombre']); ?></span>
                            <span class="ms-2">Por: 
                                <span class="username <?php echo $publicacion['nivel_mecenas']; ?>">
                                    <?php echo htmlspecialchars($publicacion['autor_nombre']); ?>
                                </span>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?php if (isset($publicacion['fecha_publicacion'])): ?>
                                Publicado: <?php echo date('d/m/Y H:i', strtotime($publicacion['fecha_publicacion'])); ?>
                            <?php endif; ?>
                            <?php if (isset($publicacion['fecha_actualizacion']) && $publicacion['fecha_actualizacion']): ?>
                                <br>Actualizado: <?php echo date('d/m/Y H:i', strtotime($publicacion['fecha_actualizacion'])); ?>
                            <?php endif; ?>
                        </small>
                    </div>

                    <?php if (!empty($publicacion['imagen'])): ?>
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($publicacion['imagen']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($publicacion['titulo']); ?>">
                    </div>
                    <?php endif; ?>

                    <div class="publicacion-contenido">
                        <?php echo $publicacion['contenido']; ?>
                    </div>
                </div>
            </div>

            <!-- Comentarios -->
            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0">Comentarios</h3>
                </div>
                <div class="card-body">
                    <?php if (isLoggedIn()): ?>
                        <form id="comentarioForm" class="mb-4">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id']; ?>">
                            <textarea class="form-control mb-2" name="comentario" rows="3" placeholder="Escribe tu comentario..."></textarea>
                            <button type="submit" class="btn btn-primary">Enviar</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">Debes iniciar sesión para comentar.</div>
                    <?php endif; ?>

                    <?php if (empty($comentarios)): ?>
                        <div class="alert alert-secondary">Aún no hay comentarios. ¡Sé el primero!</div>
                    <?php else: ?>
                        <?php foreach ($comentarios as $com): ?>
                            <div class="border rounded p-2 mb-2">
                                <small>
                                    <span class="username <?php echo $com['nivel_mecenas']; ?>">
                                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($com['nombre']); ?>
                                    </span>
                                    | <?php echo date('d/m/Y H:i', strtotime($com['fecha'])); ?>
                                </small>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($com['comentario'])); ?></p>
                                <?php if (isLoggedIn() && $_SESSION['user_id'] == $com['id_usuario']): ?>
                                    <button class="btn btn-sm btn-outline-danger btn-eliminar-comentario" data-id="<?php echo $com['id']; ?>">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Sidebar con información del autor -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Sobre el autor</h4>
                </div>
                <div class="card-body">
                    <p>
                        <strong>
                            <span class="username <?php echo $publicacion['nivel_mecenas']; ?>">
                                <?php echo htmlspecialchars($publicacion['autor_nombre']); ?>
                            </span>
                        </strong>
                    </p>
                    <?php if ($publicacion['nivel_mecenas'] && $publicacion['nivel_mecenas'] != 'ninguno'): ?>
                        <p>
                            <span class="badge bg-<?php echo $publicacion['nivel_mecenas'] === 'hoplita' ? 'secondary' : 'warning text-dark'; ?>">
                                Mecenas <?php echo ucfirst($publicacion['nivel_mecenas']); ?>
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Más publicaciones del autor -->
            <?php
            $stmt = $pdo->prepare("
                SELECT id, titulo 
                FROM publicaciones 
                WHERE id_usuario = ? AND id != ? 
                ORDER BY fecha_publicacion DESC 
                LIMIT 5
            ");
            $stmt->execute([$publicacion['id_usuario'], $publicacion['id']]);
            $mas_publicaciones = $stmt->fetchAll();

            if (!empty($mas_publicaciones)):
            ?>
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Más del autor</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($mas_publicaciones as $pub): ?>
                            <li class="list-group-item">
                                <a href="ver-publicacion.php?id=<?php echo $pub['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($pub['titulo']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>









