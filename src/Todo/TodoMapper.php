<?php declare(strict_types=1);

namespace Todo;

use PDO;
use Predis\Client;
use Predis\Response\Status;
use RuntimeException;

class TodoMapper
{
    const TODO_SEQ = 'todo_seq';
    private $redis;

    /**
     * TodoMapper constructor.
     */
    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get the redisKey from an ID
     */
    private function keyFromId(int $id) : string
    {
        return 'todo:' . $id;
    }

    /**
     * Get the ID from a redisKey
     */
    private function idFromKey(string $redisKey) : int
    {
        if (strpos($redisKey, ':') === false) {
            throw new RuntimeException('Invalid Key');
        }
        $parts = explode(':', $redisKey);
        return (int)$parts[1];
    }

    /**
     * Load a Todo item from a redisKey
     */
    private function loadByKey(string $redisKey) : Todo
    {
        $data = $this->redis->get($redisKey);

        if (!$data) {
            throw new Exception\TodoNotFoundException();
        }
        $data = unserialize($data, ['allowed_classes' => false]);

        $data['id'] = $this->idFromKey($redisKey);
        return new Todo($data);
    }

    /**
     * Fetch all todos
     *
     * @return array [Todo]
     */
    public function fetchAll() : array
    {
        $keys = $this->redis->zrangebyscore('todo_list', 0, PHP_INT_MAX);

        $todos = [];
        foreach ($keys as $key) {
            $todos[] = $this->loadByKey($key);
        }

        return $todos;
    }

    public function loadById(int $id) : Todo
    {
        $redisKey = $this->keyFromId($id);
        $data = $this->redis->get($redisKey);

        if (!$data) {
            throw new Exception\TodoNotFoundException();
        }
        $data = unserialize($data, ['allowed_classes' => false]);

        $data['id'] = $id;
        return new Todo($data);
    }

    public function insert(array $data) : Todo
    {
        $todo = new Todo($data);
        $params = $todo->getArrayCopy();

        if (isset($params['id'])) {
            throw new RuntimeException('Todo objject already has an ID!');
        }

        $id = $this->redis->incr(self::TODO_SEQ);
        $redisKey = $this->keyFromId($id);

        $this->redis->multi();
        $this->redis->set($redisKey, serialize($params));
        $this->redis->zadd('todo_list', $id, $redisKey);
        $this->redis->exec();

        $params['id'] = $id;
        return new Todo($params);
    }

    public function update(Todo $todo, array $data) : Todo
    {
        if (array_key_exists('id', $data)) {
            unset($data['id']);
        }

        $originalData = $todo->getArrayCopy();
        $data = array_replace($originalData, $data);

        $updatedTodo = new Todo($data);
        $params = $updatedTodo->getArrayCopy();

        $this->redis->multi();
        if (isset($params['id'])) {
            $id = $params['id'];
            unset($params['id']);
        } else {
            throw new RuntimeException("Expected Todo item to have an ID!");
        }
        $redisKey = $this->keyFromId($id);
        $this->redis->set($redisKey, serialize($params));
        $this->redis->zadd('todo_list', [$id => $redisKey]);
        $this->redis->exec();

        return $updatedTodo;
    }

    public function deleteAll() : void
    {
        $keys = $this->redis->zrangebyscore('todo_list', 0, PHP_INT_MAX);

        $todos = [];
        foreach ($keys as $redisKey) {
            $this->redis->del($redisKey);
        }
    }

    public function delete(int $id) : void
    {
        $redisKey = $this->keyFromId($id);
        $this->redis->del($redisKey);
    }
}
