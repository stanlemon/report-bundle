<?php
namespace Lemon\ReportBundle\Report\Output;

class Csv implements OutputInterface
{
    protected $csv;

    public function __construct(array $data)
    {
        $handle = fopen('php://memory', 'rb+');

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        $this->csv = stream_get_contents($handle);

        fclose($handle);
    }

    public function render()
    {
        return $this->csv;
    }
}
