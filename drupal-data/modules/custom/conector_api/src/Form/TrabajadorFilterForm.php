<?php

namespace Drupal\conector_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Formulario de filtrado para la lista de trabajadores de GLPI.
 */
class TrabajadorFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trabajador_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Usamos GET para que los filtros sean parte de la URL
    $form['#method'] = 'get';

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
        'style' => 'margin-bottom: 25px; background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #0071bc;',
      ],
    ];

    // Campo de Nombre
    $form['filters']['nombre'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
      '#default_value' => \Drupal::request()->query->get('nombre'),
      '#size' => 20,
      '#attributes' => [
        'placeholder' => $this->t('BUSCAR NOMBRE...'),
        'style' => 'text-transform: uppercase;', // Visualmente en mayúsculas
      ],
    ];

    // Desplegable de Departamentos (Valores en Mayúsculas para la API)
    $form['filters']['dept'] = [
      '#type' => 'select',
      '#title' => $this->t('Departamento'),
      '#options' => [
        '' => $this->t('- Todos -'),
        'SERVICIOS INFORMÁTICOS' => $this->t('Servicios Informáticos'),
        'ADMINISTRACIÓN' => $this->t('Administración'),
        'MANTENIMIENTO' => $this->t('Mantenimiento'),
        'INVESTIGACIÓN' => $this->t('Investigación'),
      ],
      '#default_value' => \Drupal::request()->query->get('dept'),
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['style' => 'display: inline-block; margin-left: 10px;'],
    ];

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filtrar'),
      '#button_type' => 'primary',
    ];

    $form['filters']['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Limpiar'),
      '#url' => Url::fromRoute('conector_api.lista_trabajadores'),
      '#attributes' => ['class' => ['button'], 'style' => 'margin-left: 10px;'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No requiere lógica, Drupal redirige con los parámetros GET.
  }

}