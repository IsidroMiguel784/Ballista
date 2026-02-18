<?php
require_once 'includes/header.php';

// Obtengo el slug de la categoría
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Verifico que el slug es válido
$stmt = $pdo->prepare("SELECT id, nombre FROM categorias WHERE slug = ?");
$stmt->execute([$slug]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header('Location: index.php');
    exit;
}

// Obtengo las publicaciones de esta categoría
$stmt = $pdo->prepare("
    SELECT p.id, p.titulo, p.contenido, p.imagen, p.fecha_publicacion, u.nombre as autor, u.nivel_mecenas
    FROM publicaciones p
    JOIN usuarios u ON p.id_usuario = u.id
    WHERE p.id_categoria = ?
    ORDER BY p.fecha_publicacion DESC
");
$stmt->execute([$categoria['id']]);
$publicaciones = $stmt->fetchAll();
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-lg-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h1 class="mb-4"><?php echo htmlspecialchars($categoria['nombre']); ?></h1>
            
            <?php if (isLoggedIn()): ?>
            <div class="mb-4">
                <a href="crear-publicacion.php?categoria=<?php echo $categoria['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva publicación en <?php echo htmlspecialchars($categoria['nombre']); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (empty($publicaciones)): ?>
                <div class="alert alert-info">
                    No hay publicaciones en esta categoría todavía.
                    <?php if (isLoggedIn()): ?>
                        ¡Sé el primero en crear una!
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($publicaciones as $publicacion): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <?php if ($publicacion['imagen']): ?>
                                    <img src="<?php echo htmlspecialchars($publicacion['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($publicacion['titulo']); ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/800x400?text=<?php echo urlencode($publicacion['titulo']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($publicacion['titulo']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($publicacion['titulo']); ?></h5>
                                    <p class="card-text"><?php echo substr(strip_tags($publicacion['contenido']), 0, 150) . '...'; ?></p>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        Por <span class="username <?php echo $publicacion['nivel_mecenas']; ?>"><?php echo htmlspecialchars($publicacion['autor']); ?></span> | 
                                        <?php echo date('d/m/Y', strtotime($publicacion['fecha_publicacion'])); ?>
                                    </small>
                                    <a href="ver-publicacion.php?id=<?php echo $publicacion['id']; ?>" class="btn btn-sm btn-outline-primary float-end">Leer más</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>