<?php

declare(strict_types=1);

use App\AppContainer;
use Todo\TodoMapper;

/**
 * DELETE /todos
 */
function main(array $args) : array
{
    global $pdo;
    try {
        $container = new AppContainer($args);
        $mapper = $container[TodoMapper::class];

        $mapper->deleteAll();

        $pdo = null;
        return [
            'statusCode' => 204,
        ];
    } catch (\Throwable $e) {
        $pdo = null;
        var_dump((string)$e);
        $code = $e->getCode() < 400 ? 500 : $e->getCode();
        return [
            'statusCode' => $code,
            'body' => ['error' => $e->getMessage()]];
    }
}
