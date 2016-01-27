<?php

namespace Lemon\ReportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Psr\Log\LoggerInterface;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\View\TwitterBootstrap3View as PagerView;
use Lemon\ReportBundle\Report\Engine;
use Lemon\ReportBundle\Report\ColumnBuilder;
use Lemon\ReportBundle\Form\ReportParameterConverter;
use Lemon\ReportBundle\Report\Exception as ReportException;
use Twig_Environment;
use Exception;

class ReportController
{
    protected $twig;
    protected $formFactory;
    protected $connection;
    protected $logger;
    protected $serializer;
    protected $router;
    protected $reportEngine;
    protected $reportParameterConverter;
    protected $debug;

    public function __construct(
        Engine $reportEngine,
        ReportParameterConverter $reportParameterConverter,
        Router $router,
        Twig_Environment $twig,
        LoggerInterface $logger = null,
        $debug = false
    ) {
        $this->reportEngine = $reportEngine;
        $this->reportParameterConverter = $reportParameterConverter;
        $this->router = $router;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function listAction()
    {
        $reports = $this->reportEngine->all();

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

            $form->handleRequest($request);


            if ($form->isSubmitted()) {
                $request->getSession()->set('report_' . $report->getSlug(), $form->getData());
            }

            $results = $this->reportEngine
                ->with($form->getData())
                ->run()
            ;

            return array($report, $results, $form);
        } catch (ReportException $e) {
            throw new NotFoundHttpException("Report not found!");
        } catch (Exception $e) {
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

        if ($type == 'csv') {
            $response->headers->set(
                'Content-Disposition',
                sprintf("attachment;filename=%s", $report->getSlug() . ".csv")
            );
        }

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
        $pager = $view->render($pagerfanta, function ($page) use ($id, $router) {
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
                'query'     => \SqlFormatter::format($this->reportEngine->query()),
                'columns'   => $columns,
            )
        ));
    }
}
