<?php

declare(strict_types=1);

use App\AppContainer;
use Todo\TodoMapper;

/**
 * DELETE /todos/{id}
 */
function main(array $args) : array
{
    try {
        $parts = explode("/", $args['__ow_path']);
        $id = (int)array_pop($parts);

        $container = new AppContainer($args);
        $mapper = $container[TodoMapper::class];

        $mapper->delete($id);
        return [
            'statusCode' => 204,
        ];
    } catch (\Exception $e) {
        error_log((string)$e);
        $code = $e->getCode() < 400 ? 500 : $e->getCode();
        return [
            'statusCode' => $code,
            'body' => ['error' => $e->getMessage()]];
    }
}
