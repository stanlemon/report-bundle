<?php
namespace Lemon\ReportBundle\Form;

use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

use Lemon\ReportBundle\Entity\Report;
use Lemon\ReportBundle\Entity\ReportParameter;


class ReportParameterConverter 
{
    protected $report;
    protected $request;
    protected $formName;

    public function __construct(Report $report, Request $request, LoggerInterface $logger, $formName = 'lemon_report_form')
    {
        $this->report = $report;
        $this->request = $request;
        $this->formName = $formName;
        $this->logger = $logger;
    }

    public function createForm()
    {
        $formFactory = Forms::createFormFactory();

        $formBuilder = $formFactory->createNamedBuilder('lemon_report_form');

        $this->logger->info(count($this->report->getParameters()));

        foreach ($this->report->getParameters() as $parameter) {
            $options = $this->getFieldOptions($parameter);

            $formBuilder->add($parameter->getName(), $parameter->getType(), $options);
        }

        $formBuilder->add('Submit', 'submit', array(
            'attr' => array(
                'class' => 'btn'
            )
        ));

        return $formBuilder->getForm();
    }

    protected function getFieldOptions(ReportParameter $parameter)
    {
        $options = array(
            'required'  => false,
            'data'      => $this->getParameterValue($parameter),
        );
        
        if ($parameter->getType() == 'date' || $parameter->getType() == 'time') {
            $options = array_merge($options, array(
                'widget' => 'single_text',
            ));
        }

        if ($parameter->getType() == 'datetime') {
            $options = array_merge($options, array(
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
            ));
        }

        return $options;
    }

    /**
     * @todo Data transformation should not be done this way
     */
    protected function getParameterValue(ReportParameter $parameter)
    {
        $params = $this->request->request->get($this->formName);
        
        if (isset($params[$parameter->getName()]) && !empty($params[$parameter->getName()])) {
            $value = $params[$parameter->getName()];

            if ($parameter->getType() == 'date' || 
                $parameter->getType() == 'time' ||
                $parameter->getType() == 'datetime') {
                
                    $value = new \DateTime($value);
            }

            $parameter->setValue($value);
        }

        return $parameter->getValue();
    }
}
