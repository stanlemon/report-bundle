<?php
namespace Lemon\ReportBundle\Report;

use Lemon\ReportBundle\Report\Exception;
use Lemon\ReportBundle\Report\Executor;
use Lemon\ReportBundle\Report\Loader\RepositoryInterface;
use Psr\Log\LoggerInterface;

class Engine
{
    protected $report = null;
    protected $params = array();
    protected $results = array();
    protected $repository;
    protected $executor;
    protected $logger;

    public function load($id)
    {
        if (null === ($this->report = $this->repository->findById($id))) {
            throw new Exception("Report does not exist!");
        }
        return $this->report;
    }

    public function with($params = array())
    {
        $this->params = $params;
        return $this;
    }

    public function run()
    {
        $this->results = $this->executor->setReport(
            $this->report
        )->execute($this->params);

        return $this;
    }

    public function results()
    {
        return $this->results;
    }

    public function free()
    {
        $this->report = null;
        $this->params = array();
        $this->results = array();
    }

    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    public function setExecutor(Executor $executor)
    {
        $this->executor = $executor;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
