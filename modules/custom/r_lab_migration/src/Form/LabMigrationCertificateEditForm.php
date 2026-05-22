<?php

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LabMigrationCertificateEditForm extends FormBase {

  public function getFormId() {
    return 'lab_migration_certificate_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

$route_match = \Drupal::routeMatch();

$type = $route_match->getParameter('type');
$action = $route_match->getParameter('action');
$proposal_id = (int) $route_match->getParameter('proposal_id');
$certi_id = (int) $route_match->getParameter('certi_id');
    if ($type == "lm-proposer" && $action == "edit") {

      $details_list = \Drupal::database()->query(
        "SELECT * FROM {lab_migration_certificate} WHERE proposal_id = :prop_id AND id = :certi_id",
        [
          ":prop_id" => $proposal_id,
          ":certi_id" => $certi_id,
        ]
      )->fetchObject();

      if ($details_list && $details_list->type == "Proposer") {

        $form['name_title'] = [
          '#type' => 'select',
          '#title' => $this->t('Title'),
          '#options' => [
            'Dr.' => 'Dr.',
            'Prof.' => 'Prof.',
            'Mr.' => 'Mr.',
            'Mrs.' => 'Mrs.',
            'Ms.' => 'Ms.',
          ],
          '#default_value' => $details_list->name_title,
        ];

        $form['name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Name of Proposer'),
          '#default_value' => $details_list->name,
        ];

        $form['email_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Email'),
          '#default_value' => $details_list->email_id,
        ];

        $form['institute_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('College / Institute Name'),
          '#default_value' => $details_list->institute_name,
        ];

        $form['institute_address'] = [
          '#type' => 'textfield',
          '#title' => $this->t('College / Institute Address'),
          '#default_value' => $details_list->institute_address,
        ];

        $form['lab_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Lab name'),
          '#default_value' => $details_list->lab_name,
        ];

        $form['department'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Department'),
          '#default_value' => $details_list->department,
        ];

        $form['semester_details'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Semester details'),
          '#default_value' => $details_list->semester_details,
        ];

        $form['proposal_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Lab Proposal Id'),
          '#description' => $this->t('Find it in URL: r.fossee.in/lab-migration/lab-migration-run/{id}'),
          '#default_value' => $details_list->proposal_id,
        ];

        $form['certi_id'] = [
          '#type' => 'hidden',
          '#default_value' => $details_list->id,
        ];

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
        ];
      }
      else {
        $form['err_message'] = [
          '#type' => 'item',
          '#title' => $this->t('Message'),
          '#markup' => $this->t('Invalid information'),
        ];
      }
    }
    else {
      $form['err_message'] = [
        '#type' => 'item',
        '#title' => $this->t('Message'),
        '#markup' => $this->t('Invalid information'),
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();
    $v = $form_state->getValues();

    \Drupal::database()->update('lab_migration_certificate')
      ->fields([
        'uid' => $user->id(),
        'name_title' => trim($v['name_title']),
        'name' => trim($v['name']),
        'email_id' => trim($v['email_id']),
        'institute_name' => trim($v['institute_name']),
        'institute_address' => trim($v['institute_address']),
        'lab_name' => trim($v['lab_name']),
        'department' => trim($v['department']),
        'semester_details' => trim($v['semester_details']),
        'proposal_id' => trim($v['proposal_id']),
        'type' => 'Proposer',
        'creation_date' => time(),
      ])
      ->condition('id', $v['certi_id'])
      ->execute();

    \Drupal::messenger()->addStatus($this->t('Certificate updated successfully.'));

    return new RedirectResponse('/lab-migration/certificate');
  }

}