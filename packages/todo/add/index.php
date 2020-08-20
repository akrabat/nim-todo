<?php

declare(strict_types=1);

use App\AppContainer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Todo\TodoTransformer;
use Todo\TodoMapper;

/**
 * POST /todos
 */
function main(array $args) : array
{
    try {
        $body = $args['__ow_body'] ?? '';
        if (!$body) {
            throw new InvalidArgumentException('Missing body', 400);
        }
        $data = json_decode(base64_decode($body), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Missing body', 400);
        }

        $container = new AppContainer($args);
        /** @var TodoMapper $mapper */
        $mapper = $container[TodoMapper::class];

        $todo = $mapper->insert($data);

        $transformer = $container[TodoTransformer::class];
        $resource = new Item($todo, $transformer, 'todos');
        $fractal = $container[Manager::class];

        return [
            'statusCode' => 200,
            'body' => $fractal->createData($resource)->toArray(),
        ];
    } catch (\Throwable $e) {
        error_log((string)$e);
        $code = $e->getCode() < 400 ? 500 : $e->getCode();
        return [
            'statusCode' => $code,
            'body' => ['error' => $e->getMessage()]];
    }
}
