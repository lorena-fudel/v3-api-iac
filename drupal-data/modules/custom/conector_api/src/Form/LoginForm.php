<?php
namespace Drupal\conector_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

class LoginForm extends FormBase {
  public function getFormId() {
    return 'conector_api_login_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['usuario'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Usuario de la API'),
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Contraseña'),
      '#required' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Entrar al Sistema'),
    ];
    return $form;
  }

    // En src/Form/LoginForm.php
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $client = \Drupal::httpClient();
    
    try {
      // 1. Solicitamos el token a la ruta /auth/login de tu app.js
      $response = $client->post('http://api:3000/auth/login', [
        'json' => [
          'username' => $form_state->getValue('usuario'),
          'password' => $form_state->getValue('password'),
        ],
      ]);

      $data = json_decode($response->getBody());

      if (isset($data->token)) {
        // 2. Guardamos el token en la sesión de Drupal
        \Drupal::service('session')->set('mi_token_api', $data->token);
        
        \Drupal::messenger()->addStatus('Autenticación exitosa con la API.  yess');
        $form_state->setRedirect('conector_api.historial');
      }
    } catch (\Exception $e) {
  // Esto te mostrará si es un problema de conexión o un error 401/500 de la API
  \Drupal::messenger()->addError('Error técnico --- : ' . $e->getMessage());
}
  }
}