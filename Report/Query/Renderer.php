<?php
namespace Lemon\ReportBundle\Report\Query;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Lemon\ReportBundle\Entity\Report;

class Renderer
{
    public function render($report)
    {
        $this->report = $report;

        try {
            $this->query = $this->twig->render(
                $this->report->getQuery(),
                $this->report->getParameterArray()
            );
            
            return $this->query;
        } catch (\Twig_Error_Syntax $e) {
            throw new RendererException($e->getMessage());
        }
    }
    
    public function getQuery()
    {
        return $this->query;
    }
    
    public function getParameters()
    {
        $parameters = array();
        
        foreach ($this->report->getParameters() as $parameter) {
            if (strpos($this->query, ':' . $parameter->getName()) !== false) {
                $parameters[] = $parameter;
            }
        }
        
        return $parameters;
    }

    public function setTwig($twig)
    {
        $this->twig = $twig;
    }
}