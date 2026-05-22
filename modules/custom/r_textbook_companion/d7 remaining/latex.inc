<?php
function textbook_companion_download_book()
{
    $preference_id = arg(2);
    // drupal_set_message('This feature is currently unavailabe. We will resumes it shorty.','error');
    //drupal_goto('textbook_run/'. $preference_id);
    //exit;
    _latex_copy_script_file();
    $full_book = arg(3);
    if ($full_book == "1")
        _latex_generate_files($preference_id, TRUE);
    else
        _latex_generate_files($preference_id, FALSE);
}
function _latex_generate_files($preference_id, $full_book = FALSE)
{
    $root_path = textbook_companion_path();
    $dir_path = $root_path . "latex/";
    $book_filedata = "";
    $contributor_filedata = "";
    $latex_filedata = "";
    $latex_dep_filedata = "";
    $depedency_list = array();
    $eol = "\n";
    $sep = "#";
    $preference_q = \Drupal::database()->query("SELECT * FROM {textbook_companion_preference} WHERE id = :id", array(
        ':id' => $preference_id
    ));
    $preference_data = $preference_q->fetchObject();
    if (!$preference_data)
    {
        \Drupal::messenger()->addError('Invalid book specified.');
        drupal_goto('');
    } //!$preference_data
    if ($preference_data->approval_status == 0)
    {
        \Drupal::messenger()->addError('Book proposal is still in pending review.');
        drupal_goto('');
    } //$preference_data->approval_status == 0
    $book_filedata = $preference_data->book . $sep . $preference_data->author . $sep . $preference_data->isbn . $sep . $preference_data->publisher . $sep . $preference_data->edition . $sep . $preference_data->year . $eol;
    /* regenerate book if full book selected */
    if ($full_book)
        del_book_pdf($preference_data->id);
    /* check if book already generated */
    //var_dump(file_exists($dir_path . "book_" . $preference_data->id . ".pdf"));die;
    if (file_exists($dir_path . "book_" . $preference_data->directory_name . ".pdf"))
    {
        /* download PDF file */
        ob_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-Type: application/pdf');
        header('Content-disposition: attachment; filename="' . $preference_data->book . '_' . $preference_data->author . '.pdf"');
        header('Content-Length: ' . filesize($dir_path . "book_" . $preference_data->directory_name . ".pdf"));
        //header("Content-Transfer-Encoding: binary");
        //header('Expires: 0');
        //header('Pragma: no-cache');
        //ob_end_flush();
        //ob_clean();
        //flush();
        readfile($dir_path . "book_" . $preference_data->directory_name . ".pdf");
        return;
    } //file_exists($dir_path . "book_" . $preference_data->id . ".pdf")
    $proposal_q = \Drupal::database()->query("SELECT * FROM {textbook_companion_proposal} WHERE id = :id", array(
        ':id' => $preference_data->proposal_id
    ));
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data)
    {
        \Drupal::messenger()->addError('Could not fetch contributors information for the book specified.');
    } //!$proposal_data
    $contributor_filedata .= $proposal_data->full_name . $sep . $proposal_data->course . $sep . $proposal_data->branch . $sep . $proposal_data->university . $sep . $proposal_data->faculty . $sep . $proposal_data->reviewer . $eol;
    $chapter_q = \Drupal::database()->query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = :id ORDER BY number DESC", array(
        ':id' => $preference_data->id
    ));
    while ($chapter_data = $chapter_q->fetchObject())
    {
        if ($full_book)
            $example_q = \Drupal::database()->query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = :id ORDER BY number DESC", array(
                ':id' => $chapter_data->id
            ));
        else
            $example_q = \Drupal::database()->query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = :id AND approval_status = 1 ORDER BY number DESC", array(
                ':id' => $chapter_data->id
            ));
        while ($example_data = $example_q->fetchObject())
        {
            $example_files_q = \Drupal::database()->query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = :id", array(
                ':id' => $example_data->id
            ));
            while ($example_files_data = $example_files_q->fetchObject())
            {
                $latex_filedata .= $chapter_data->number . $sep;
                $latex_filedata .= $chapter_data->name . $sep;
                $latex_filedata .= $example_data->number . $sep;
                $latex_filedata .= $example_data->caption . $sep;
                $latex_filedata .= $example_files_data->filename . $sep;
                $latex_filedata .= $preference_data->directory_name . '/' . $example_files_data->filepath . $sep;
                $latex_filedata .= $example_files_data->filetype . $sep;
                $latex_filedata .= $sep;
                $latex_filedata .= $example_files_data->id;
                $latex_filedata .= $eol;
            } //$example_files_data = $example_files_q->fetchObject()
            /*$dependency_files_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE example_id = :id", array(
                ':id' => $example_data->id
            ));
            while ($dependency_files_data = $dependency_files_q->fetchObject())
            {
                $dependency_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = :id LIMIT 1", array(
                    ':id' => $dependency_files_data->dependency_id
                ));
                if ($dependency_data = $dependency_q->fetchObject())
                {
                    $latex_filedata .= $chapter_data->number . $sep;
                    $latex_filedata .= $chapter_data->name . $sep;
                    $latex_filedata .= $example_data->number . $sep;
                    $latex_filedata .= $example_data->caption . $sep;
                    $latex_filedata .= $dependency_data->filename . $sep;
                    $latex_filedata .= $dependency_data->filepath . $sep;
                    $latex_filedata .= 'D' . $sep;
                    $latex_filedata .= $dependency_data->caption . $sep;
                    $latex_filedata .= $dependency_data->id;
                    $latex_filedata .= $eol;
                    $depedency_list[$dependency_data->id] = "D";
                } //$dependency_data = $dependency_q->fetchObject()
            } */ //$dependency_files_data = $dependency_files_q->fetchObject()
        } //$example_data = $example_q->fetchObject()
    } //$chapter_data = $chapter_q->fetchObject()
    /*foreach ($depedency_list as $row => $data)
    {
        $dependency_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = :id LIMIT 1", array(
            ':id' => $row
        ));
        if ($dependency_data = $dependency_q->fetchObject())
        {
            $latex_dep_filedata .= $dependency_data->filename . $sep;
            $latex_dep_filedata .= $dependency_data->filepath . $sep;
            $latex_dep_filedata .= $dependency_data->caption . $sep;
            $latex_dep_filedata .= $dependency_data->id;
            $latex_dep_filedata .= $eol;
        } //$dependency_data = $dependency_q->fetchObject()
    }*/ //$depedency_list as $row => $data
    /**************************** WRITE TO FILES ********************************/
    $download_filename = $preference_data->book . "_" . $preference_data->author;
    $book_filename = "tmp_" . $preference_data->id . "_book.txt";
    $contributor_filename = "tmp_" . $preference_data->id . "_contributor.txt";
    $latex_filename = "tmp_" . $preference_data->id . "_data.txt";
    /*$latex_dep_filename = "tmp_" . $preference_data->id . "_dep_data.txt";*/
    $pdf_filename = "book_" . $preference_data->directory_name . ".pdf";
    // $book_filedata = str_replace("&", "\&", $book_filedata);
    $fb = fopen($dir_path . $book_filename, 'w');
    fwrite($fb, $book_filedata);
    fclose($fb);
    // $contributor_filedata = str_replace("&", "\&", $contributor_filedata);
    $fc = fopen($dir_path . $contributor_filename, 'w');
    fwrite($fc, $contributor_filedata);
    fclose($fc);
    $fl = fopen($dir_path . $latex_filename, 'w');
    fwrite($fl, $latex_filedata);
    fclose($fl);
    /*$fd = fopen($dir_path . $latex_dep_filename, 'w');
    fwrite($fd, $latex_dep_filedata);
    fclose($fd);*/
    //var_dump($dir_path);die;
    if (_latex_run_script($book_filename, $contributor_filename, $latex_filename, $pdf_filename))
    {
        /* download PDF file */
       // var_dump($dir_path . $pdf_filename);die;
        if (filesize($dir_path . $pdf_filename) == TRUE)
        {
            //ob_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-Type: application/pdf');
            header('Content-disposition: attachment; filename="' . $preference_data->book . '_' . $preference_data->author . '.pdf"');
            header('Content-Length: ' . filesize($dir_path . $pdf_filename));
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            @readfile($dir_path . $pdf_filename);
            ob_end_flush();
            ob_clean();
            flush();
        } //filesize($dir_path . $pdf_filename) == TRUE
        else
        {
            \Drupal::messenger()->addError("Error occurred generating the PDF version of the Book.");
            drupal_goto('textbook-companion/textbook-run/' . $preference_data->id);
        }
    } //_latex_run_script($book_filename, $contributor_filename, $latex_filename, $latex_dep_filename, $pdf_filename)
    else
    {
        \Drupal::messenger()->addError("Error occurred when generating the PDF version of the Book.");
    }
    /*********************** DELETING TEMPORARY FILES ***************************/
    /* regenerate book if full book selected */
    if ($full_book)
        del_book_pdf($preference_data->id);
}
function _latex_copy_script_file()
{
    exec("cp ./" . drupal_get_path('module', 'textbook_companion') . "/latex/* ./r_uploads/tbc_uploads/latex");
    exec("chmod u+x ./r_uploads/tbc_uploads/latex/*.sh");
}
function _latex_run_script($book_filename, $contributor_filename, $latex_filename, $pdf_filename)
{
   // var_dump($book_filename . $contributor_filename . $latex_filename . $pdf_filename);die;
    $root_path = textbook_companion_path();
    $ret = 0;
    chdir("r_uploads/tbc_uploads");
    chdir("latex");
    //var_dump($book_filename . " " . $contributor_filename . " " . $latex_filename . " " . $latex_dep_filename);die;
    $sh_command = "./pdf_creator.sh " . $book_filename . " " . $contributor_filename . " " . $latex_filename . " " . $pdf_filename;
    exec($sh_command);
    //var_dump($sh_command);die; 
    //exec("mv TEX_final.pdf " , $pdf_filename);
    // rename('TEX_final.pdf', $pdf_filename);
    //var_dump($pdf_filename);die;
    if ($ret == 0)
    {
        return TRUE;
    } //$ret == 0
    else
    {
        return FALSE;
    }
}
function textbook_companion_delete_book()
{
    $book_id = arg(2);
    del_book_pdf($book_id);
    \Drupal::messenger()->addStatus(t('Book schedule for regeneration.'));
    drupal_goto('textbook-companion/code-approval/bulk');
    return;
}
