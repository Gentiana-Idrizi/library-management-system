<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\Book;
use Library\Entity\Loan;
use Library\Entity\User;
use Library\Exception\BusinessRuleException;
use Library\Exception\NotFoundException;
use Library\Repository\BookRepository;
use Library\Repository\LoanRepository;
use Library\Validator\Input;

class BookService
{
    use Transactional;

    private BookRepository $books;
    private LoanRepository $loans;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications
    ) {
        $this->books = $em->getRepository(Book::class);
        $this->loans = $em->getRepository(Loan::class);
    }

    protected function entityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function search(?string $q, ?string $category, bool $availableOnly, int $page, int $limit): array
    {
        return $this->books->search($q, $category, $availableOnly, $page, $limit);
    }

    public function categories(): array
    {
        return $this->books->categories();
    }

    public function get(int $id): Book
    {
        return $this->books->findActive($id) ?? throw new NotFoundException('Book not found.');
    }

    public function create(User $actor, array $data): Book
    {
        $values = $this->validate($data, partial: false);

        return $this->transactional(function () use ($actor, $values): Book {
            $now      = $this->clock->now();
            $existing = $this->books->findByIsbn($values['isbn']);

            if ($existing !== null && ! $existing->isDeleted()) {
                throw new BusinessRuleException('ISBN_TAKEN', 'A book with this ISBN already exists.', ['bookId' => $existing->getId()]);
            }

            if ($existing !== null) {
                $book = $existing;
                $book->restore($now);
                $book->update($values, $now);
            } else {
                $book = new Book($values['title'], $values['author'], $values['isbn'], $values['category'], $values['stock'], $now);
                $this->em->persist($book);
            }
            $this->em->flush();

            $this->audit->log($actor, AuditLogger::BOOK_CREATED, 'book', $book->getId(), [
                'title'    => $book->getTitle(),
                'isbn'     => $book->getIsbn(),
                'stock'    => $book->getStock(),
                'restored' => $existing !== null,
            ]);
            $this->notifications->notifyStaffAboutStock($book);

            return $book;
        });
    }

    public function update(User $actor, int $id, array $data, bool $partial): Book
    {
        $values = $this->validate($data, $partial);

        return $this->transactional(function () use ($actor, $id, $values): Book {
            $book = $this->get($id);

            if (isset($values['isbn']) && $values['isbn'] !== $book->getIsbn()) {
                $other = $this->books->findByIsbn($values['isbn']);
                if ($other !== null) {
                    throw new BusinessRuleException('ISBN_TAKEN', 'A book with this ISBN already exists.', ['bookId' => $other->getId()]);
                }
            }

            $before = $this->snapshot($book);
            $book->update($values, $this->clock->now());
            $after   = $this->snapshot($book);
            $changes = [];
            foreach ($after as $field => $value) {
                if ($before[$field] !== $value) {
                    $changes[$field] = ['old' => $before[$field], 'new' => $value];
                }
            }

            if ($changes !== []) {
                $this->audit->log($actor, AuditLogger::BOOK_UPDATED, 'book', $book->getId(), ['changes' => $changes]);
                if (isset($changes['stock'])) {
                    $this->notifications->notifyStaffAboutStock($book);
                }
            }

            return $book;
        });
    }

    public function delete(User $actor, int $id): void
    {
        $this->transactional(function () use ($actor, $id): void {
            $book = $this->get($id);
            $open = $this->loans->countOpenByBook($book);
            if ($open > 0) {
                throw new BusinessRuleException(
                    'BOOK_HAS_OPEN_LOANS',
                    sprintf('This book cannot be deleted: %d copy(ies) are still borrowed.', $open),
                    ['openLoans' => $open]
                );
            }
            $book->markDeleted($this->clock->now());
            $this->audit->log($actor, AuditLogger::BOOK_DELETED, 'book', $book->getId(), [
                'title' => $book->getTitle(),
                'isbn'  => $book->getIsbn(),
            ]);
        });
    }

    private function validate(array $data, bool $partial): array
    {
        $input  = new Input($data, $partial);
        $values = [
            'title'    => $input->string('title', 255),
            'author'   => $input->string('author', 255),
            'isbn'     => $input->string('isbn', 30),
            'category' => $input->string('category', 100),
            'stock'    => $input->int('stock', 0, 100000),
        ];

        if ($values['isbn'] !== null) {
            $isbn = strtoupper(preg_replace('/[\s-]+/', '', $values['isbn']) ?? '');
            if (! preg_match('/^(\d{9}[\dX]|\d{13})$/', $isbn)) {
                $input->addError('isbn', 'Must be a valid ISBN-10 or ISBN-13 (digits, hyphens allowed).');
            }
            $values['isbn'] = $isbn;
        }
        $input->assertValid();

        return array_filter($values, static fn($v) => $v !== null);
    }

    private function snapshot(Book $book): array
    {
        return [
            'title'    => $book->getTitle(),
            'author'   => $book->getAuthor(),
            'isbn'     => $book->getIsbn(),
            'category' => $book->getCategory(),
            'stock'    => $book->getStock(),
        ];
    }
}
