<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;
use Pimple\Container;
use Predis\Client;
use RuntimeException;
use Todo\TodoMapper;
use Todo\TodoTransformer;

class AppContainer extends Container
{
    /**
     * Constructor.
     *
     * @param array $args the array of parameters passed into the OpenWhisk action
     */
    public function __construct(array $args)
    {
        if (!isset($_ENV['__NIM_REDIS_IP'])) {
            throw new InvalidArgumentException("Redis IP address missing");
        }

        $configuration ['settings'] = [
            'base_url' => $this->determineBaseUrl($args),
        ];

        /**
         * Factory to create a Predis Client instance
         */
        $configuration[Client::class] = static function (Container $c): Client {
            $redisIP = $_ENV['__NIM_REDIS_IP'];
            $redisPassword = $_ENV['__NIM_REDIS_PASSWORD'];
            if ($redisIP && $redisPassword) {
                $client = new Client(
                    [
                        'scheme' => 'tcp',
                        'host' => $redisIP,
                        'port' => 6379,
                    ]
                );
                $client->auth($redisPassword);
                return $client;
            }

            throw new RuntimeException('Key-Value store is not available.');
        };

        /**
         * Factory to create a TodoMapper instance
         */
        $configuration[TodoMapper::class] = static function (Container $c): TodoMapper {
            return new TodoMapper($c[Client::class]);
        };

        /**
         * Factory to create a TodoTransformer instance
         */
        $configuration[TodoTransformer::class] = static function (Container $c): TodoTransformer {
            return new TodoTransformer($c['settings']['base_url']);
        };

        /**
         * Factory to create a Manager instance
         */
        $configuration[Manager::class] = static function (Container $c): Manager {
            $baseUrl = $c['settings']['base_url'];

            $manager = new Manager();
            $manager->setSerializer(new ArraySerializer($baseUrl));
            return $manager;
        };

        parent::__construct($configuration);
    }

    /**
     * Determine the base URL from the x-forwarded-url header by
     * removing __ow_path from the end
     */
    private function determineBaseUrl(array $args): string
    {
        $url = $_ENV['__OW_API_HOST'] . '/v1/web' . $_ENV['__OW_ACTION_NAME'];
        $url =  explode('/', $url);
        array_pop($url);
        $url = implode('/', $url);

        return $url;
    }
}
