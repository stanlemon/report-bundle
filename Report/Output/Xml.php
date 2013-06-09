<?php
namespace Lemon\ReportBundle\Report\Output;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;


class Xml implements OutputInterface {
    
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function render()
    {
        $serializer = new Serializer(array(), array(new XmlEncoder()));

        return $serializer->serialize($this->data, 'xml');
    }
}