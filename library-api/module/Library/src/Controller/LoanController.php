<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Entity\LoanStatus;
use Library\Exception\ValidationException;
use Library\Service\AuthContext;
use Library\Service\LoanService;
use Library\Service\Presenter;

class LoanController extends AbstractApiController
{
    public function __construct(
        AuthContext $auth,
        private readonly LoanService $loans,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $actor  = $this->currentUser();
        $status = $this->query('status');
        $valid  = array_merge(['open'], array_map(static fn(LoanStatus $s) => $s->value, LoanStatus::cases()));
        if ($status !== null && ! in_array($status, $valid, true)) {
            throw new ValidationException(['status' => 'Must be one of: ' . implode(', ', $valid) . '.']);
        }

        [$page, $limit] = $this->paging();
        $result         = $this->loans->search($actor, [
            'userId' => $this->queryInt('userId'),
            'bookId' => $this->queryInt('bookId'),
            'status' => $status,
        ], $page, $limit);

        return $this->json($this->presenter->page($result, $this->presenter->loan(...), $page, $limit));
    }

    public function get($id): Response
    {
        return $this->json($this->presenter->loan($this->loans->get($this->currentUser(), (int) $id)));
    }

    public function create($data): Response
    {
        $actor  = $this->currentUser();
        $bookId = filter_var(((array) $data)['bookId'] ?? null, FILTER_VALIDATE_INT);
        if ($bookId === false || $bookId < 1) {
            throw new ValidationException(['bookId' => 'This field is required.']);
        }

        $loan = $this->loans->borrow($actor, $bookId);

        return $this->json([
            'message' => sprintf(
                'Borrowed "%s". Borrow fee €%s charged. Please return it by %s.',
                $loan->getBook()->getTitle(),
                $loan->getBorrowFee(),
                $loan->getDueAt()->format('Y-m-d')
            ),
            'loan'    => $this->presenter->loan($loan),
        ], 201);
    }

    public function returnAction(): Response
    {
        $this->allowMethods('POST');
        $loan    = $this->loans->returnLoan($this->currentUser(), $this->routeId());
        $penalty = $loan->getPenalty();

        return $this->json([
            'message' => $penalty === null
                ? 'Book returned on time. The loan is closed.'
                : sprintf(
                    'Book returned %d day(s) late. A penalty of €%s must be paid to close the loan.',
                    $penalty->getDaysLate(),
                    $penalty->getAmount()
                ),
            'loan'    => $this->presenter->loan($loan),
        ]);
    }
}
