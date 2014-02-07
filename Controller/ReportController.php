<?php

namespace Lemon\ReportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormFactory;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\View\TwitterBootstrapView as PagerView;

use Lemon\ReportBundle\Report\Executor;
use Lemon\ReportBundle\Report\ColumnBuilder;
use Lemon\ReportBundle\Report\Loader\RepositoryInterface;
use Lemon\ReportBundle\Form\ReportParameterConverter;
use Lemon\ReportBundle\Report\Exception as ReportException;


class ReportController
{
    protected $twig;
    protected $formFactory;
    protected $connection;
    protected $logger;
    protected $serializer;
    protected $router;
    protected $reportExecutor;
    protected $reportLoader;
    protected $reportEngine;
    protected $debug;

    public function listAction()
    {
        $reports = $this->reportLoader->findAll();

        return new Response($this->twig->render(
            '@LemonReport/Default/list.html.twig', 
            array(
                'reports'   => $reports,
            )
        ));
    }

    protected function loadReport(Request $request, $id)
    {
        try {
            $report = $this->reportEngine->load($id);

            $formBuilder = $this->reportParameterConverter->createFormBuilder(
                $report,
                $request->getSession()->get('report_' . $report->getSlug()) ?: array()
            );

            $form = $formBuilder->getForm();

            if ($request->isMethod('post')) {
                $form->bind($request);

                $request->getSession()->set('report_' . $report->getSlug(), $form->getData());
            }

            $results = $this->reportEngine
                ->with($form->getData())
                ->run()
                ->results()
            ;

            return array($report, $results, $form);
        } catch (ReportException $e) {
            throw new NotFoundHttpException("Report not found!");
        } catch (\Exception $e) {
            $request->getSession()->set('report_' . $report->getSlug(), array());
            throw $e;
        }
    }

    protected function reportAction($type, Request $request, $id)
    {
        list($report, $results) = $this->loadReport($request, $id);

        $class = 'Lemon\\ReportBundle\\Report\\Output\\' . ucfirst($type);

        $output = new $class($results);
        $response = new Response($output->render());
        $response->headers->set("Content-type", "application/{$type}");
        return $response;
    }

    public function csvAction(Request $request, $id)
    {
        return $this->reportAction('csv', $request, $id);
    }

    public function jsonAction(Request $request, $id)
    {
        return $this->reportAction('json', $request, $id);
    }

    public function xmlAction(Request $request, $id)
    {
        return $this->reportAction('xml', $request, $id);
    }

    public function viewAction(Request $request, $id, $page = 1)
    {
        list($report, $results, $form) = $this->loadReport($request, $id);

        $adapter = new ArrayAdapter($results);

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(25);

        try {
            $pagerfanta->setCurrentPage($page);
        } catch (NotValidCurrentPageException $e) {
            throw new NotFoundHttpException();
        }

        $columnBuilder = new ColumnBuilder($results);
        $columns = $columnBuilder->build();

        $view = new PagerView();
        $router = $this->router;
        $pager = $view->render($pagerfanta, function($page) use($id, $router) {
            return $router->generate('lemon_report_view_page', array('page' => $page, 'id' => $id));
        }, array('proximity' => 3));

        return new Response($this->twig->render(
            '@LemonReport/Default/view.html.twig', 
            array(
                'debug'     => $request->query->get('debug') != null ? $request->query->get('debug') : $this->debug,
                'pager'     => $pager,
                'total'     => $pagerfanta->getNbResults(),
                'results'   => $pagerfanta->getCurrentPageResults(),
                'report'    => $report,
                'form'      => $form->createView(),
                'query'     => \SqlFormatter::format($this->reportExecutor->getQuery()),
                'columns'   => $columns,
            )
        ));
    }

    public function setReportExecutor(Executor $reportExecutor)
    {
        $this->reportExecutor = $reportExecutor;
        return $this;
    }

    public function setReportLoader(RepositoryInterface $reportLoader)
    {
        $this->reportLoader = $reportLoader;
        return $this;
    }

    public function setReportParameterConverter(ReportParameterConverter $reportParameterConverter)
    {
        $this->reportParameterConverter = $reportParameterConverter;
        return $this;
    }
    
    public function setRouter($router)
    {
        $this->router = $router;
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

    public function setTwig(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setReportEngine($reportEngine)
    {
        $this->reportEngine = $reportEngine;
        return $this;
    }
    
    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }
}
