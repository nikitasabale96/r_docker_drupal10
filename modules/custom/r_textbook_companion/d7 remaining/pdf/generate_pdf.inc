<?php
function generate_pdf()
  {
    $mpath = drupal_get_path('module', 'textbook_companion');
    require($mpath.'/pdf/fpdf/fpdf.php');
    require($mpath.'/pdf/phpqrcode/qrlib.php');
    $user = \Drupal::currentUser();
    $x = $user->uid;
    $proposal_id = arg(3);
    /*$query2 = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status=1 AND  proposal_id=".     $proposal_id);
    $data2 = db_fetch_object($query2);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $data2 = $result->fetchObject();
    /*$query3 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id=".$proposal_id);
    $data3 = db_fetch_object($query3);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    $data3 = $result->fetchObject();
    $query4            = \Drupal::database()->query("SELECT COUNT( tce.id ) AS example_count FROM textbook_companion_example tce
                        LEFT JOIN textbook_companion_chapter tcc ON tce.chapter_id = tcc.id
                        LEFT JOIN textbook_companion_preference tcpe ON tcc.preference_id = tcpe.id
                        LEFT JOIN textbook_companion_proposal tcpo ON tcpe.proposal_id = tcpo.id
                        WHERE tcpo.proposal_status =3 AND tce.approval_status =1 AND tce.approval_status=1 AND tcpo.id = :prop_id", array(
        ':prop_id' => $proposal_id
    ));
    $data4             = $query4->fetchObject();
    if($data4->example_count == 0) {
        \Drupal::messenger()->addError('Certificate is not available');
        return;
    }
    $number_of_example = $data4->example_count;
    //var_dump($data3->gender);
    $gender = array(
        'salutation' => 'Mr. /Ms.',
        'gender' => 'He/She'
    );
    if ($data3->gender)
      {
        if ($data3->gender == 'M')
          {
            $gender = array(
                'salutation' => 'Mr.',
                'gender' => 'He'
            );
          }
        else
          {
            $gender = array(
                'salutation' => 'Ms.',
                'gender' => 'She'
            );
          }
      }
    //die;
    /*if($data3->proposal_status == 3)
    {*/
    $pdf = new FPDF('L', 'mm', 'Letter');
    if (!$pdf)
      {
        echo "Error!";
      }
    $pdf->SetTextColor(129, 80, 47);
    $author = $data2->author; 
    $path = drupal_get_path('module', 'textbook_companion');
    $pdf->AddPage();
    $image_bg = $path . "/pdf/images/bg_cert.png";
    $pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
    $pdf->SetMargins(18, 1, 18);
    $pdf->Ln(25);
    $pdf->SetFont('Times', 'I', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(320, 10, 'This is to certify that', '0', '1', 'C');
    $pdf->Ln(0);
    $pdf->SetFont('Times', 'I', 20);
    //$pdf->SetFont('Times', 'BI', 25);
    $pdf->SetTextColor(30, 100, 182);
    $pdf->Cell(320, 12, $data3->full_name, '0', '1', 'C');
    $pdf->Ln(0);
    $pdf->SetFont('Times', 'I', 16);
    $pdf->SetTextColor(0, 0, 0);
        //$pdf->Cell(240, 8, 'from ' . $data3->university . ' has successfully', '0', '1', 'C');
    $pdf->MultiCell(320, 10, wordwrap('from "' . $data3->university . '" has successfully completed a', 80), '0','C');
    $pdf->Ln(0);
    $pdf->MultiCell(320, 10, 'R textbook companion by coding "' . $number_of_example . '" examples from the book', '0', 'C');
    $pdf->Ln(0); 
    $pdf->SetTextColor(30, 100, 182);
    $pdf->Cell(320, 12,$data2->book, '0', '1', 'C');
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
    $UniqueString    = "";
    $tempDir         = $path . "/pdf/temp_prcode/";
    $query           = \Drupal::database()->select('textbook_companion_qr_code');
    $query->fields('textbook_companion_qr_code');
    $query->condition('proposal_id', $proposal_id);
    $result          = $query->execute();
    $data            = $result->fetchObject();
    $DBString        = $data->qr_code;
    $proposal_get_id = $data->proposal_id;
    if ($DBString == "" || $DBString == "null") {
        $UniqueString = generateRandomString();
        $query        = "
                INSERT INTO textbook_companion_qr_code
                (proposal_id,qr_code)
                VALUES
                (:proposal_id,:qr_code)
                ";
        $args         = array(
            ":proposal_id" => $proposal_id,
            ":qr_code" => $UniqueString,
        );
        $result       = \Drupal::database()->query($query, $args, $query);
    } //$DBString == "" || $DBString == "null"
    else {
        $UniqueString = $DBString;
    }
    $codeContents        = "https://r.fossee.in/textbook-companion/certificates/verify/" . $UniqueString;
    $fileName            = 'generated_qrcode.png';
    $pngAbsoluteFilePath = $tempDir . $fileName;
    $urlRelativeFilePath = $path . "/pdf/temp_prcode/" . $fileName;
    $sign = $path . "/pdf/images/sign.png";
    QRcode::png($codeContents, $pngAbsoluteFilePath);
    $pdf->SetY(85);
    $pdf->SetX(320);
    $pdf->Ln(10);
    $sign1 = $path . "/pdf/images/sign1.png";
    $sign2 = $path . "/pdf/images/sign2.png";
    $pdf->Image($sign1, $pdf->GetX()+60, $pdf->GetY() + 45, 85, 0);
    $pdf->Image($sign2, $pdf->GetX()+160, $pdf->GetY() + 45, 85, 0);
    $pdf->Image($pngAbsoluteFilePath, $pdf->GetX()+15, $pdf->GetY()+70, 30, 0);
    $fossee = $path . "/pdf/images/fossee.png";
    $mhrd = $path . "/pdf/images/mhrd.png"; 
    $pdf->Image($fossee, $pdf->GetX()+80, $pdf->GetY()+80, 50, 0);
    $pdf->Image($mhrd, $pdf->GetX()+180, $pdf->GetY()+80, 40, 0);
    //$pdf->SetX(29);
    //$pdf->SetY(-50);
    $pdf->Ln(2);
    $ftr_line = $path . "/pdf/images/ftr_line.png"; 
    $pdf->Image($ftr_line, $pdf->GetX(), $pdf->GetY()+105, 250, 0);
    $pdf->SetFont('Times', 'I', 15);
    $pdf->SetLeftMargin(40);
    $pdf->GetY()+60;
    $pdf->Ln(62);
    $pdf->Cell(320, 8, $UniqueString, '0', '1', 'L');
    //$pdf->Ln(6);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);
    $filename = str_replace(' ', '-', $data3->full_name) . '-R_TBC_Certificate.pdf';
    $file     = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
    $pdf->Output($file, 'F');
    ob_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Content-Length: " . filesize($file));
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Pragma: no-cache");
    flush();
    $fp = fopen($file, "r");
    while (!feof($fp)) {
        echo fread($fp, filesize($file));
        flush();
    } //!feof($fp)
    ob_end_flush();
    ob_clean();
    fclose($fp);
    unlink($file);
    return;
    /*}
    else
    {
    drupal_set_message('Your Book Is Still Under Review.', 'status');
    }*/
}
function generateRandomString($length = 5)
{
    $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    } //$i = 0; $i < $length; $i++
    return $randomString;
}  
