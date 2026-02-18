<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT p.id, p.titulo, p.contenido, p.imagen, p.id_categoria, p.id_usuario
    FROM publicaciones p
    WHERE p.id = ?
");
$stmt->execute([$id]);
$publicacion = $stmt->fetch();

if (!$publicacion || $publicacion['id_usuario'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = $stmt->fetchAll();
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-lg-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h1 class="mb-4">Editar Publicación</h1>

            <form id="publicacionForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $publicacion['id']; ?>">

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($publicacion['titulo']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo ($publicacion['id_categoria'] == $categoria['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen (opcional)</label>
                    <?php if ($publicacion['imagen']): ?>
                        <div class="mb-2">
                            <img src="<?php echo htmlspecialchars($publicacion['imagen']); ?>" class="img-thumbnail" style="max-height: 200px;" alt="Imagen actual">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="eliminarImagen" name="eliminar_imagen">
                                <label class="form-check-label" for="eliminarImagen">
                                    Eliminar imagen actual
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                    <div class="form-text">Tamaño máximo: 2MB. Formatos permitidos: JPG, PNG, GIF.</div>
                </div>

                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido</label>
                    <textarea class="form-control" id="contenido" name="contenido" rows="15" required><?php echo htmlspecialchars($publicacion['contenido']); ?></textarea>
                    <div class="form-text">Puedes usar HTML básico para dar formato a tu texto. Por ejemplo: &lt;b&gt;negrita&lt;/b&gt;, &lt;i&gt;cursiva&lt;/i&gt;, &lt;a href="url"&gt;enlace&lt;/a&gt;</div>
                </div>

                <div class="alert alert-danger d-none" id="formError"></div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="ver-publicacion.php?id=<?php echo $publicacion['id']; ?>" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.getElementById('publicacionForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = this;
    const errorDiv = document.getElementById('formError');
    const formData = new FormData(form);

    fetch('api/publicaciones.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'ver-publicacion.php?id=' + <?php echo $publicacion['id']; ?>;
        } else {
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Error al procesar la solicitud. Inténtalo de nuevo más tarde.';
        errorDiv.classList.remove('d-none');
    });
});
</script>



