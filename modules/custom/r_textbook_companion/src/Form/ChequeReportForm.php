<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeReportForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::database()->select('textbook_companion_proposal', 'p');
    $query->join('textbook_companion_cheque', 'c', 'p.id = c.proposal_id');
    $query->fields('p', ['full_name']);
    $query->fields('c', ['address_con', 'cheque_no', 'cheque_dispatch_date']);
    $query->condition('c.address_con', 'Submitted');
    $rows = $query->execute()->fetchAll();

    if (empty($rows)) {
      $this->messenger()->addError($this->t('No records found for the report.'));
      return [];
    }

    $response = new StreamedResponse(function () use ($rows) {
      $handle = fopen('php://output', 'wb');
      $header = [
        'Name Of The Student',
        'Application Form Status',
        'Cheque No',
        'Cheque Clearance Date',
      ];
      fputcsv($handle, $header);
      foreach ($rows as $row) {
        fputcsv($handle, [
          $row->full_name,
          $row->address_con,
          $row->cheque_no,
          $row->cheque_dispatch_date,
        ]);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="Report.csv"');
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');

    $form_state->setResponse($response);
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
