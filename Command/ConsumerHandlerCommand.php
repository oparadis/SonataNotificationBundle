<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\NotificationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

use Sonata\NotificationBundle\Model\MessageInterface;
use Sonata\NotificationBundle\Consumer\ConsumerInterface;

class ConsumerHandlerCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('sonata:notification:start');
        $this->setDescription('Listen for incoming messages');
        $this->addOption('iteration', 'i', InputOption::VALUE_OPTIONAL ,'Only run n iterations before exiting', false);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Checking listeners');
        foreach($this->getDispatcher()->getListeners() as $type => $listeners) {
            $output->writeln(sprintf(" - %s", $type));
            foreach ($listeners as $listener) {
                if (!$listener[0] instanceof ConsumerInterface) {
                    throw new \RuntimeException(sprintf('The registered service does not implement the ConsumerInterface (class=%s', get_class($listener[0])));
                }

                $output->writeln(sprintf('   > %s', get_class($listener[0])));
            }
        }

        $backend = $this->getBackend();

        $output->writeln('Initialize backend');
        // initialize the backend
        $backend->initialize();

        $output->writeln(sprintf("<info>Starting the backend handler</info> - %s", get_class($backend)));

        $dispatcher = $this->getDispatcher();

        $startMemoryUsage = memory_get_usage(true);
        $i = 0;
        foreach($backend->getIterator() as $message) {
            $i++;
            if (!$message->getType()) {
                $output->write("<error>Skipping : no type defined </error>");
                continue;
            }

            $date = new \DateTime();
            $output->write(sprintf("[%s] <info>%s</info> : ", $date->format('r'), $message->getType(), $i));
            $memoryUsage = memory_get_usage(true);
            try {

                $start = microtime(true);
                $backend->handle($message, $dispatcher);

                $currentMemory = memory_get_usage(true);

                $output->writeln(sprintf("<comment>OK! </comment> - %0.04fs, %ss, %s, %s - %s = %s, %0.02f%%",
                    microtime(true) - $start,
                    $date->format('U') - $message->getCreatedAt()->format('U'),
                    $this->formatMemory($currentMemory - $memoryUsage),
                    $this->formatMemory($currentMemory),
                    $this->formatMemory($startMemoryUsage),
                    $this->formatMemory($currentMemory - $startMemoryUsage),
                    ($currentMemory - $startMemoryUsage) / $startMemoryUsage * 100
                ));

            } catch (\Exception $e) {
                if ($e instanceof \Sonata\NotificationBundle\Exception\HandlingException) {
                    $output->writeln(sprintf("<error>KO! - %s</error>", $e->getPrevious()->getMessage()));
                } else {
                    $output->writeln(sprintf("<error>KO! - %s</error>", $e->getMessage()));
                }
            }

            $this->optimize();

            if ($input->getOption('iteration') && $i >= (int) $input->getOption('iteration')) {
                $output->writeln('End of iteration cycle');
                return;
            }
        }
    }

    private function optimize()
    {
        if ($this->getContainer()->has('doctrine')) {
            $this->getContainer()->get('doctrine')->getEntityManager()->getUnitOfWork()->clear();
        }
    }

    /**
     * @param $memory
     * @return string
     */
    private function formatMemory($memory)
    {
        if ($memory < 1024) {
            return $memory."b";
        } elseif ($memory < 1048576) {
            return round($memory / 1024, 2)."Kb";
        } else {
            return round($memory / 1048576, 2)."Mb";
        }
    }

    /**
     * @return \Sonata\NotificationBundle\Backend\BackendInterface
     */
    private function getBackend()
    {
        return $this->getContainer()->get('sonata.notification.backend');
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private function getDispatcher()
    {
        return $this->getContainer()->get('sonata.notification.dispatcher');
    }

    /**
     * @return \Sonata\NotificationBundle\Model\MessageManagerInterface
     */
    private function getMessageManager()
    {
        return $this->getContainer()->get('sonata.notification.manager.message');
    }
}