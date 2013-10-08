<?php
namespace Lemon\ReportBundle\Report;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Lemon\ReportBundle\Entity\Report;
use Lemon\ReportBundle\Report\Query\Renderer as QueryRenderer;

class Executor 
{
    protected $logger;
    protected $connection;
    protected $queryRenderer;

    protected $report;

    public function __construct()
    {
    }

    public function setReport(Report $report)
    {
        $this->report = $report;
        return $this;
    }

    public function execute($values = array()) 
    {
        $this->start = microtime(true);
        
        //SqlFormatter::splitQuery() @todo

        $this->queryRenderer->render($this->report);

        $stmt = $this->connection->prepare(
            $this->queryRenderer->getQuery()
        );
        
        foreach ($this->queryRenderer->getParameters() as $parameter) {
            $stmt->bindValue($parameter->getName(), $values[$parameter->getName()]);
        }
        
        $stmt->execute();

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->end = microtime(true);

        return $results;
    }

    public function getExecutionTime()
    {
        return $this->end - $this->start;
    }

    public function getQuery()
    {
        return $this->queryRenderer->getQuery();
    }

    public function setQueryRenderer(QueryRenderer $queryRenderer)
    {
        $this->queryRenderer = $queryRenderer;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}