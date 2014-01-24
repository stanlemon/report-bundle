<?php

namespace Lemon\ReportBundle\Report\Loader;

use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

use Lemon\ReportBundle\Report\Loader\LoaderInterface;

class OrmRepository extends EntityRepository implements RepositoryInterface
{
    protected $logger;

    public function findById($id)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('r, rp')
            ->from('Lemon\ReportBundle\Entity\Report', 'r')
            ->leftJoin('r.parameters', 'rp', 'WITH', 'rp.active = true')
            ->where('r.active = true')
        ;

        if (is_numeric($id)) {
            $qb->andWhere("r.id = :id");
        } else {
            $qb->andWhere("r.slug = :id");
        }

        $query = $qb->getQuery();

        $query->setParameters(array(
            'id' => $id
        ));

        $report = $query->getOneOrNullResult();

        return $report;
    }

    public function findAll()
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('r, rp')
            ->from('Lemon\ReportBundle\Entity\Report', 'r')
            ->leftJoin('r.parameters', 'rp', 'WITH', 'rp.active = true')
            ->where('r.active = true')
            ->orderBy('r.name')
        ;

        $query = $qb->getQuery();

        $reports = $query->getResult();

        return $reports;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
