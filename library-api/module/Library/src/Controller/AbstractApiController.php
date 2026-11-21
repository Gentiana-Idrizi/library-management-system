<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Library\Entity\Role;
use Library\Entity\User;
use Library\Exception\MethodNotAllowedException;
use Library\Exception\ValidationException;
use Library\Service\AuthContext;

abstract class AbstractApiController extends AbstractRestfulController
{
    public function __construct(protected readonly AuthContext $auth)
    {
    }

    protected function currentUser(): User
    {
        return $this->auth->requireUser();
    }

    protected function requireRole(Role ...$roles): User
    {
        return $this->auth->requireRole(...$roles);
    }

    protected function requireStaff(): User
    {
        return $this->auth->requireStaff();
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return JsonResponder::json($this->getResponse(), $data, $status);
    }

    protected function noContent(): Response
    {
        return JsonResponder::json($this->getResponse(), null, 204);
    }

    protected function httpRequest(): Request
    {
        $request = $this->getRequest();

        return $request;
    }

    protected function allowMethods(string ...$methods): void
    {
        if (! in_array($this->httpRequest()->getMethod(), $methods, true)) {
            throw new MethodNotAllowedException();
        }
    }

    protected function jsonBody(): array
    {
        $content = trim((string) $this->httpRequest()->getContent());
        if ($content === '') {
            return [];
        }
        $data = json_decode($content, true);
        if (! is_array($data)) {
            throw new ValidationException(['body' => 'Request body must be a JSON object.']);
        }

        return $data;
    }

    protected function routeId(): int
    {
        return (int) $this->params()->fromRoute('id', 0);
    }

    protected function query(string $name): ?string
    {
        $value = $this->params()->fromQuery($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function queryInt(string $name): ?int
    {
        $value = $this->query($name);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }

    protected function queryBool(string $name): ?bool
    {
        $value = $this->query($name);

        return $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function paging(int $defaultLimit = 20): array
    {
        $page  = max(1, $this->queryInt('page') ?? 1);
        $limit = min(100, max(1, $this->queryInt('limit') ?? $defaultLimit));

        return [$page, $limit];
    }

    public function getList()
    {
        throw new MethodNotAllowedException();
    }

    public function get($id)
    {
        throw new MethodNotAllowedException();
    }

    public function create($data)
    {
        throw new MethodNotAllowedException();
    }

    public function update($id, $data)
    {
        throw new MethodNotAllowedException();
    }

    public function patch($id, $data)
    {
        throw new MethodNotAllowedException();
    }

    public function delete($id)
    {
        throw new MethodNotAllowedException();
    }

    public function replaceList($data)
    {
        throw new MethodNotAllowedException();
    }

    public function patchList($data)
    {
        throw new MethodNotAllowedException();
    }

    public function deleteList($data)
    {
        throw new MethodNotAllowedException();
    }
}
