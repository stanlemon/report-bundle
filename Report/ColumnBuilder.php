<?php
namespace Lemon\ReportBundle\Report;

class ColumnBuilder 
{
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function build()
    {
        if (empty($this->data)) {
            return array();
        }

        $slice = array_slice($this->data, 0, 1);
        $columns = array_keys(array_shift($slice));

        foreach ($columns as $key => $value) {
            $columns[$key] = ucwords(str_replace('_', ' ', $value));
        }

        return $columns;
    }
}