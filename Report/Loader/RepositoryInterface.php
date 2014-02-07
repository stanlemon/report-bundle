<?php
namespace Lemon\ReportBundle\Report\Loader;

interface RepositoryInterface
{
    public function findById($id);

    public function findAll();
}
