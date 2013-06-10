<?php
namespace Lemon\ReportBundle\Report\Loader;

interface LoaderInterface {
    
    public function findById($id);

    public function findAll();
}
