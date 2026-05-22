<?php
function generate_lm_pdf()
	{
		$mpath = drupal_get_path('module', 'lab_migration');
		//var_dump($mpath);die;
		require($mpath . '/pdf/fpdf/fpdf.php');
		require($mpath . '/pdf/phpqrcode/qrlib.php');
		$user = \Drupal::currentUser();
		$x = $user->uid;
		$proposal_id = arg(3); // proposal id of lab
		$lab_certi_id = arg(4);
		if ($proposal_id != NULL && $lab_certi_id != NULL)
			{
				$query3 = \Drupal::database()->query("SELECT * FROM lab_migration_certificate WHERE proposal_id= :prop_id AND id=:certi_id", array(
						':prop_id' => $proposal_id,
						":certi_id" => $lab_certi_id
				));
				$data3 = $query3->fetchObject();
				//var_dump($data3->type);die;
				if ($data3->type == 'Proposer')
					{
//var_dump("1st mere ko call kiya");die;
						$pdf = new FPDF('L', 'mm', 'Letter');
						if (!$pdf)
							{
								echo "Error!";
							} //!$pdf
						$pdf->AddPage();
						$image_bg = $mpath . "/pdf/images/bg.png";
						$pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
						$pdf->Rect(5, 5, 267, 207, 'D'); //For A4
						$pdf->SetMargins(18, 1, 18);
						$pdf->Line(7.0, 7.0, 270.0, 7.0);
						$pdf->Line(7.0, 7.0, 7.0, 210.0);
						$pdf->Line(270.0, 210.0, 270.0, 7.0);
						$pdf->Line(7.0, 210.0, 270.0, 210.0);
						$path = drupal_get_path('module', 'lab_migration');
						//$image1 = $mpath . "/pdf/images/scilab.png";
						$pdf->Ln(15);
						//$pdf->Cell(200, 8, $pdf->Image($image1, 100, 19, 0, 28), 0, 1, 'C');
						$pdf->Ln(35);
						//$pdf->SetFont('Times', 'BI', 25);
						//$pdf->SetTextColor(139, 69, 19);
						//$pdf->Cell(240, 8, 'Certificate of Lab Migration', '0', 1, 'C');
						//$pdf->Ln(4);
						$pdf->SetFont('Times', 'BI', 12);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->Cell(240, 8, 'This is to certify that under the supervision/guidance of ' . $data3->name_title . ' ' . $data3->name . ',', '0', 1, 'C');
						$pdf->SetTextColor(139, 69, 19);
						$pdf->Ln(2);
						$pdf->Cell(240, 8, 'from the ' . $data3->department . ',', '0', 1, 'C');
						$pdf->Ln(0);
						$pdf->SetFont('Times', 'BI', 12);
						$pdf->SetTextColor(139, 69, 19);
						$pdf->Cell(240, 8, $data3->institute_name . ', ' . $data3->institute_address, '0', '1', 'C');
						$pdf->Ln(2);
						$pdf->SetFont('Times', 'BI', 12);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->Cell(240, 8, ' has successfully migrated the ', '0', '1', 'C');
						$pdf->Ln(0);
						$pdf->SetTextColor(139, 69, 19);
						$pdf->Cell(240, 8, $data3->lab_name, '0', '1', 'C');
						//$pdf->Ln(0);
						//$pdf->SetTextColor(0,0,0);
						//$pdf->Cell(240,8,'in ', '0','1','C');
						$pdf->Ln(0);
						$pdf->SetTextColor(139, 69, 19);
						$pdf->Cell(240, 8, '(' . $data3->semester_details . ')', '0', '1', 'C');
						$pdf->Ln(0);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->Cell(240, 8, 'to a R-only lab.', '0', '1', 'C');
						$pdf->Ln(0);
						//$proposal_get_id=0;
						$UniqueString = "";
						$tempDir = $path . "/pdf/temp_prcode/";
						//$UniqueString=generateRandomString();
						$query = \Drupal::database()->select('lab_migration_certificate_qr_code');
						$query->fields('lab_migration_certificate_qr_code', array(
								'qr_code'
						));
						//$query->condition('qr_code', $UniqueString);
						$query->condition('proposal_id', $proposal_id);
						$result = $query->execute();
						$data = $result->fetchObject();
						$DBString = $data->qr_code;
						//$proposal_get_id=$data->proposal_id;
						if ($DBString == "" || $DBString == "null")
							{
								$UniqueString = generateRandomString();
								$query = "
				INSERT INTO lab_migration_certificate_qr_code
				(proposal_id,qr_code,certificate_id,gen_date)
				VALUES
				(:proposal_id,:qr_code,:certificate_id,:gen_date)
				";
								$args = array(
										":proposal_id" => $proposal_id, // id is not the proposal id its lab migration certificate table id
										":qr_code" => $UniqueString,
										":certificate_id" => $lab_certi_id,
										":gen_date" => time()
								);
								/* storing the row id in $result */
								$result = \Drupal::database()->query($query, $args, $query);
							} //$DBString == "" || $DBString == "null"
						else
							{
								$UniqueString = $DBString;
							}
						$codeContents = "https://r.fossee.in/lab-migration/certificates/verify/" . $UniqueString;
						$fileName = 'generated_qrcode.png';
						$pngAbsoluteFilePath = $tempDir . $fileName;
						$urlRelativeFilePath = $path . "/pdf/temp_prcode/" . $fileName;
						QRcode::png($codeContents, $pngAbsoluteFilePath);
						$pdf->Cell(240, 4, '', '0', '1', 'C');
						$pdf->SetX(95);
						$pdf->SetFont('', 'U');
						$pdf->SetTextColor(139, 69, 19);
						$pdf->SetFont('', '');
						$pdf->SetTextColor(0, 0, 0);
						$pdf->write(0, '.', '.');
						$pdf->Ln(5);
						$pdf->SetX(198);
						$pdf->SetFont('', '');
						$pdf->SetTextColor(0, 0, 0);
						$pdf->SetY(-70);
						$pdf->SetX(50);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->SetFont('', '');
						$sign = $path . "/pdf/images/pisign.png";
						$pdf->Image($sign, $pdf->GetX() - 20, $pdf->GetY(), 75, 0);
						$pdf->SetY(-70);
						$pdf->SetX(200);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->SetFont('', '');
						$copisign = $path . "/pdf/images/copisign.png";
						$pdf->Image($copisign, $pdf->GetX() - 20, $pdf->GetY(), 75, 0);
						//$pdf->Cell(0,7,'', 0,1,'L');
						//$pdf->SetFont('Times', 'I', 12);
						//$pdf->Cell(0, 8, 'Prof. Kannan Moudgalya', 0, 1, 'L');
						//$pdf->SetX(194);
						//$pdf->Cell(0, 7, 'Principal Investigator - FOSSEE', 0, 1, 'L');
						//$pdf->SetX(190);
						//$pdf->Cell(0, 7, ' Dept. of Chemical Engg., IIT Bombay.', 0, 1, 'L');
						//$pdf->SetX(29);
						$pdf->SetFont('Times', 'B', 10);
						$pdf->SetY(-49);
						$pdf->SetX(10);
						$pdf->Cell(0, 2, $UniqueString, 0, 0, 'C');
						//$image4=$path."/pdf/images/verify_content.png";
						//$pdf->Image($image4, $pdf->GetX(), $pdf->GetY(),20, 0);
						$pdf->SetY(-40);
						$pdf->SetX(70);
						$image3 = $path . "/pdf/images/MOE-logo.png";
						$image2 = $path . "/pdf/images/fossee.png";
						$pdf->Image($image2, $pdf->GetX() - 15, $pdf->GetY() + 7, 40, 0);
						$pdf->Image($pngAbsoluteFilePath, $pdf->GetX() + 50, $pdf->GetY() - 5, 30, 0);
						$pdf->Image($image3, $pdf->GetX() + 110, $pdf->GetY() + 3, 40, 0);
						$image4 = $path . "/pdf/images/ftr_line.png";
						$pdf->Image($image4, $pdf->GetX() - 15, $pdf->GetY() + 28, 150, 0);
						$pdf->SetFont('Times', 'I', 8);
						$pdf->SetTextColor(0, 0, 0);
						$filename = str_replace(' ', '-', $data3->proposal_id) . '-R-Lab-migration-Certificate.pdf';
						$file = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
						$pdf->Output($file, 'F');
						header("Content-Type: application/octet-stream");
						header("Content-Disposition: attachment; filename=" . $filename);
						header("Content-Type: application/octet-stream");
						header("Content-Type: application/pdf");
						header("Content-Description: File Transfer");
						header("Content-Length: " . filesize($file));
						flush(); // this doesn't really matter.
						$fp = fopen($file, "r");
						while (!feof($fp))
							{
								echo fread($fp, 65536);
								flush(); // this is essential for large downloads
							} //!feof($fp)
						fclose($fp);
						unlink($file);
						//RedirectResponse('lab_migration/certificate');
						return;
					} //$data3->type = 'Proposer'
				elseif ($data3->type == 'Participant')
					{
						$pdf = new FPDF('L', 'mm', 'Letter');
						if (!$pdf)
							{
								echo "Error!";
							} //!$pdf
						$pdf->AddPage();
						$image_bg = $mpath . "/pdf/images/bg.png";
						$pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
						$pdf->Rect(5, 5, 267, 207, 'D'); //For A4
						$pdf->SetMargins(18, 1, 18);
						$pdf->Line(7.0, 7.0, 270.0, 7.0);
						$pdf->Line(7.0, 7.0, 7.0, 210.0);
						$pdf->Line(270.0, 210.0, 270.0, 7.0);
						$pdf->Line(7.0, 210.0, 270.0, 210.0);
						$path = drupal_get_path('module', 'lab_migration');
						//$image1 = $mpath . "/pdf/images/scilab.png";
						$pdf->Ln(15);
						//$pdf->Cell(200, 8, $pdf->Image($image1, 100, 19, 0, 28), 0, 1, 'C');
						$pdf->Ln(35);
						/*$pdf->SetFont('Times', 'BI', 25);
						$pdf->SetTextColor(139, 69, 19);
						$pdf->Cell(240, 8, 'Certificate of Participation', '0', 1, 'C');
						$pdf->Ln(3);*/
						$pdf->SetFont('Times', 'BI', 12);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->Cell(240, 8, 'This is to certify that', '0', '1', 'C');
						$pdf->Ln(4);
						$pdf->SetFont('Times', 'BI', 20);
						$pdf->SetTextColor(139, 69, 19);
						$pdf->Cell(240, 8, $data3->name_title . ' ' . $data3->name, '0', 1, 'C');
						$pdf->Ln(5);
						$pdf->SetFont('Times', 'I', 12);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->Cell(240, 8, 'from the Department of ' . $data3->department . ', ', '0', '1', 'C');
						$pdf->Ln(0);
						$pdf->Cell(240, 8, $data3->institute_name . ', ' . $data3->institute_address, '0', 1, 'C');
						$pdf->Ln(0);
						$pdf->Cell(240, 8, ' has developed the solutions in R for the ', '0', 1, 'C');
						$pdf->Ln(0);
						$pdf->Cell(240, 8, $data3->lab_name . ' (' . $data3->semester_details . ').', '0', 1, 'C');
						$pdf->Ln(0);
						$pdf->Cell(240, 4, '', '0', '1', 'C');
						$pdf->SetX(95);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->write(0, 'The work done is available at ');
						$pdf->SetFont('', 'U');
						$pdf->SetTextColor(139, 69, 19);
						$pdf->write(0, 'https://r.fossee.in/', 'https://r.fossee.in/');
						$pdf->SetFont('', '');
						$pdf->SetTextColor(0, 0, 0);
						$pdf->write(0, '.', '.');
						//$proposal_get_id=0;
						$UniqueString = "";
						$tempDir = $path . "/pdf/temp_prcode/";
						//$UniqueString=generateRandomString();
						$query = \Drupal::database()->select('lab_migration_certificate_qr_code');
						$query->fields('lab_migration_certificate_qr_code', array(
								'qr_code',
								'certificate_id'
						));
						//$query->condition('qr_code', $UniqueString);
						$query->condition('proposal_id', $proposal_id);
						$query->condition('certificate_id', $lab_certi_id);
						$result = $query->execute();
						$data = $result->fetchObject();
						$DBString = $data->qr_code;
						//$proposal_get_id=$data->proposal_id;
						if ($DBString == "" || $DBString == "null")
							{
								$UniqueString = generateRandomString();
								$query = "
				INSERT INTO lab_migration_certificate_qr_code
				(proposal_id,qr_code,certificate_id,gen_date)
				VALUES
				(:proposal_id,:qr_code,:certificate_id,:gen_date)
				";
								$args = array(
										":proposal_id" => $proposal_id, // id is not the proposal id its lab migration certificate table id
										":qr_code" => $UniqueString,
										":certificate_id" => $lab_certi_id,
										":gen_date" => time()
								);
								/* storing the row id in $result */
								$result = \Drupal::database()->query($query, $args, $query);
							} //$DBString == "" || $DBString == "null"
						else
							{
								$UniqueString = $DBString;
							}
						$codeContents = "https://r.fossee.in/lab-migration/certificates/verify/" . $UniqueString;
						$fileName = 'generated_qrcode.png';
						$pngAbsoluteFilePath = $tempDir . $fileName;
						$urlRelativeFilePath = $path . "/pdf/temp_prcode/" . $fileName;
						QRcode::png($codeContents, $pngAbsoluteFilePath);
						$pdf->Cell(240, 4, '', '0', '1', 'C');
						$pdf->SetX(95);
						$pdf->SetFont('', 'U');
						$pdf->SetTextColor(139, 69, 19);
						$pdf->SetFont('', '');
						$pdf->SetTextColor(0, 0, 0);
						$pdf->write(0, '.', '.');
						$pdf->Ln(5);
						$pdf->SetX(198);
						$pdf->SetFont('', '');
						$pdf->SetTextColor(0, 0, 0);
						$pdf->SetY(-70);
						$pdf->SetX(50);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->SetFont('', '');
						$sign = $path . "/pdf/images/pisign.png";
						$pdf->Image($sign, $pdf->GetX() - 20, $pdf->GetY(), 75, 0);
						$pdf->SetY(-70);
						$pdf->SetX(200);
						$pdf->SetTextColor(0, 0, 0);
						$pdf->SetFont('', '');
						$copisign = $path . "/pdf/images/copisign.png";
						$pdf->Image($copisign, $pdf->GetX() - 20, $pdf->GetY(), 75, 0);
						//$pdf->Cell(0,7,'', 0,1,'L');
						//$pdf->SetFont('Times', 'I', 12);
						//$pdf->Cell(0, 8, 'Prof. Kannan Moudgalya', 0, 1, 'L');
						//$pdf->SetX(194);
						//$pdf->Cell(0, 7, 'Principal Investigator - FOSSEE', 0, 1, 'L');
						//$pdf->SetX(190);
						//$pdf->Cell(0, 7, ' Dept. of Chemical Engg., IIT Bombay.', 0, 1, 'L');
						//$pdf->SetX(29);
						$pdf->SetFont('Times', 'B', 10);
						$pdf->SetY(-56);
						$pdf->SetX(10);
						$pdf->Cell(0, 2, $UniqueString, 0, 0, 'C');
						$pdf->SetY(-47);
						$pdf->SetX(70);
						$image3 = $path . "/pdf/images/MOE-logo.png";
						$image2 = $path . "/pdf/images/fossee.png";
						$pdf->Image($image2, $pdf->GetX() - 15, $pdf->GetY() + 7, 40, 0);
						$pdf->Image($pngAbsoluteFilePath, $pdf->GetX() + 50, $pdf->GetY() - 5, 30, 0);
						$pdf->Image($image3, $pdf->GetX() + 110, $pdf->GetY() + 3, 40, 0);
						$image4 = $path . "/pdf/images/ftr_line.png";
						//$pdf->Image($image4, $pdf->GetX(), $pdf->GetY(),0, 10);
						$pdf->Image($image4, $pdf->GetX() - 15, $pdf->GetY() + 28, 150, 0);
						$pdf->SetFont('Times', 'I', 8);
						$pdf->SetTextColor(0, 0, 0);
						$filename = str_replace(' ', '-', $data3->id) . '-R-Lab-migration-Participation-Certificate.pdf';
						$file = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
						$pdf->Output($file, 'F');
						header("Content-Type: application/octet-stream");
						header("Content-Disposition: attachment; filename=" . $filename);
						header("Content-Type: application/octet-stream");
						header("Content-Type: application/pdf");
						header("Content-Description: File Transfer");
						header("Content-Length: " . filesize($file));
						flush(); // this doesn't really matter.
						$fp = fopen($file, "r");
						while (!feof($fp))
							{
								echo fread($fp, 65536);
								flush(); // this is essential for large downloads
							} //!feof($fp)
						fclose($fp);
						unlink($file);
						//RedirectResponse('lab_migration/certificate');
						return;
					} //$data3->type = 'Proposer'
				else
					{
						RedirectResponse('lab_migration/certificate');
					}
			} //$proposal_id != NULL && $lab_certi_id !== NULL
		else
			{
				\Drupal::messenger()->addStatus('Your lab Is Still Under Review.');
			}
	}
function generateRandomString($length = 5)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++)
			{
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			} //$i = 0; $i < $length; $i++
		return $randomString;
	}


