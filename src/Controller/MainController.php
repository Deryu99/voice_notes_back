<?php

namespace App\Controller;

use App\Service\NoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    private array $notes = [
        [
            'id' => 1,
            'title' => 'Ideas para el MVP de la app de notas',
            'summary' => 'Priorizar: editor markdown, tags, búsqueda rápida y modo oscuro. Dejar para v2 el compartir notas y colaboración en tiempo real.',
            'tags' => ['proyecto', 'startup', 'mvp', '2026'],
            'createdAt' => '03-03-2026',
            'updatedAt' => '10-03-2026',
        ],
        [
            'id' => 2,
            'title' => 'Libros por leer en 2026',
            'summary' => '• Atomic Habits (ya empezado)\n• The Mom Test\n• Clean Architecture\n• Staff Engineer\n• Slow Productivity (Cal Newport)',
            'tags' => ['lectura', 'desarrollo personal', 'tech'],
            'createdAt' => '28-02-2026',
        ],
        [
            'id' => 3,
            'title' => 'Compras importantes Q2 2026',
            'summary' => 'Monitor 27" 4K (~350€), teclado Keychron Q1 Pro, suscripción Superhuman o similar, segundo monitor brazo ergonómico',
            'tags' => ['compras', 'productividad', 'setup'],
            'createdAt' => '12-03-2026',
            'color' => '#fff3cd', // amarillo suave (opcional)
        ],
        [
            'id' => 4,
            'title' => 'Rutina de gimnasio actual',
            'summary' => 'Lunes: Push\nMartes: Pull\nMiércoles: Piernas o descanso\nJueves: Push\nViernes: Pull\nSábado: Full body o cardio\nDomingo: descanso activo',
            'tags' => ['salud', 'gym', 'rutina'],
            'createdAt' => '15-01-2026',
            'updatedAt' => '14-03-2026',
        ],
        [
            'id' => 5,
            'title' => 'Errores caros que he cometido programando',
            'summary' => '1. No hacer backup antes de migración masiva\n2. Usar SELECT * en producción\n3. No versionar archivos .env\n4. Pushear credenciales a GitHub (dos veces 😅)\n5. Ignorar tests en features críticas',
            'tags' => ['programación', 'lecciones', 'fail'],
            'createdAt' => '09-02-2026',
        ],
        [
            'id' => 6,
            'title' => 'Frases motivacionales rápidas',
            'summary' => '• Done is better than perfect\n• Progress > perfection\n• Focus on the next most important thing\n• You are not behind, you are just early for the next chapter',
            'tags' => ['motivación', 'mindset'],
            'createdAt' => '01-03-2026',
        ],
        [
            'id' => 7,
            'title' => 'Meeting con el cliente – 17 mar 2026',
            'summary' => 'Preparar:\n- Demo del dashboard\n- Explicar limitaciones actuales de exportación\n- Preguntar prioridad real: mobile vs reports\n- Pedir acceso a más datos de prueba',
            'tags' => ['trabajo', 'reunión', 'cliente'],
            'createdAt' => '15-03-2026',
            'pinned' => true, // ← podría usar este campo en el futuro
        ],
        [
            'id' => 8,
            'title' => 'Receta – Bowl de salmón y aguacate',
            'summary' => 'Ingredientes (1 persona):\n- 120g salmón fresco\n- ½ aguacate\n- 80g arroz sushi o basmati\n- Edamame, pepino, zanahoria rallada\n- Salsa: soja + sriracha + miel + limón',
            'tags' => ['recetas', 'comida sana'],
            'createdAt' => '05-03-2026',
        ],
    ];

    public function __construct() {}

    #[Route('/')]
    public function Homepage(): Response
    {
        // Opcional: ordenar por fecha descendente (más nuevas primero)
        usort($this->notes, function ($a, $b) {
            return strtotime($b['createdAt']) - strtotime($a['createdAt']);
        });

        return $this->render('main/homepage.html.twig', [
            'notes' => $this->notes,
            'conversation' => '',
            'selectedNote' => false,
        ]);
    }
}
