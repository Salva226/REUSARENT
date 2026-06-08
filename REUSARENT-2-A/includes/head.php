<?php
// --- ARCHIVO: includes/head.php ---
// Este archivo lo incluyo en TODAS las páginas dentro de la etiqueta <head>.
// Así no tengo que copiar y pegar los links a las fuentes y librerías cada vez que creo un archivo nuevo.
?>
<meta charset="UTF-8">

<!-- Esto es vital para que la web sea "responsive" y se vea bien en móviles sin que la gente tenga que hacer zoom -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<!-- El icono de la pestañita del navegador -->
<link rel="icon" href="./img/logo.PNG">

<!-- 1. LIBRERÍA DE ICONOS (Phosphor Icons) -->
<!-- Aquí llamo a la librería que uso para dibujar los iconos de casita, usuario, corazón, etc. -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<!-- 2. TAILWIND CSS -->
<!-- Cargo Tailwind por CDN. En producción se suele compilar, pero así me resulta más fácil prototipar rápido -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- 3. FUENTES DE GOOGLE -->
<!-- Me conecto a Google Fonts para traerme la fuente 'Outfit', que le da ese toque moderno a la app -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- 4. CONFIGURACIÓN PERSONALIZADA DE TAILWIND -->
<script>
    // Aquí le digo a Tailwind: "Oye, añade mi fuente Outfit y guárdate mi color turquesa (brand) como color principal".
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Outfit', 'sans-serif'],
          },
          colors: {
            brand: {
              DEFAULT: '#09b6a2',
              light: '#28cfbb',
              dark: '#068f80'
            }
          }
        }
      }
    }
</script>

<!-- 5. ESTILOS GLOBALES PROPIOS -->
<style>
    body {
        font-family: 'Outfit', sans-serif; /* Fuerza la fuente a todo el cuerpo */
        background-color: #f9fafb; /* Color de fondo gris clarito (estilo app móvil) */
        -webkit-tap-highlight-color: transparent; /* Quita el destello azul feo al tocar botones en el móvil */
    }
    
    /* Clases personalizadas para ocultar las barras de scroll en listas horizontales (como las categorías) */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Esta clase es un salvavidas: le da un colchón de espacio abajo del todo a la página 
       para que la barra de navegación del móvil no tape el contenido importante. */
    .pb-safe { padding-bottom: max(6rem, env(safe-area-inset-bottom)); }
</style>

<!-- 6. LIBRERÍA SWEETALERT2 -->
<!-- Para mostrar alertas y mensajes bonitos en vez de los feos del navegador por defecto -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
