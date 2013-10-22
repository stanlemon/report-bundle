<?php

namespace Lemon\ReportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Templating\EngineInterface as Templating;
use Symfony\Component\Form\FormFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;

use Lemon\ReportBundle\Report\Executor;
use Lemon\ReportBundle\Report\ColumnBuilder;
use Lemon\ReportBundle\Report\Loader\LoaderInterface;
use Lemon\ReportBundle\Report\Output\Csv;
use Lemon\ReportBundle\Report\Output\Json;
use Lemon\ReportBundle\Report\Output\Xml;
use Lemon\ReportBundle\Form\ReportParameterConverter;


/**
 * @Route("/report", service="lemon_report.report_controller"))
 */
class ReportController
{
    protected $templating;
    protected $formFactory;
    protected $connection;
    protected $logger;
    protected $serializer;
    protected $reportExecutor;
    protected $reportLoader;

    /**
     * @Route("/", name="lemon_report_list")
     * @Template()
     */
    public function listAction()
    {
        $reports = $this->reportLoader->findAll();

        return array(
            'reports'   => $reports,
        );
    }

    /**
     * @Route("/view/{id}/{page}", name="lemon_report_view_page")
     * @Route("/view/{id}.{_format}", name="lemon_report_view", defaults={"_format" = "html"})
     */
    public function viewAction(Request $request, $id, $page = 1)
    {
        if (!($report = $this->reportLoader->findById($id))) {
            throw new NotFoundHttpException("Report does not exist!");
        }

        $converter = new ReportParameterConverter(
            $this->formFactory,
            $report, 
            $this->connection, 
            $this->logger
        );

        $formBuilder = $converter->createFormBuilder($request->getSession()->get('report_' . $report->getSlug()));
        $formBuilder->setMethod($request->getMethod());

        $form = $formBuilder->getForm();

        $form->setData(
            $request->getSession()->get('report_' . $report->getSlug())
        );

        $form->bind($request);

        if ($request->isMethod('post') && $page == 1) {
            $request->getSession()->set('report_' . $report->getSlug(), $form->getData());
        }


        $results = $this->reportExecutor->setReport($report)
            ->execute($form->getData());

        $columnBuilder = new ColumnBuilder($results);
        $columns = $columnBuilder->build();

        $format = $request->getRequestFormat();

        switch ($format) {
            case 'csv':
                $csv = new Csv($results);
                $response = new Response($csv->render());
                $response->headers->set('Content-type', 'application/csv');
                return $response;

            case 'json':
                $json = new Json($results);
                $response = new Response($json->render());

                return $response;

            case 'xml':
                $xml = new Xml($results);
                $response = new Response($xml->render());

                return $response;

            default:
                $adapter = new ArrayAdapter($results);

                $pagerfanta = new Pagerfanta($adapter);
                $pagerfanta->setMaxPerPage(25);

                try {
                    $pagerfanta->setCurrentPage($page);
                } catch (NotValidCurrentPageException $e) {
                    throw new NotFoundHttpException();
                }

                return new Response($this->templating->render(
                    'LemonReportBundle:Default:view.html.twig', 
                    array(
                        'pager'     => $pagerfanta,
                        'report'    => $report,
                        'form'      => $form->createView(),
                        'query'     => \SqlFormatter::format($this->reportExecutor->getQuery()),
                        'columns'   => $columns,
                        'results'   => $results,
                    )
                ));
        }
    }

    public function setReportExecutor(Executor $reportExecutor)
    {
        $this->reportExecutor = $reportExecutor;
        return $this;
    }

    public function setReportLoader(LoaderInterface $reportLoader)
    {
        $this->reportLoader = $reportLoader;
        return $this;
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

    public function setTemplating(Templating $templating)
    {
        $this->templating = $templating;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
