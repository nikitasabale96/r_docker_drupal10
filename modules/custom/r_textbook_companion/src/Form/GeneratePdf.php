<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\GeneratePdf.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class GeneratePdf extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_pdf';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    $proposal_id = $proposal_id ?? \Drupal::routeMatch()->getParameter('proposal_id');
    $proposal_id = (int) $proposal_id;

    $module_path = \Drupal::service('extension.list.module')->getPath('textbook_companion');
    $module_root = \Drupal::root() . '/' . $module_path;

    require_once $module_root . '/pdf/fpdf/fpdf.php';
    require_once $module_root . '/pdf/phpqrcode/qrlib.php';

    $connection = \Drupal::database();

    $data2 = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('approval_status', 1)
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $data3 = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $count_query = $connection->select('textbook_companion_example', 'tce');
    $count_query->leftJoin('textbook_companion_chapter', 'tcc', 'tce.chapter_id = tcc.id');
    $count_query->leftJoin('textbook_companion_preference', 'tcpe', 'tcc.preference_id = tcpe.id');
    $count_query->leftJoin('textbook_companion_proposal', 'tcpo', 'tcpe.proposal_id = tcpo.id');
    $count_query->condition('tcpo.proposal_status', 3);
    $count_query->condition('tce.approval_status', 1);
    $count_query->condition('tcpo.id', $proposal_id);
    $count_query->addExpression('COUNT(tce.id)', 'example_count');
    $data4 = $count_query->execute()->fetchObject();

    if (empty($data4) || (int) $data4->example_count === 0) {
      $this->messenger()->addError($this->t('Certificate is not available'));
      return [];
    }

    $number_of_example = (int) $data4->example_count;

    $pdf = new \FPDF('L', 'mm', 'Letter');
    if (!$pdf) {
      echo "Error!";
    }

    $author = $data2->author ?? '';
    $path = $module_root;

    $pdf->SetTextColor(129, 80, 47);
    $pdf->AddPage();
    $image_bg = $path . '/pdf/images/bg_cert.png';
    $pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
    $pdf->SetMargins(18, 1, 18);
    $pdf->Ln(25);
    $pdf->SetFont('Times', 'I', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(320, 10, 'This is to certify that', '0', '1', 'C');
    $pdf->Ln(0);
    $pdf->SetFont('Times', 'I', 20);
    $pdf->SetTextColor(30, 100, 182);
    $pdf->Cell(320, 12, $data3->full_name ?? '', '0', '1', 'C');
    $pdf->Ln(0);
    $pdf->SetFont('Times', 'I', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(320, 10, wordwrap('from "' . ($data3->university ?? '') . '" has successfully completed a', 80), '0', 'C');
    $pdf->Ln(0);
    $pdf->MultiCell(320, 10, 'R textbook companion by coding "' . $number_of_example . '" examples from the book', '0', 'C');
    $pdf->Ln(0);
    $pdf->SetTextColor(30, 100, 182);
    $pdf->Cell(320, 12, $data2->book ?? '', '0', '1', 'C');
    $pdf->Ln(0);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(320, 10, 'written by "' . $author . '".', '0', 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(320, 10, 'The work done is available at', '0', '1', 'C');
    $pdf->Cell(320, 4, '', '0', '1', 'C');
    $pdf->SetX(155);
    $pdf->SetFont('', 'U');
    $pdf->SetTextColor(30, 100, 182);
    $pdf->write(0, 'https://r.fossee.in/', 'https://r.fossee.in/');
    $pdf->Ln(0);

    $temp_dir = $path . '/pdf/temp_prcode/';
    $qr_data = $connection->select('textbook_companion_qr_code', 'tqc')
      ->fields('tqc')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $db_string = $qr_data->qr_code ?? '';
    if ($db_string === '' || $db_string === 'null') {
      $unique_string = $this->generateRandomString();
      $connection->insert('textbook_companion_qr_code')
        ->fields([
          'proposal_id' => $proposal_id,
          'qr_code' => $unique_string,
        ])
        ->execute();
    }
    else {
      $unique_string = $db_string;
    }

    $code_contents = 'https://r.fossee.in/textbook-companion/certificates/verify/' . $unique_string;
    $file_name = 'generated_qrcode.png';
    $png_absolute_file_path = $temp_dir . $file_name;
    $sign1 = $path . '/pdf/images/sign1.png';
    $sign2 = $path . '/pdf/images/sign2.png';

    \QRcode::png($code_contents, $png_absolute_file_path);

    $pdf->SetY(85);
    $pdf->SetX(320);
    $pdf->Ln(10);
    $pdf->Image($sign1, $pdf->GetX() + 60, $pdf->GetY() + 45, 85, 0);
    $pdf->Image($sign2, $pdf->GetX() + 160, $pdf->GetY() + 45, 85, 0);
    $pdf->Image($png_absolute_file_path, $pdf->GetX() + 15, $pdf->GetY() + 70, 30, 0);
    $fossee = $path . '/pdf/images/fossee.png';
    $mhrd = $path . '/pdf/images/mhrd.png';
    $pdf->Image($fossee, $pdf->GetX() + 80, $pdf->GetY() + 80, 50, 0);
    $pdf->Image($mhrd, $pdf->GetX() + 180, $pdf->GetY() + 80, 40, 0);
    $pdf->Ln(2);
    $ftr_line = $path . '/pdf/images/ftr_line.png';
    $pdf->Image($ftr_line, $pdf->GetX(), $pdf->GetY() + 105, 250, 0);
    $pdf->SetFont('Times', 'I', 15);
    $pdf->SetLeftMargin(40);
    $pdf->Ln(62);
    $pdf->Cell(320, 8, $unique_string, '0', '1', 'L');
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);

    $filename = str_replace(' ', '-', ($data3->full_name ?? '')) . '-R_TBC_Certificate.pdf';
    $file = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
    $pdf->Output($file, 'F');

    ob_clean();
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: public');
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Length: ' . filesize($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Pragma: no-cache');
    flush();

    $fp = fopen($file, 'r');
    while (!feof($fp)) {
      echo fread($fp, filesize($file));
      flush();
    }
    ob_end_flush();
    ob_clean();
    fclose($fp);
    unlink($file);

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit handling; PDF generation is performed during build.
  }

  private function generateRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
      $random_string .= $characters[random_int(0, $characters_length - 1)];
    }
    return $random_string;
  }

}
