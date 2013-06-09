<?php

namespace Lemon\ReportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Psr\Log\LoggerInterface;

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
class DefaultController extends Controller
{
    protected $logger;
    protected $serializer;
    protected $reportExecutor;
    protected $reportLoader;

    /**
     * @Route("/", name="report_list")
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
     * @Route("/view/{id}.{_format}", name="report_view", defaults={"_format" = "html"})
     */
    public function viewAction(Request $request, $id)
    {
        $report = $this->reportLoader->findById($id);

        if (null === $report) {
            throw new NotFoundHttpException("Report does not exist!");
        }

        $converter = new ReportParameterConverter($report, $request);
        $form = $converter->createForm();

        $results = $this->reportExecutor->setReport($report)
            ->execute();

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
                return $this->render('LemonReportBundle:Default:view.html.twig', array(
                    'report'    => $report,
                    'form'      => $form->createView(),
                    'query'     => \SqlFormatter::format($this->reportExecutor->getQuery()),
                    'columns'   => $columns,
                    'results'   => $results,
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

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
