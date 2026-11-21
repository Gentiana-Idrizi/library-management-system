<?php

declare(strict_types=1);

namespace Library\Command;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\Book;
use Library\Entity\Role;
use Library\Entity\User;
use Library\Repository\UserRepository;
use Library\Service\AuditLogger;
use Library\Service\ClockInterface;
use Library\Service\PasswordHasher;
use Library\Service\SettingsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SeedCommand extends Command
{
    private UserRepository $users;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SettingsService $settings,
        private readonly PasswordHasher $hasher,
        private readonly ClockInterface $clock,
        private readonly AuditLogger $audit
    ) {
        parent::__construct('library:seed');
        $this->users = $em->getRepository(User::class);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Insert default settings, the first Super Admin and (optionally) demo data.')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Super Admin email', 'ardian@admin.com')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'Super Admin password', 'Admin123!')
            ->addOption('admin-name', null, InputOption::VALUE_REQUIRED, 'Super Admin name', 'Ardian Hoxha')
            ->addOption('demo', null, InputOption::VALUE_NONE, 'Also create demo operator, students and books');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->settings->ensureDefaults();
        $output->writeln($created === []
            ? 'Settings: already present.'
            : 'Settings: inserted ' . implode(', ', $created) . '.');

        $hasAdmin = (int) $this->em->createQuery(
            'SELECT COUNT(u.id) FROM ' . User::class . ' u WHERE u.role = :role AND u.deletedAt IS NULL'
        )->setParameter('role', Role::SuperAdmin->value)->getSingleScalarResult() > 0;

        if ($hasAdmin) {
            $output->writeln('Super Admin: already exists.');
        } else {
            $email = (string) $input->getOption('admin-email');
            $this->createUser((string) $input->getOption('admin-name'), $email, (string) $input->getOption('admin-password'), Role::SuperAdmin);
            $output->writeln(sprintf('Super Admin: created <info>%s</info> / <info>%s</info>', $email, $input->getOption('admin-password')));
        }

        if ($input->getOption('demo')) {
            $this->seedDemo($output);
        }

        $this->em->flush();
        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }

    private function seedDemo(OutputInterface $output): void
    {
        $accounts = [
            ['Olta Krasniqi', 'olta@operator.com', 'Operator123!', Role::Operator],
            ['Arta Bajrami', 'artabajrami@student.com', 'Student123!', Role::Student],
            ['Gentiana Idrizi', 'gentianaidrizi@student.com', 'Student123!', Role::Student],
        ];
        foreach ($accounts as [$name, $email, $password, $role]) {
            if ($this->users->emailExists($email)) {
                continue;
            }
            $this->createUser($name, $email, $password, $role);
            $output->writeln(sprintf('Demo %s: <info>%s</info> / <info>%s</info>', $role->value, $email, $password));
        }

        $books = [
            ['Clean Code', 'Robert C. Martin', '9780132350884', 'Software Engineering', 3],
            ['The Pragmatic Programmer', 'Andrew Hunt, David Thomas', '9780135957059', 'Software Engineering', 2],
            ['Design Patterns', 'Erich Gamma et al.', '9780201633610', 'Software Engineering', 1],
            ['Introduction to Algorithms', 'Thomas H. Cormen et al.', '9780262046305', 'Computer Science', 4],
            ['Database System Concepts', 'Abraham Silberschatz et al.', '9780078022159', 'Computer Science', 2],
            ['Electric Machinery Fundamentals', 'Stephen J. Chapman', '9780073529547', 'Electrical Engineering', 3],
            ['Power System Analysis', 'Hadi Saadat', '9780984543861', 'Electrical Engineering', 1],
            ['Sapiens', 'Yuval Noah Harari', '9780062316097', 'History', 5],
            ['1984', 'George Orwell', '9780451524935', 'Fiction', 0],
        ];
        $repo  = $this->em->getRepository(Book::class);
        $count = 0;
        foreach ($books as [$title, $author, $isbn, $category, $stock]) {
            if ($repo->findOneBy(['isbn' => $isbn]) !== null) {
                continue;
            }
            $this->em->persist(new Book($title, $author, $isbn, $category, $stock, $this->clock->now()));
            $count++;
        }
        $output->writeln(sprintf('Demo books: %d added.', $count));
    }

    private function createUser(string $name, string $email, string $password, Role $role): void
    {
        $user = new User($name, $email, $this->hasher->hash($password), $role, $this->clock->now());
        $this->em->persist($user);
        $this->em->flush();
        $this->audit->log(null, AuditLogger::USER_CREATED, 'user', $user->getId(), [
            'email'  => $user->getEmail(),
            'role'   => $role->value,
            'source' => 'seed',
        ]);
    }
}
