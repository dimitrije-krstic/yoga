<?php
declare(strict_types=1);

namespace App\Client;

use Predis\Client;
use Psr\Log\LoggerInterface;


class RedisClient
{
    private const APP_PREFIX = 'app_';

    private Client $redisClient;
    private LoggerInterface $logger;

    public function __construct(
        Client $redisClient,
        LoggerInterface $logger
    ) {
        $this->redisClient = $redisClient;
        $this->logger = $logger;
    }

    public function getItem(string $key): ?string
    {
        try {
            return $this->redisClient->get(self::APP_PREFIX . $key);
        } catch (\Exception $exception) {
            throw new \Exception('Redis get');
            //$this->logger->critical('Redis connection fails: '. $exception->getMessage());
            //return '';
        }
    }

    public function saveItem(string $key, string $data): void
    {
        try {
            $this->redisClient->set(self::APP_PREFIX . $key, $data);
        } catch (\Exception $exception) {
            throw new \Exception('Redis put');
            //$this->logger->critical('Redis connection fails: '. $exception->getMessage());
        }
    }
}
