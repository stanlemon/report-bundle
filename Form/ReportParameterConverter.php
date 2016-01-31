<?php
namespace Lemon\ReportBundle\Form;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Lemon\ReportBundle\Entity\Report;
use Lemon\ReportBundle\Entity\ReportParameter;

class ReportParameterConverter
{
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var FormFactory
     */
    protected $formFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
    }

    public function createFormBuilder(Report $report, $data = array())
    {
        return $this->createNamedFormBuilder(null, $report, $data);
    }

    public function createNamedFormBuilder($formName, Report $report, $data = array())
    {
        $formBuilder = $this->formFactory->createNamedBuilder($formName, FormType::class, $data, array(
            'csrf_protection' => false
        ));

        foreach ($report->getParameters() as $parameter) {
            $options = $this->buildOptions($parameter);

            if (isset($data[$parameter->getName()])) {
                $options['data'] = $data[$parameter->getName()];
            }

            $className = '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\' . ucfirst($parameter->getType()) . 'Type';

            if (class_exists($parameter->getType())) {
                $type = $parameter->getType();
            } else if (class_exists($className)) {
                $type = $className;
            } else {
                $type = TextType::class;
            }

            $formBuilder->add($parameter->getName(), $type, $options);
        }

        $formBuilder->add('Search', SubmitType::class, array(
            'attr' => array(
                'class' => 'btn btn-primary'
            )
        ));

        return $formBuilder;
    }

    protected function buildOptions(ReportParameter $parameter)
    {
        $options = array();

        if ($parameter->getType() == ReportParameter::TYPE_QUERY) {
            $stmt = $this->connection->prepare($parameter->getChoices());
            $stmt->execute();

            $results = $stmt->fetchAll();

            $values = array(null => '');

            $keySet = array_keys(current($results));
            $key = current(array_slice($keySet, 0, 1));
            $value = current(array_slice($keySet, 1, 1));

            foreach ($results as $result) {
                $values[$result[$value]] = $result[$key];
            }

            $options = array_merge($options, array(
                'choices' => $values,
            ));

            $parameter->setType(ChoiceType::class);
        }

        $normalizer = new \Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer();
        $normalizer->setIgnoredAttributes(array('id', 'name', 'type', 'created', 'modified', 'active'));

        $serializer = new \Symfony\Component\Serializer\Serializer(array($normalizer));

        $options = array_merge($serializer->normalize($parameter), $options);

        foreach ($options as $key => $value) {
            if (is_null($value)) {
                unset($options[$key]);
            }
        }

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

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function setFormFactory(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
