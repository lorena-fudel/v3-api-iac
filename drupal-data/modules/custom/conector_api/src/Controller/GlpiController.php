<?php

namespace Drupal\conector_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class GlpiController extends ControllerBase {

  protected $httpClient;

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  public function buscarUsuarioPorEmail($email) {
    $base_url   = getenv('GLPI_BASE_URL');
    $app_token  = getenv('GLPI_APP_TOKEN');
    $user_token = getenv('GLPI_USER_TOKEN');

    try {
      // 1. Iniciar Sesión
      $res_session = $this->httpClient->get($base_url . '/initSession', [
        'headers' => [
          'App-Token'     => $app_token,
          'Authorization' => 'user_token ' . $user_token,
        ],
      ]);
      $session_token = json_decode($res_session->getBody())->session_token;

      // 2. BUSCAR POR EMAIL (Campo 5 según tu Postman)
      $query = [
        'criteria[0][field]'      => 5, // 5 es el ID del campo Email
        'criteria[0][searchtype]' => 'contains',
        'criteria[0][value]'      => $email,
      ];

      $res_search = $this->httpClient->get($base_url . '/search/User', [
        'headers' => [
          'App-Token'     => $app_token,
          'Session-Token' => $session_token,
        ],
        'query' => $query,
      ]);

      $search_results = json_decode($res_search->getBody(), TRUE);

      // 3. Cerrar Sesión inmediatamente
      $this->httpClient->get($base_url . '/killSession', [
        'headers' => ['App-Token' => $app_token, 'Session-Token' => $session_token],
      ]);

      // 4. Procesar resultados
      if (empty($search_results['data'])) {
        return ['#markup' => "<h3>No se encontró al usuario con email: $email</h3>"];
      }

      $user = $search_results['data'][0];

      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['glpi-user-info']],
        'title' => ['#markup' => "<h2>Datos de GLPI para: $email</h2>"],
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            "Nombre de usuario (Login): " . $user[1],
            "Nombre Completo: " . $user[34],
            "Correo electrónico: " . $user[5],
            "ID Interno GLPI: " . $user[2],
          ],
        ],
      ];

    } catch (GuzzleException $e) {
      return ['#markup' => "Error de conexión: " . $e->getMessage()];
    }
  }

  /**
 * Obtiene todas las tareas asignadas a un ID de usuario concreto.
 */
  /**
 * Obtiene todas las tareas asignadas a un ID de usuario concreto.
 */
    public function mostrarTareasUsuario($id_usuario) {
    $base_url   = getenv('GLPI_BASE_URL');
    $app_token  = getenv('GLPI_APP_TOKEN');
    $user_token = getenv('GLPI_USER_TOKEN');

    try {
      // 1. Iniciar Sesión
      $res_session = $this->httpClient->get($base_url . '/initSession', [
        'headers' => [
          'App-Token'     => $app_token,
          'Authorization' => 'user_token ' . $user_token,
        ],
      ]);
      $session_token = json_decode($res_session->getBody())->session_token;

      // 2. BUSCAR TAREAS - Usamos el campo 5 (Técnico) según tu JSON
      $query = [
        'criteria[0][field]'      => 5, // <--- TU NÚMERO REAL PARA TÉCNICO
        'criteria[0][searchtype]' => 'equals',
        'criteria[0][value]'      => $id_usuario,
      ];

      $res_tasks = $this->httpClient->get($base_url . '/search/TicketTask', [
        'headers' => [
          'App-Token'     => $app_token,
          'Session-Token' => $session_token,
        ],
        'query' => $query,
      ]);

      $task_results = json_decode($res_tasks->getBody(), TRUE);

      // 3. Cerrar Sesión
      $this->httpClient->get($base_url . '/killSession', [
        'headers' => ['App-Token' => $app_token, 'Session-Token' => $session_token],
      ]);

      // 4. Procesar resultados
      if (empty($task_results['data'])) {
        return ['#markup' => "<h3>No hay tareas asignadas al técnico ID: $id_usuario</h3>"];
      }

      $items = [];
      foreach ($task_results['data'] as $task) {
        // Mapeamos según tu JSON: 1=Descripción, 3=Fecha, 7=Estado
        $descripcion = isset($task[1]) ? strip_tags($task[1]) : 'Sin descripción';
        $fecha       = $task[3] ?? 'Sin fecha';
        $estado_id   = $task[7] ?? 'N/A';
        
        // Creamos una línea bonita para cada tarea
        $items[] = "<strong>[$fecha]</strong> (Estado: $estado_id) - $descripcion";
      }

      return [
        '#theme' => 'item_list',
        '#title' => $this->t('Tareas de GLPI para el técnico @id', ['@id' => $id_usuario]),
        '#items' => $items,
      ];

    } catch (GuzzleException $e) {
      return ['#markup' => "Error: " . $e->getMessage()];
    }
  }

  /**
 * Obtiene la lista de trabajadores (usuarios) con su email e ID.
 */
 
  /**
   * Lista trabajadores con paginación de servidor y filtros de búsqueda.
   */
  /**
   * Lista trabajadores únicamente con paginación.
   */
  public function listarTrabajadoresIac(Request $request) {
    $base_url   = getenv('GLPI_BASE_URL');
    $app_token  = getenv('GLPI_APP_TOKEN');
    $user_token = getenv('GLPI_USER_TOKEN');

    // 1. Parámetros de paginación
    $limit        = (int) $request->query->get('limit', 20);
    $current_page = (int) $request->query->get('page', 0);
    $start        = $current_page * $limit;
    $end          = $start + $limit;

    try {
      // Iniciar Sesión en GLPI
      $res_session = $this->httpClient->get($base_url . '/initSession', [
        'headers' => ['App-Token' => $app_token, 'Authorization' => 'user_token ' . $user_token]
      ]);
      $session_token = json_decode($res_session->getBody())->session_token;

      // 2. CONSULTA DIRECTA (Sin filtros/criteria)
      $query = [
        'forcedisplay[0]' => 2,  // ID
        'forcedisplay[1]' => 5,  // Email
        'forcedisplay[2]' => 34, // Nombre completo
        'forcedisplay[3]' => 80, // Departamento
        'range'           => "$start-$end",
        'get_full_count'  => 'true',
      ];

      $res_users = $this->httpClient->get($base_url . '/search/User', [
        'headers' => ['App-Token' => $app_token, 'Session-Token' => $session_token],
        'query' => $query,
      ]);

      // Obtener el total real para calcular páginas
      $total_records = 0;
      if ($range_header = $res_users->getHeader('Content-Range')) {
        $parts = explode('/', $range_header[0]);
        $total_records = (int) end($parts);
      }

      $data = json_decode($res_users->getBody(), TRUE);
      
      // Cerrar Sesión
      $this->httpClient->get($base_url . '/killSession', [
        'headers' => ['App-Token' => $app_token, 'Session-Token' => $session_token]
      ]);

      // --- 3. RENDERIZADO ---

      $build['status_info'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--status']],
        'text' => ['#markup' => $this->t('Mostrando página <strong>@p</strong> de @total trabajadores.', [
          '@p' => $current_page + 1,
          '@total' => $total_records
        ])],
      ];

      // Tabla de datos
      $build['tabla'] = [
        '#type' => 'table',
        '#header' => [$this->t('ID'), $this->t('Nombre'), $this->t('Departamento'), $this->t('Email')],
        '#rows' => [],
        '#empty' => $this->t('No hay trabajadores registrados.'),
      ];

      if (!empty($data['data'])) {
        foreach ($data['data'] as $user) {
          $build['tabla']['#rows'][] = [$user[2], $user[34], $user[80], $user[5]];
        }
      }

      // 4. BOTONES DE PAGINACIÓN (Anterior / Siguiente)
      $pager_items = [];
      
      if ($current_page > 0) {
        $pager_items[] = [
          '#type' => 'link',
          '#title' => $this->t('« Anterior'),
          '#url' => \Drupal\Core\Url::fromRoute('conector_api.lista_trabajadores', [], ['query' => ['page' => $current_page - 1, 'limit' => $limit]]),
          '#attributes' => ['class' => ['button']],
        ];
      }

      $pager_items[] = ['#markup' => '<span style="padding: 0 20px;">' . $this->t('Página @p', ['@p' => $current_page + 1]) . '</span>'];

      if ($end < $total_records) {
        $pager_items[] = [
          '#type' => 'link',
          '#title' => $this->t('Siguiente »'),
          '#url' => \Drupal\Core\Url::fromRoute('conector_api.lista_trabajadores', [], ['query' => ['page' => $current_page + 1, 'limit' => $limit]]),
          '#attributes' => ['class' => ['button']],
        ];
      }

      $build['paginador'] = [
        '#type' => 'container',
        '#attributes' => ['style' => 'text-align:center; margin-top:30px;'],
        'links' => $pager_items,
      ];

      $build['#cache']['max-age'] = 0;

      return $build;

    } catch (\Exception $e) {
      return ['#markup' => "Error al conectar con la API: " . $e->getMessage()];
    }
  }


}