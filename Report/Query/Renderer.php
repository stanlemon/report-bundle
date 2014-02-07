<?php
namespace Lemon\ReportBundle\Report\Query;

use Twig_Environment;
use Twig_Error_Syntax;
use Lemon\ReportBundle\Report\Query\Renderer\Exception as RendererException;

class Renderer
{
    public function render($report, $values = array())
    {
        $this->report = $report;

        try {
            $this->query = $this->twig->render(
                $this->report->getQuery(),
                array_merge($this->report->getParameterArray(), $values)
            );
            
            return $this->query;
        } catch (Twig_Error_Syntax $e) {
            throw new RendererException($e->getMessage());
        }
    }
    
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @todo Can this be removed?
     */
    public function getParameters()
    {
        return $this->report->getParameters();
    }

    public function setTwig(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }
}