<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\textbook_companion\Helper\CertificateHelper;

class VerifyCertificatesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'verify_certificates_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['qr_code'] = [
      '#type' => 'textfield',
      '#title' => t('Enter QR Code'),
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Verify'),
      '#ajax' => [
        'callback' => '::ajaxVerifyCallback',
        'progress' => [
          'message' => '',
        ],
      ],
    ];
    $form['displaytable'] = [
      '#type' => 'markup',
      '#prefix' => '<div><div id="displaytable" style="font-weight:bold;padding-top:10px">',
      '#suffix' => '</div></div>',
      '#markup' => $form_state->get('verification_markup') ?: '',
    ];
    return $form;
  }

  public function ajaxVerifyCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $markup = CertificateHelper::verifyQrCode($form_state->getValue('qr_code'));
    $response->addCommand(new HtmlCommand('#displaytable', $markup));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $markup = CertificateHelper::verifyQrCode($form_state->getValue('qr_code'));
    $form_state->set('verification_markup', $markup);
    $form_state->setRebuild(TRUE);
  }

}
