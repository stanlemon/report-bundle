<?php
namespace Lemon\ReportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Swift_Mailer;
use InvalidArgumentException;
use Lemon\ReportBundle\Report\Mailer as ReportMailer;

class EmailReportCommand extends ContainerAwareCommand
{
    protected $input;
    protected $output;
    protected $reportEngine;
    protected $mailer;
    protected $report;
    protected $from;
    protected $to;
    protected $params;

    protected function configure()
    {
        $this
            ->setName('report:email')
            ->setDescription('Email the results of a report')
            ->addOption('report', 'r', InputOption::VALUE_REQUIRED, 'Report to run the results for')
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED, 'Email address to send the report from')
            ->addOption('to', 't', InputOption::VALUE_REQUIRED, 'Email address to send the report to')
            ->addOption('param', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Parameters to be utilized for the report', array())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->reportEngine = $this->getContainer()->get('lemon_report.report_engine');
        $this->mailer = new Swift_Mailer($this->getContainer()->get('swiftmailer.transport.real'));
        $this->report = $input->getOption('report');
        $this->from = $input->getOption('from');
        $this->to = $input->getOption('to');
        $this->params = $this->buildParams($input->getOption('param'));

        $this->command();
    }

    public function buildParams($_params)
    {
        $params = array();

        foreach ($_params as $param) {
            list($key, $value) = explode('=', $param);
            $params[$key] = $value;
        }

        return $params;
    }

    protected function validate()
    {
        if (empty($this->report)) {
            throw new InvalidArgumentException("Report must be specified");
        }

        if (empty($this->from)) {
            throw new InvalidArgumentException("From address must be specified");
        }

        if (empty($this->to)) {
            throw new InvalidArgumentException("To address must be specified");
        }
    }

    protected function command()
    {
        $this->validate();

        $mailer = new ReportMailer($this->reportEngine, $this->mailer);
        $mailer
            ->run($this->report, $this->params)
            ->from($this->from)
            ->to($this->to)
            ->send()
        ;
    }
}
