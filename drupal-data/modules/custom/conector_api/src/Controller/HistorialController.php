<?php

namespace Drupal\conector_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

class HistorialController extends ControllerBase
{

  public function mostrar()
  {
    // 1. Recuperar el token de la sesión (el mismo nombre que usas en LoginForm)
    $token = \Drupal::service('session')->get('mi_token_api');

    if (!$token) {
      return [
        '#markup' => $this->t('No tienes un token válido. Por favor, <a href="/api/entrar">inicia sesión</a>.'),
      ];
    }

    $client = \Drupal::httpClient();

    try {
      // 2. Llamada a la API
      $response = $client->get('http://api:3000/api/ver-historial', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
        ],
      ]);

      // 3. EXTRAER EL CONTENIDO CORRECTAMENTE
      // Usamos (string) para convertir el stream de Guzzle en texto plano
      $contenido = (string)$response->getBody();

      // 🛡️ Sanitizamos la salida para prevenir XSS
      $safe_contenido = Html::escape($contenido);

      return [
        '#type' => 'markup',
        '#markup' => '<h2>Contenido de introducir-texto.txt:</h2><pre>' . $safe_contenido . '</pre>',
        '#cache' => [
          'max-age' => 0, // Esto obliga a Drupal a recargar de la API siempre
        ], ];

    }
    catch (\GuzzleHttp\Exception\ClientException $e) {
      if ($e->getResponse()->getStatusCode() == 403 || $e->getResponse()->getStatusCode() == 401) {
        \Drupal::service('session')->remove('mi_token_api');
        \Drupal::messenger()->addWarning('Tu sesión en la API ha caducado. Por favor, inicia sesión de nuevo.');

        // CORRECCIÓN: Evitar devolver por Render Array y forzar el envío del header HTTP enviando el objeto de Symfony.
        $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/api/entrar');
        $response->send();
        exit();
      }
      return [
        '#markup' => $this->t('Error de la API: @message', ['@message' => $e->getMessage()]),
      ];
    }
    catch (\GuzzleHttp\Exception\RequestException $e) {
      // Si la API devuelve error (404, 500, etc), lo capturamos aquí
      return [
        '#markup' => $this->t('Error de la API: @message', ['@message' => $e->getMessage()]),
      ];
    }
    catch (\Exception $e) {
      // Cualquier otro error de PHP
      return [
        '#markup' => $this->t('Error inesperado: @message', ['@message' => $e->getMessage()]),
      ];
    }
  }



  public function saludar()
  {
    $token = \Drupal::service('session')->get('mi_token_api');

    if (!$token) {
      \Drupal::messenger()->addWarning('Debes loguearte primero.');
      $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/api/entrar');
      $response->send();
      exit();
    }

    $client = \Drupal::httpClient();
    try {
      $response = $client->get('http://api:3000/api/saludar', [
        'headers' => ['Authorization' => 'Bearer ' . $token]
      ]);

      $data = json_decode($response->getBody());

      // 🛡️ Sanitizamos los datos para prevenir XSS (ATAQUES DE INYECION)
      $safe_mensaje = Html::escape($data->mensaje);
      $safe_hora = Html::escape($data->hora);
      $safe_foto = UrlHelper::filterBadProtocol($data->foto);

      // Construimos un diseño sencillo con la foto y la hora
      $html = "
        <div style='text-align: center; border: 1px solid #ccc; padding: 20px; border-radius: 10px;'>
          <h1>" . $safe_mensaje . "</h1>
          <p style='font-size: 1.5em; color: #007bff;'>🕒 Hora del servidor API: <strong>" . $safe_hora . "</strong></p>
          <img src='" . $safe_foto . "' alt='Foto API' style='max-width: 100%; height: auto; border-radius: 5px; margin-top: 15px;'>
        </div>
      ";

      return [
        '#markup' => $html,
        '#cache' => ['max-age' => 0], // Importante para que la hora se actualice al refrescar
      ];
    }
    catch (\GuzzleHttp\Exception\ClientException $e) {
      if ($e->getResponse()->getStatusCode() == 403 || $e->getResponse()->getStatusCode() == 401) {
        \Drupal::service('session')->remove('mi_token_api');
        \Drupal::messenger()->addWarning('Tu sesión en la API ha caducado. Por favor, inicia sesión de nuevo.');

        // CORRECCIÓN REDIRECCIÓN
        $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/api/entrar');
        $response->send();
        exit();
      }
      return ['#markup' => 'Error de la API: ' . $e->getMessage()];
    }
    catch (\Exception $e) {
      return ['#markup' => 'No se pudo conectar: ' . $e->getMessage()];
    }
  }

  public function infoApis()
  {
    $html = "
      <div style='max-width: 800px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background-color: #f9f9f9; font-family: sans-serif;'>
        <h1 style='color: #2c3e50; text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 15px;'>¿Qué es una API?</h1>
        
        <p style='font-size: 1.2rem; line-height: 1.6; color: #444; margin-top: 20px;'>
          Las siglas <strong>API</strong> significan <em>Application Programming Interface</em> (Interfaz de Programación de Aplicaciones). 
          En términos sencillos, es un conjunto de reglas y protocolos que permite a distintas aplicaciones informáticas comunicarse entre sí.
        </p>

        <h2 style='color: #2980b9; margin-top: 30px;'>¿Para qué sirve una API?</h2>
        <ul style='font-size: 1.1rem; line-height: 1.8; color: #555; padding-left: 20px;'>
          <li><strong>Conexión de sistemas:</strong> Permite que un Frontend (como Drupal, React, o una App móvil) se comunique con un Backend (como nuestro servidor Node.js) sin importar en qué lenguaje estén escritos.</li>
          <li><strong>Automatización y eficiencia:</strong> Evita tener que programar todo desde cero. Puedes consumir la API de Google Maps para mostrar un mapa sin tener que programar un sistema GPS completo.</li>
          <li><strong>Seguridad:</strong> Actúa como una 'puerta'. La base de datos (PostgreSQL) está oculta y la API es la encargada de validar el token (JWT) antes de decidir si te entrega los datos o no.</li>
          <li><strong>Microservicios:</strong> Permite dividir aplicaciones gigantescas en partes pequeñas que se comunican entre sí a través de APIs (como estamos haciendo entre Node y Drupal).</li>
        </ul>

         <div style='background-color: #e8f4f8; border-left: 5px solid #3498db; padding: 15px; margin-top: 25px;'>
          <p style='margin:0; color: #2c3e50;'><strong>Ejemplo cotidiano:</strong> Piensa en un restaurante. Tú eres el cliente (Drupal/Frontend), la cocina es el servidor con la base de datos, y el camarero es la API. Tú no vas a la cocina, le das la orden al camarero (API), él se la lleva al cocinero y te devuelve tu comida (datos).</p>
        </div>
      </div>
    ";

    return [
      '#markup' => $html,
    ];
  }

}