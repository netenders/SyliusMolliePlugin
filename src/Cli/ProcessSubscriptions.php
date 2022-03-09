<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Cli;

use BitBag\SyliusMolliePlugin\Processor\SubscriptionProcessorInterface;
use BitBag\SyliusMolliePlugin\Repository\MollieSubscriptionRepositoryInterface;
use BitBag\SyliusMolliePlugin\Transitions\MollieSubscriptionPaymentProcessingTransitions;
use BitBag\SyliusMolliePlugin\Transitions\MollieSubscriptionProcessingTransitions;
use SM\Factory\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Webmozart\Assert\Assert;

class ProcessSubscriptions extends Command
{
    public const COMMAND_NAME = 'mollie:subscription:process';

    public const COMMAND_ID = 'mollie:subscription:process';

    /** @var SymfonyStyle */
    private $io;

    private MollieSubscriptionRepositoryInterface $mollieSubscriptionRepository;

    private Factory $stateMachineFactory;

    private SubscriptionProcessorInterface $subscriptionProcessor;

    private RouterInterface $router;

    public function __construct(
        MollieSubscriptionRepositoryInterface $mollieSubscriptionRepository,
        Factory $stateMachineFactory,
        SubscriptionProcessorInterface $subscriptionProcessor,
        RouterInterface $router
    ) {
        parent::__construct(self::COMMAND_NAME);
        $this->mollieSubscriptionRepository = $mollieSubscriptionRepository;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->subscriptionProcessor = $subscriptionProcessor;
        $this->router = $router;
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

            $subscriptions = $this->mollieSubscriptionRepository->findProcessableSubscriptions();
            $routerContext = $this->router->getContext();
            foreach ($subscriptions as $subscription) {
                $processingGraph = $this->stateMachineFactory->get($subscription, MollieSubscriptionProcessingTransitions::GRAPH);

                if (false === $processingGraph->can(MollieSubscriptionProcessingTransitions::TRANSITION_PROCESS)) {
                    continue;
                }

                $paymentGraph = $this->stateMachineFactory->get($subscription, MollieSubscriptionPaymentProcessingTransitions::GRAPH);

                if (false === $paymentGraph->can(MollieSubscriptionPaymentProcessingTransitions::TRANSITION_BEGIN)) {
                    continue;
                }

                $paymentGraph->apply(MollieSubscriptionPaymentProcessingTransitions::TRANSITION_BEGIN);

                $configuration = $subscription->getSubscriptionConfiguration();
                $routerContext->setHost($configuration->getHostName());
                $firstOrder = $subscription->getFirstOrder();

                Assert::notNull($firstOrder);
                $routerContext->setParameter('_locale', $firstOrder->getLocaleCode());
                $this->subscriptionProcessor->processNextPayment($subscription);

                $processingGraph->apply(MollieSubscriptionProcessingTransitions::TRANSITION_PROCESS);
                $this->mollieSubscriptionRepository->add($subscription);
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
