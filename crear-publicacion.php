<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
$categorias = $stmt->fetchAll();

$categoriaSeleccionada = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-lg-3">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <h1 class="mb-4">Crear Nueva Publicación</h1>

            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle"></i> Las publicaciones que crees serán visibles para todos los usuarios de Ballista.
            </div>

            <form id="publicacionForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                </div>

                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo ($categoriaSeleccionada == $categoria['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen (opcional)</label>
                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                    <div class="form-text">Tamaño máximo: 2MB. Formatos permitidos: JPG, PNG, GIF.</div>
                </div>

                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido</label>
                    <textarea class="form-control" id="contenido" name="contenido" rows="15" required></textarea>
                    <div class="form-text">Puedes usar HTML básico para dar formato a tu texto. Por ejemplo: &lt;b&gt;negrita&lt;/b&gt;, &lt;i&gt;cursiva&lt;/i&gt;, &lt;a href="url"&gt;enlace&lt;/a&gt;</div>
                </div>

                <div class="alert alert-danger d-none" id="formError"></div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="javascript:history.back()" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Publicar</button>
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
            window.location.href = 'index.php';
        } else {
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Error al procesar la solicitud. Inténtalo más tarde.';
        errorDiv.classList.remove('d-none');
    });
});
</script>

