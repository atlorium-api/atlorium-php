<?php

declare(strict_types=1);

namespace Atlorium\Http;

/**
 * Подменный транспорт для тестов вашего кода: отдаёт заранее заданные ответы и запоминает запросы.
 *
 *     $transport = new MockTransport(Response::json(200, ['found' => true]));
 *     $client = new \Atlorium\Client(null, ['transport' => $transport]);
 *     $client->egrul->get('7707083893');
 *     $transport->getLastRequest()->getPath(); // «/api/egrul/7707083893»
 *
 * Элемент очереди — готовый Response, исключение (будет брошено) или callable(Request): Response.
 */
final class MockTransport implements TransportInterface
{
    /** @var list<Response|\Throwable|\Closure> */
    private array $queue = [];

    /** @var list<Request> */
    private array $requests = [];

    private ?\Closure $default = null;

    /** @param mixed ...$responses Response, исключение или callable(Request): Response */
    public function __construct(...$responses)
    {
        foreach ($responses as $response) {
            $this->append($response);
        }
    }

    /**
     * Добавляет ответ в конец очереди.
     *
     * @param mixed $response Response, исключение или callable(Request): Response
     */
    public function append($response): self
    {
        if ($response instanceof Response || $response instanceof \Throwable) {
            $this->queue[] = $response;
        } elseif (is_callable($response)) {
            $this->queue[] = \Closure::fromCallable($response);
        } else {
            throw new \InvalidArgumentException('Ответ MockTransport — Response, исключение или callable(Request): Response.');
        }

        return $this;
    }

    /**
     * Обработчик для всех запросов, пришедших после исчерпания очереди.
     *
     * @param callable $handler callable(Request): Response
     */
    public function setDefault(callable $handler): self
    {
        $this->default = \Closure::fromCallable($handler);

        return $this;
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue) ?? $this->default;
        if ($next === null) {
            throw new \LogicException(sprintf(
                'MockTransport: очередь ответов пуста, а пришёл запрос %s %s.',
                $request->getMethod(),
                $request->getUrl()
            ));
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }
        if ($next instanceof Response) {
            return $next;
        }

        $response = $next($request);
        if (!$response instanceof Response) {
            throw new \LogicException('MockTransport: обработчик должен вернуть ' . Response::class . '.');
        }

        return $response;
    }

    /** @return list<Request> все запросы по порядку */
    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getLastRequest(): ?Request
    {
        $count = count($this->requests);

        return $count === 0 ? null : $this->requests[$count - 1];
    }
}
