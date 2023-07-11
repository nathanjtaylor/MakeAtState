<?php

class ExportStats
{
    private $reason_data;
    private $job_data;
    private $dc;

    function __construct()
    {
        $this->dc = new DataCalls();

        $this->reason_data= $this->dc->getExportStats();
        $this->job_data= $this->dc->getDetailJobStats();
        $this->exportStats();

        exit();
    }

    private function exportStats()
    {
        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=3dprime_data.csv');

        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        fputcsv($output, array('Cancelled by User', 'Cancelled by Staff', 'Total'));
        fputcsv($output, array($this->job_data['user_cancelled_job'],$this->job_data['staff_cancelled_job'],$this->job_data['user_cancelled_job']+$this->job_data['staff_cancelled_job']));
        fputcsv($output, array());
        // output the column headings
        fputcsv($output, array('DATE', 'REASON', 'MORE INFO'));


        // loop over the rows, outputting them
        foreach($this->reason_data as $row) fputcsv($output, $row);

    }
}