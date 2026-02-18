# ⚔️ Ballista
# Plataforma web sobre historia militar

Ballista es una plataforma web y red social temática centrada en historia militar, estrategia, liderazgo y armamento. Permite a los usuarios crear y compartir contenido especializado, interactuar mediante comentarios y apoyar el proyecto a través de un sistema de mecenazgo con ventajas exclusivas.

El proyecto ha sido desarrollado con una arquitectura clara, tecnologías web clásicas y buenas prácticas de seguridad y mantenimiento.

🧭 Características principales

Registro y autenticación de usuarios

Creación y edición de publicaciones enriquecidas (editor WYSIWYG)

Categorización de contenido por temáticas:

Estrategia

Armamento

Liderazgo

Historia militar

Sistema de comentarios en publicaciones

Comentarios asociados a usuarios

Visualización cronológica

Validación en cliente y servidor

Sistema de mecenazgo con niveles:

Hoplita (5 €)

Centurión (10 €)

Generación automática de facturas en PDF

Envío de facturas por correo electrónico

Panel de usuario con:

Información personal

Publicaciones creadas

Comentarios realizados

Estado de suscripción

Interfaz responsive y diseño cuidado

💬 Sistema de comentarios

La plataforma incorpora un sistema de comentarios integrado en cada publicación:

Creación de comentarios mediante formularios dinámicos

Asociación del comentario al usuario autenticado

Relación directa publicación–comentarios en base de datos

Protección contra inyección SQL mediante PDO

Validación de contenido tanto en frontend como en backend

Visualización optimizada para dispositivos móviles

Este sistema fomenta la interacción y el debate histórico dentro de la comunidad.

⚙️ Tecnologías utilizadas
Frontend

Bootstrap 5.3.2

Bootstrap Icons 1.11.1

jQuery 3.6.0

Summernote 0.8.18

JavaScript nativo + Fetch API

CSS personalizado con media queries

Backend

PHP (programación orientada a objetos)

MySQL / MariaDB

PDO para acceso seguro a base de datos

TCPDF para generación de PDFs

PHPMailer para envío de correos electrónicos

🏗️ Arquitectura

Patrón MVC (Modelo–Vista–Controlador) simplificado

Separación clara de responsabilidades

Patrones de diseño aplicados:

Singleton para la conexión a base de datos

Factory para la creación de objetos

Observer para la gestión de eventos (facturas, correos)

🔐 Seguridad

Cifrado de contraseñas con bcrypt

Uso de consultas preparadas con PDO

Validación de datos en cliente y servidor

Control de sesiones

Protección básica contra accesos no autorizados

Gestión segura de formularios (publicaciones, comentarios, mecenazgo)

🧪 Pruebas realizadas

Pruebas unitarias

Inicio de sesión

Generación de PDFs

Envío de correos

Creación de comentarios

Pruebas de integración

Flujo completo:
Registro → Publicación → Comentarios → Mecenazgo → Factura

Pruebas de usuario

Experiencia real desde el registro hasta la suscripción

Pruebas básicas

Rendimiento

Seguridad

🚀 Estado del proyecto

El proyecto se encuentra funcional y estable, con margen para ampliaciones futuras como:

Sistema de moderación

Edición/eliminación de comentarios

Notificaciones

Likes o valoraciones

Roles de usuario
