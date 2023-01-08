<?php

declare(strict_types=1);

namespace Karaev\Cats\Console\Command;

use Psr\Log\LoggerInterface;
use Karaev\Cats\Model\SwapProductPicture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CatInvasion
 * @package Karaev\Cats\Console\Command
 */
class CatInvasion extends Command
{
    private LoggerInterface $logger;

    private SwapProductPicture $swapProductPicture;

    /**
     * @param SwapProductPicture $swapProductPicture
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        SwapProductPicture $swapProductPicture,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->swapProductPicture = $swapProductPicture;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('cats:invasion');
        $this->setDescription('Swapping products pictures to random cats pictures.
                                                          ∧＿∧
                                                         (｡･ω･｡)つ━☆・*。
                                                         ⊂　　 /　　 ・゜+.
                                                         しーＪ　　　°。+'
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->swapProductPicture->run();

            $output->writeln('
                <info>
                        •.,¸,.•*`•.,¸¸,.•*¯ ╭━━━━╮
                        •.,¸,.•*¯`•.,¸,.•*¯.|:::::::::: /\___/\
                        •.,¸,.•*¯`•.,¸,.•* <|:::::::::(｡ ●ω●｡)
                        •.,¸,.•¯•.,¸,.•╰ * し------し---   Ｊ
                </info>');

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            $output->writeln('<error>Something went wrong ¯\_(ツ)_/¯</error>');
        }
    }
}
