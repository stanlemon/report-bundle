<?php
namespace Lemon\ReportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Swift_Mailer;
use Swift_Attachment;
use InvalidArgumentException;
use Lemon\ReportBundle\Report\Mailer as ReportMailer;

class EmailReportCommand extends ContainerAwareCommand
{
    protected $input;
    protected $output;
    protected $reportEngine;
    protected $report;
    protected $from;
    protected $to;
    protected $params;
    protected $attachment;

    protected function configure()
    {
        $this
            ->setName('report:email')
            ->setDescription('Email the results of a report')
            ->addOption('report', 'r', InputOption::VALUE_REQUIRED, 'Report to run the results for')
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED, 'Email address to send the report from')
            ->addOption('to', 't', InputOption::VALUE_REQUIRED, 'Email address to send the report to')
            ->addOption('params', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Parameters to be utilized for the report', [])
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
        $this->params = $input->getOption('params'); // @todo

        $this->command();
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
            ->run($this->report)
            ->from($this->from)
            ->to($this->to)
            ->send()
        ;
    }
}
