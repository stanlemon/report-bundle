<?php

namespace Lemon\ReportBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

use Lemon\ReportBundle\Report\Loader\LoaderInterface;

/**
 * ReportRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReportRepository extends EntityRepository implements LoaderInterface
{
    protected $logger;

    public function findById($id)
    {
        return $this->findOneById($id);
    }
    
    public function findByKey($key)
    {
        return $this->findOneByKey($key);
    }
    
    public function findAll()
    {
        return $this->findBy(array(
            'active' => 1
        ));
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
