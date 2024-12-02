<?php
/* src/Command/SanitizeDataCommand.php */

namespace App\Command;

use App\Entity\Speaker;
use App\Repository\SpeakerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sanitize-data',
    description: 'Sanitize speaker data (first_name, last_name, username and picture).',
    aliases: ['app:sanitize']
)]
class SanitizeDataCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(private SpeakerRepository $speakerRepository, private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $speakers = $this->speakerRepository->findAll();
        $this->io->progressStart(count($speakers));

        $this->entityManager->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            /** @var Speaker $speaker */
            foreach ($speakers as $speaker) {
                $this->io->progressAdvance();
                // fake user info
                $speaker->setLastName('Wick');
                $speaker->setFirstName('John');
                $speaker->setUsername(uniqid('john-wick-'));
                $speaker->setPicture('https://cdna.artstation.com/p/assets/images/images/004/943/296/large/andrey-pankov-neo.jpg?1487365474');
            }
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            $this->io->progressFinish();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return Command::SUCCESS;
    }
}