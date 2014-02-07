<?php

namespace Lemon\ReportBundle\Report\Loader;

use Psr\Log\LoggerInterface;

class ChainRepository implements RepositoryInterface
{
    protected $repositories = array();
    protected $logger;

    public function add(RepositoryInterface $repository)
    {
        $this->repositories[] = $repository;
        return $this;
    }

    public function findById($id)
    {
        foreach ($this->repositories as $repository) {
            if (false !== ($report = $repository->findById($id))) {
                return $report;
            }
        }

        return null;
    }

    public function findAll()
    {
        $reports = array();

        foreach ($this->repositories as $repository) {
            $reports = array_merge(
                $reports,
                $repository->findAll()
            );
        }

        return $reports;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
