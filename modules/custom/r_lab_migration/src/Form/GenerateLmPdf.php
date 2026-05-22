<?php

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;

class GenerateLmPdf extends FormBase {

  public function getFormId() {
    return 'generate_lm_pdf';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $extension_path_resolver = \Drupal::service('extension.path.resolver');
    $mpath = $extension_path_resolver->getPath('module', 'lab_migration');

    require_once $mpath . '/pdf/fpdf/fpdf.php';
    require_once $mpath . '/pdf/phpqrcode/qrlib.php';

    $database = \Drupal::database();
    $user = \Drupal::currentUser();

    $route_match = \Drupal::routeMatch();
    $proposal_id = (int) $route_match->getParameter('proposal_id');
    $lab_certi_id = (int) $route_match->getParameter('lab_certi_id');

    if ($proposal_id && $lab_certi_id) {

      $query3 = $database->query(
        "SELECT * FROM {lab_migration_certificate} WHERE proposal_id = :prop_id AND id = :certi_id",
        [
          ':prop_id' => $proposal_id,
          ':certi_id' => $lab_certi_id,
        ]
      );

      $data3 = $query3->fetchObject();

      if (!$data3) {
        \Drupal::messenger()->addError('Invalid certificate.');
        return;
      }

      /* ================= PROPOSER ================= */
      if ($data3->type == 'Proposer') {

        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->AddPage();

        $image_bg = $mpath . "/pdf/images/bg.png";
        $pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());

        $pdf->Rect(5, 5, 267, 207, 'D');
        $pdf->SetMargins(18, 1, 18);

        $pdf->SetFont('Times', 'BI', 12);
        $pdf->Ln(50);

        $pdf->Cell(240, 8, 'This is to certify that under the supervision/guidance of ' . $data3->name_title . ' ' . $data3->name . ',', 0, 1, 'C');

        $pdf->Cell(240, 8, 'from the ' . $data3->department . ',', 0, 1, 'C');

        $pdf->Cell(240, 8, $data3->institute_name . ', ' . $data3->institute_address, 0, 1, 'C');

        $pdf->Cell(240, 8, 'has successfully migrated the ' . $data3->lab_name, 0, 1, 'C');

        /* QR CODE */
        $tempDir = $mpath . "/pdf/temp_prcode/";

        $query = $database->select('lab_migration_certificate_qr_code', 'q')
          ->fields('q', ['qr_code'])
          ->condition('proposal_id', $proposal_id)
          ->execute()
          ->fetchObject();

        $UniqueString = $query->qr_code ?? '';

        if (empty($UniqueString)) {
          $UniqueString = uniqid();

          $database->insert('lab_migration_certificate_qr_code')
            ->fields([
              'proposal_id' => $proposal_id,
              'qr_code' => $UniqueString,
              'certificate_id' => $lab_certi_id,
              'gen_date' => time(),
            ])
            ->execute();
        }

        $codeContents = "https://r.fossee.in/lab-migration/certificates/verify/" . $UniqueString;

        $pngFile = $tempDir . 'generated_qrcode.png';
        \QRcode::png($codeContents, $pngFile);

        $pdf->Image($pngFile, 120, 150, 30);

        $filename = 'certificate.pdf';
        $file = $mpath . '/pdf/temp_certificate/' . $filename;

        $pdf->Output($file, 'F');

        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=" . $filename);
        readfile($file);
        unlink($file);
        exit;
      }

      /* ================= PARTICIPANT ================= */
      elseif ($data3->type == 'Participant') {

        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->AddPage();

        $pdf->SetFont('Times', 'BI', 20);
        $pdf->Cell(240, 10, $data3->name_title . ' ' . $data3->name, 0, 1, 'C');

        $filename = 'participation_certificate.pdf';
        $file = $mpath . '/pdf/temp_certificate/' . $filename;

        $pdf->Output($file, 'F');

        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=" . $filename);
        readfile($file);
        unlink($file);
        exit;
      }

      else {
        return new RedirectResponse('/lab-migration/certificate');
      }
    }
    else {
      \Drupal::messenger()->addMessage('Your lab is still under review.');
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}

}