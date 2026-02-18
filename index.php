<?php
require_once 'includes/header.php';

// Configuración de paginación para publicaciones recientes
$publicaciones_por_pagina = 6;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $publicaciones_por_pagina;

// Obtengo las últimas publicaciones de cada categoría
$stmt = $pdo->query("
    SELECT c.id as categoria_id, c.nombre as categoria_nombre, c.slug as categoria_slug,
           p.id, p.titulo, p.contenido, p.imagen, p.fecha_publicacion, 
           u.nombre as autor, u.nivel_mecenas
    FROM categorias c
    LEFT JOIN (
        SELECT p1.*
        FROM publicaciones p1
        LEFT JOIN publicaciones p2
        ON p1.id_categoria = p2.id_categoria AND p1.fecha_publicacion < p2.fecha_publicacion
        WHERE p2.id IS NULL
    ) p ON c.id = p.id_categoria
    LEFT JOIN usuarios u ON p.id_usuario = u.id
    ORDER BY c.nombre
");
$categorias = [];
while ($row = $stmt->fetch()) {
    if (!isset($categorias[$row['categoria_id']])) {
        $categorias[$row['categoria_id']] = [
            'id' => $row['categoria_id'],
            'nombre' => $row['categoria_nombre'],
            'slug' => $row['categoria_slug'],
            'publicaciones' => []
        ];
    }
    
    if ($row['id']) {
        $categorias[$row['categoria_id']]['publicaciones'][] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'contenido' => $row['contenido'],
            'imagen' => $row['imagen'],
            'fecha_publicacion' => $row['fecha_publicacion'],
            'autor' => $row['autor'],
            'nivel_mecenas' => $row['nivel_mecenas']
        ];
    }
}

// Cuento el total de publicaciones para la paginación
$stmt = $pdo->query("SELECT COUNT(*) FROM publicaciones");
$total_publicaciones = $stmt->fetchColumn();
$total_paginas = ceil($total_publicaciones / $publicaciones_por_pagina);

// Obtengo las publicaciones más recientes con paginación
$stmt = $pdo->prepare("
    SELECT p.id, p.titulo, p.contenido, p.imagen, p.fecha_publicacion, 
           u.nombre as autor, u.nivel_mecenas, c.nombre as categoria, c.slug as categoria_slug
    FROM publicaciones p
    JOIN usuarios u ON p.id_usuario = u.id
    JOIN categorias c ON p.id_categoria = c.id
    ORDER BY p.fecha_publicacion DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':limit', $publicaciones_por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$publicacionesRecientes = $stmt->fetchAll();
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-lg-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <!-- Carousel -->
            <div id="mainCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
                <!-- Carousel Indicators -->
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                </div>
                
                <!-- Carousel Items -->
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="assets/img/carrusel/foto1.jpeg" class="d-block w-100" alt="Slide 1">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Bienvenido a Ballista</h5>
                            <p>Descubre nuestro increíble contenido y servicios.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="assets/img/carrusel/foto2.jpeg" class="d-block w-100" alt="Slide 2">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Novedades</h5>
                            <p>Mantente al día con nuestras últimas actualizaciones.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="assets/img/carrusel/foto3.jpeg" class="d-block w-100" alt="Slide 3">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Hazte Mecenas</h5>
                            <p>Apoya nuestro proyecto y obtén beneficios exclusivos.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Carousel Controls -->
                <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            
            <!-- Publicaciones Recientes -->
            <section class="mb-5">
                <h2 class="mb-4">Publicaciones Recientes</h2>
                
                <?php if (empty($publicacionesRecientes)): ?>
                    <div class="alert alert-info">
                        No hay publicaciones todavía.
                        <?php if (isLoggedIn()): ?>
                            <a href="crear-publicacion.php" class="alert-link">¡Crea la primera!</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($publicacionesRecientes as $publicacion): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php if ($publicacion['imagen']): ?>
                                        <img src="<?php echo htmlspecialchars($publicacion['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($publicacion['titulo']); ?>">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/800x400?text=<?php echo urlencode($publicacion['titulo']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($publicacion['titulo']); ?>">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($publicacion['titulo']); ?></h5>
                                        <p class="card-text"><?php echo substr(strip_tags($publicacion['contenido']), 0, 100) . '...'; ?></p>
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
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Navegación de publicaciones" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
            
            <!-- Categorías -->
            <?php foreach ($categorias as $categoria): ?>
                <section class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?php echo htmlspecialchars($categoria['nombre']); ?></h2>
                        <a href="categoria.php?slug=<?php echo $categoria['slug']; ?>" class="btn btn-outline-primary">Ver todas</a>
                    </div>
                    
                    <?php if (empty($categoria['publicaciones'])): ?>
                        <div class="alert alert-info">
                            No hay publicaciones en esta categoría todavía.
                            <?php if (isLoggedIn()): ?>
                                <a href="crear-publicacion.php?categoria=<?php echo $categoria['id']; ?>" class="alert-link">¡Crea la primera!</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($categoria['publicaciones'] as $publicacion): ?>
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
                </section>
            <?php endforeach; ?>
            
            <!-- Sobre Nosotros -->
            <section class="about-section mb-5">
                <h2 class="mb-4">Sobre Nosotros</h2>
                <div class="row">
                    <div class="col-lg-8">
                        <p>Bienvenido a Ballista, la red social dedicada a la historia de la guerra, la estrategia militar y la evolución del armamento.</p>
                        
                        <p>Aquí, entusiastas, historiadores y apasionados del arte de la guerra pueden compartir sus conocimientos, experiencias y análisis sobre los conflictos que han dado forma al mundo.</p>
                        
                        <p>Desde las antiguas batallas hasta la guerra moderna, exploramos tácticas, liderazgo, innovación en armamento y los eventos que marcaron la historia militar.</p>
                        
                        <p>Únete a nuestra comunidad y sé parte del legado de la estrategia y el combate. ⚔🔥</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Únete a Ballista</h5>
                                <p class="card-text">Forma parte de nuestra comunidad de entusiastas de la historia militar y comparte tu pasión.</p>
                                <?php if (!isLoggedIn()): ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#authModal">Regístrate Ahora</button>
                                <?php else: ?>
                                    <a href="crear-publicacion.php" class="btn btn-primary">Crea tu primera publicación</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

