<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:create',
    description: 'Creates a new admin user for the QR management panel.',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Create Admin User');

        $username = $io->ask('Username');
        if (!$username) {
            $io->error('Username cannot be empty.');
            return Command::FAILURE;
        }

        $existing = $this->em->getRepository(AdminUser::class)->findOneBy(['username' => $username]);
        if ($existing) {
            $io->error("User \"{$username}\" already exists.");
            return Command::FAILURE;
        }

        $password = $io->askHidden('Password (input hidden)');
        if (!$password || strlen($password) < 8) {
            $io->error('Password must be at least 8 characters.');
            return Command::FAILURE;
        }

        $confirm = $io->askHidden('Confirm password');
        if ($password !== $confirm) {
            $io->error('Passwords do not match.');
            return Command::FAILURE;
        }

        $user = (new AdminUser())
            ->setUsername($username)
            ->setPasswordHash(password_hash($password, PASSWORD_BCRYPT));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Admin user \"{$username}\" created successfully.");

        return Command::SUCCESS;
    }
}
