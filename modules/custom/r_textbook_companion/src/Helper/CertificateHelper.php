<?php

namespace Drupal\textbook_companion\Helper;

class CertificateHelper {

  public static function verifyQrCode($qr_code) {
    $connection = \Drupal::database();

    $qr_row = $connection->select('textbook_companion_qr_code', 'tq')
      ->fields('tq', ['proposal_id'])
      ->condition('qr_code', $qr_code)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$qr_row || !$qr_row->proposal_id) {
      return '<b>Sorry ! The serial number you entered seems to be invalid. Please try again ! <b>';
    }

    $data2 = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('approval_status', 1)
      ->condition('proposal_id', $qr_row->proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $data3 = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $qr_row->proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$data2 || !$data3) {
      return '<b>Sorry ! The serial number you entered seems to be invalid. Please try again ! <b>';
    }

    $page_content = '';
    $page_content .= '<h4>Participation Details</h4><table><tr><td>Name</td>';
    $page_content .= '<td>' . $data3->full_name . '</td></tr>';
    $page_content .= '<tr><td>Project</td>';
    $page_content .= '<td>R Textbook Companion</td></tr>';
    $page_content .= '<tr><td>Books completed</td>';
    $page_content .= '<td>' . $data2->book . '</td></tr>';
    $page_content .= '<tr><td>Book Author</td>';
    $page_content .= '<td>' . $data2->author . '</td></tr>';
    $page_content .= '</table>';

    return $page_content;
  }

}
