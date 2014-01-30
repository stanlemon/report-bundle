<?php
namespace Lemon\ReportBundle\Report;

use Swift_Mailer;
use Swift_Attachment;
use Lemon\ReportBundle\Report\Output\Csv;

class Mailer 
{
    protected $engine;
    protected $mailer;
    protected $report;
    protected $results;
    protected $from;
    protected $to;

    public function __construct(Engine $engine, Swift_Mailer $mailer)
    {
        $this->engine = $engine;
        $this->mailer = $mailer;
    }

    public function run($reportId, $params)
    {
        $this->report = $this->engine->load(
            $reportId
        );

        $this->results = $this->engine
            ->with($params)
            ->run()
            ->results()
        ;

        return $this;
    }

    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    public function to($to)
    {
        $this->to = $to;
        return $this;
    }

    public function send()
    {
        $message = new \Swift_Message();
        $message
            ->setSubject($this->report->getName() . ' for ' . date('m/d/y'))
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setBody("Your report \"{$this->report->getName()}\"  has been run and is attached to this email.")
            ->attach($this->makeAttachment())
        ;

        $this->mailer->send($message);
    }

    protected function makeAttachment()
    {
        return Swift_Attachment::newInstance(
            $this->getRenderer()->render(),
            $this->report->getSlug() . '-' . date('Ymd') . '.csv', 
            'text/csv'
        );
    }

    public function getRenderer()
    {
        return new Csv($this->results);
    }
}
