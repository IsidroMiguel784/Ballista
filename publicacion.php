<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Verifico si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar esta acción']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createPublicacion();
        break;
    case 'update':
        updatePublicacion();
        break;
    case 'delete':
        deletePublicacion();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        exit;
}

function createPublicacion() {
    global $pdo;
    
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria = (int)($_POST['categoria'] ?? 0);
    
    // Validaciones
    if (empty($titulo) || empty($contenido) || $categoria <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }
    
    // Proceso imagen si existe
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen = processImage($_FILES['imagen']);
        if ($imagen === false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error al procesar la imagen']);
            exit;
        }
    }
    
    // Inserto la publicación
    try {
        $stmt = $pdo->prepare("
            INSERT INTO publicaciones (titulo, contenido, imagen, id_usuario, id_categoria, fecha_publicacion) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$titulo, $contenido, $imagen, $_SESSION['user_id'], $categoria]);
        
        $publicacionId = $pdo->lastInsertId();
        
        header('Location: ../publicacion.php?id=' . $publicacionId);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al crear la publicación: ' . $e->getMessage()]);
        exit;
    }
}

function updatePublicacion() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria = (int)($_POST['categoria'] ?? 0);
    
    // Validaciones
    if ($id <= 0 || empty($titulo) || empty($contenido) || $categoria <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }
    
    // Verifico que la publicación existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id, imagen FROM publicaciones WHERE id = ? AND id_usuario = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $publicacion = $stmt->fetch();
    
    if (!$publicacion) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar esta publicación']);
        exit;
    }
    
    // Proceso imagen si existe
    $imagen = $publicacion['imagen'];
    
    // Si se marca eliminar imagen
    if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] == 'on') {
        if ($imagen && file_exists('../' . $imagen)) {
            unlink('../' . $imagen);
        }
        $imagen = null;
    }
    // Si se sube una nueva imagen
    elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $nuevaImagen = processImage($_FILES['imagen']);
        if ($nuevaImagen === false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error al procesar la imagen']);
            exit;
        }
        
        // Eliminar imagen anterior si existe
        if ($imagen && file_exists('../' . $imagen)) {
            unlink('../' . $imagen);
        }
        
        $imagen = $nuevaImagen;
    }
    
    // Actualizo la publicación
    try {
        $stmt = $pdo->prepare("
            UPDATE publicaciones 
            SET titulo = ?, contenido = ?, imagen = ?, id_categoria = ?, fecha_actualizacion = NOW()
            WHERE id = ? AND id_usuario = ?
        ");
        $stmt->execute([$titulo, $contenido, $imagen, $categoria, $id, $_SESSION['user_id']]);
        
        header('Location: ../publicacion.php?id=' . $id);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la publicación: ' . $e->getMessage()]);
        exit;
    }
}

function deletePublicacion() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    
    // Validaciones
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID de publicación no válido']);
        exit;
    }
    
    // Verifico que la publicación existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id, imagen, id_categoria FROM publicaciones WHERE id = ? AND id_usuario = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $publicacion = $stmt->fetch();
    
    if (!$publicacion) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar esta publicación']);
        exit;
    }
    
    // Elimino imagen si existe
    if ($publicacion['imagen'] && file_exists('../' . $publicacion['imagen'])) {
        unlink('../' . $publicacion['imagen']);
    }
    
    // Elimino la publicación
    try {
        $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        header('Location: ../categoria.php?slug=' . getCategoriaSlug($publicacion['id_categoria']));
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la publicación: ' . $e->getMessage()]);
        exit;
    }
}

function processImage($file) {
    // Valido tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Valido tamaño (2MB máximo)
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }
    
    // Creo directorio si no existe
    $uploadDir = '../uploads/images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Genero nombre único
    $filename = uniqid() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $filename;
    
    // Muevo archivo
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return 'uploads/images/' . $filename;
    }
    
    return false;
}

function getCategoriaSlug($categoriaId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT slug FROM categorias WHERE id = ?");
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch();
    
    return $categoria ? $categoria['slug'] : 'estrategia';
}
?>



