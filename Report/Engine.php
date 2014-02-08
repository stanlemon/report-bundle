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

    public function all()
    {
        return $this->repository->findAll();
    }

    public function load($id)
    {
        if (null === ($this->report = $this->repository->findById($id))) {
            throw new Exception("Report does not exist!");
        }
        $this->params = array();
        return $this->report;
    }

    public function with($params = array())
    {
        $this->params = $params;
        return $this;
    }

    public function run()
    {
        return $this->executor->setReport(
            $this->report
        )->execute($this->params);
    }

    public function query()
    {
        return $this->executor->getQuery();
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
