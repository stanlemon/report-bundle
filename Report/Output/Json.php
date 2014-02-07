<?php
namespace Lemon\ReportBundle\Report\Output;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class Json implements OutputInterface
{
    protected $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function render()
    {
        $serializer = new Serializer(array(), array(new JsonEncoder()));

        return $serializer->serialize($this->data, 'json');
    }
}
