<?php

declare(strict_types=1);

use App\AppContainer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Todo\TodoMapper;
use Todo\TodoTransformer;

/**
 * GET /todos
 */
function main(array $args) : array
{
    try {
        $container = new AppContainer($args);
        /** @var TodoMapper $mapper */
        $mapper = $container[TodoMapper::class];

        $todos = $mapper->fetchAll();

        $transformer = $container[TodoTransformer::class];
        $resource = new Collection($todos, $transformer);
        $fractal = $container[Manager::class];

        return [
            'statusCode' => 200,
            'body' => $fractal->createData($resource)->toArray()['data'],
        ];
    } catch (\Throwable $e) {
        echo "$e\n";
        $code = $e->getCode() < 400 ? 500 : $e->getCode();
        return [
            'statusCode' => $code,
            'body' => ['error' => $e->getMessage()]
        ];
    }
}
/*
function main(array $args): array
{
    $todo = new Todo(['id' => 1, 'title' => 'Have a snack']);
    $todo2 = new Todo(['id' => 2, 'title' => 'Make a cup of tea']);

    print_r($args);
    echo "ENV:\n";
    print_r($_ENV);

    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host' => $_ENV['__NIM_REDIS_IP'],
        'port' => 6379,
        'password' => $_ENV['__NIM_REDIS_PASSWORD'],
    ]);
    $redis->incr('test-counter');


    return [
        "body" => [
            'date' => date('Y-m-d H:i:s'),
            'counter' => $redis->get('test-counter'),
            'todos' => [
                $todo->getArrayCopy(),
                $todo2->getArrayCopy(),
            ],
        ]
    ];
}
*/
