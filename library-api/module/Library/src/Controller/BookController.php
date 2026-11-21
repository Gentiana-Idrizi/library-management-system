<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Service\AuthContext;
use Library\Service\BookService;
use Library\Service\Presenter;

class BookController extends AbstractApiController
{
    public function __construct(
        AuthContext $auth,
        private readonly BookService $books,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $this->currentUser();
        [$page, $limit] = $this->paging();
        $result         = $this->books->search(
            $this->query('q'),
            $this->query('category'),
            $this->queryBool('available') ?? false,
            $page,
            $limit
        );

        return $this->json($this->presenter->page($result, $this->presenter->book(...), $page, $limit));
    }

    public function get($id): Response
    {
        $this->currentUser();

        return $this->json($this->presenter->book($this->books->get((int) $id)));
    }

    public function categoriesAction(): Response
    {
        $this->allowMethods('GET');
        $this->currentUser();

        return $this->json(['items' => $this->books->categories()]);
    }

    public function create($data): Response
    {
        $actor = $this->requireStaff();

        return $this->json($this->presenter->book($this->books->create($actor, (array) $data)), 201);
    }

    public function update($id, $data): Response
    {
        $actor = $this->requireStaff();

        return $this->json($this->presenter->book($this->books->update($actor, (int) $id, (array) $data, partial: false)));
    }

    public function patch($id, $data): Response
    {
        $actor = $this->requireStaff();

        return $this->json($this->presenter->book($this->books->update($actor, (int) $id, (array) $data, partial: true)));
    }

    public function delete($id): Response
    {
        $actor = $this->requireStaff();
        $this->books->delete($actor, (int) $id);

        return $this->noContent();
    }
}
