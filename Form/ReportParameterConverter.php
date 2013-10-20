<?php
namespace Lemon\ReportBundle\Form;

use Symfony\Component\Form\FormFactory;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Lemon\ReportBundle\Entity\Report;
use Lemon\ReportBundle\Entity\ReportParameter;

class ReportParameterConverter 
{
    const PARAM_TYPE_QUERY = 'query';

    protected $report;
    protected $connections;
    protected $logger;
    protected $formName;

    public function __construct(FormFactory $formFactory, Report $report, Connection $connection, LoggerInterface $logger = null, $formName = null)
    {
        $this->formFactory = $formFactory;
        $this->report = $report;
        $this->connection = $connection;
        $this->formName = $formName;
        $this->logger = $logger;
    }

    public function createFormBuilder($data = array())
    {
        $formBuilder = $this->formFactory->createNamedBuilder($this->formName, 'form', $data, array(
            'csrf_protection' => false // TODO: This is only necessary on GET requests
        ));

        foreach ($this->report->getParameters() as $parameter) {
            $options = $this->buildOptions($parameter);

            $formBuilder->add($parameter->getName(), $parameter->getType(), $options);
        }

        $formBuilder->add('Search', 'submit', array(
            'attr' => array(
                'class' => 'btn btn-primary'
            )
        ));

        return $formBuilder;
    }

    protected function buildOptions(ReportParameter $parameter)
    {
        $options = array();

        if ($parameter->getType() == self::PARAM_TYPE_QUERY) {
            $stmt = $this->connection->prepare($parameter->getData());
            $stmt->execute();
            
            $results = $stmt->fetchAll();

            $values = array();

            $keySet = array_keys(current($results));
            $key = current(array_slice($keySet, 0, 1));
            $value = current(array_slice($keySet, 1, 1));

            foreach ($results as $result) {
                $values[$result[$key]] = $result[$value];
            }

            $options = array_merge($options, array(
                'choices' => $values,
            ));

            $parameter->setData(null);
            $parameter->setType('choice');
        }

        $normalizer = new \Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer();
        $normalizer->setIgnoredAttributes(array('id', 'name', 'type', 'created', 'modified', 'active'));

        $serializer = new \Symfony\Component\Serializer\Serializer(array($normalizer));

        $options = array_merge($options, $serializer->normalize($parameter));

        $this->handleConstraints($options);

        return $options;
    }
    
    protected function handleConstraints(array &$options)
    {
        if (!isset($options['constraints']) || !is_array($options['constraints']) || empty($options['constraints'])) {
            unset($options['constraints']); // Incase it's empty, just remove the option
            return;
        }

        foreach ($options['constraints'] as $index => $constraint) {
            // Do it this way rather than list() to prevent E_NOTICE for undefined vars
            $class = current(array_slice($constraint, 0, 1));
            $arguments = current(array_slice($constraint, 1, 1));

            if (!class_exists('Symfony\Component\Validator\Constraints\\' . $class)) {
                throw new \InvalidArgumentException(sprintf("Constraint class %s does not exist.", $class));
            }

            $class = 'Symfony\Component\Validator\Constraints\\' . $class;

            $options['constraints'][$index] = new $class($arguments);
        }
    }
}