<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Cli;

use BitBag\SyliusMolliePlugin\Repository\MollieSubscriptionRepositoryInterface;
use BitBag\SyliusMolliePlugin\Transitions\MollieSubscriptionProcessingTransitions;
use BitBag\SyliusMolliePlugin\Transitions\MollieSubscriptionTransitions;
use SM\Factory\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class BeginProcessingSubscriptions extends Command
{
    public const COMMAND_NAME = 'mollie:subscription:begin-processing';

    public const COMMAND_ID = 'mollie:subscription:begin-processing';

    /** @var SymfonyStyle */
    private $io;

    private MollieSubscriptionRepositoryInterface $mollieSubscriptionRepository;

    private Factory $stateMachineFactory;

    public function __construct(
        MollieSubscriptionRepositoryInterface $mollieSubscriptionRepository,
        Factory $stateMachineFactory
    ) {
        parent::__construct(self::COMMAND_NAME);
        $this->mollieSubscriptionRepository = $mollieSubscriptionRepository;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    protected function configure(): void
    {
        $this->setDescription('Begin processing subscriptions based on schedule.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start(self::COMMAND_ID);

        $this->io->title('Mollie - subscription processing');

        try {
            $this->io->writeln('Processing...');

            $subscriptions = $this->mollieSubscriptionRepository->findScheduledSubscriptions();
            foreach ($subscriptions as $subscription) {
                $graph = $this->stateMachineFactory->get($subscription, MollieSubscriptionTransitions::GRAPH);

                if ($graph->can(MollieSubscriptionTransitions::TRANSITION_PROCESS)) {
                    $graph->apply(MollieSubscriptionTransitions::TRANSITION_PROCESS);

                    $processingGraph = $this->stateMachineFactory->get($subscription, MollieSubscriptionProcessingTransitions::GRAPH);
                    if ($processingGraph->can(MollieSubscriptionProcessingTransitions::TRANSITION_SCHEDULE)) {
                        $processingGraph->apply(MollieSubscriptionProcessingTransitions::TRANSITION_SCHEDULE);
                    }

                    $this->mollieSubscriptionRepository->add($subscription);
                }
            }

            $this->io->success('Successfully marked scheduled subscriptions');
        } catch (\Exception $exception) {
            $this->io->error(
                \sprintf('An error has occurred during send payment link process. (%s)', $exception->getMessage())
            );

            return 1;
        }

        $event = $stopwatch->stop(self::COMMAND_ID);

        if ($output->isVerbose()) {
            $this->io->comment(
                \sprintf(
                    'Duration: %.2f ms / Memory: %.2f MB',
                    $event->getDuration(),
                    $event->getMemory() / (1024 ** 2)
                )
            );
        }

        return 0;
    }
}
