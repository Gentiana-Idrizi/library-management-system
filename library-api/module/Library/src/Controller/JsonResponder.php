<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Laminas\Stdlib\ResponseInterface;
use Library\Exception\ApiException;

final class JsonResponder
{
    public static function json(ResponseInterface $response, mixed $data, int $status = 200): Response
    {
        $response = $response instanceof Response ? $response : new Response();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
        $response->setContent($status === 204 ? '' : json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        ));

        return $response;
    }

    public static function error(ResponseInterface $response, ApiException $e): Response
    {
        $body = ['code' => $e->getErrorCode(), 'message' => $e->getMessage()];
        if ($e->getDetails() !== []) {
            $body['details'] = $e->getDetails();
        }

        return self::json($response, ['error' => $body], $e->getHttpStatus());
    }

    public static function errorBody(ResponseInterface $response, int $status, string $code, string $message, array $details = []): Response
    {
        $body = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $body['details'] = $details;
        }

        return self::json($response, ['error' => $body], $status);
    }
}
