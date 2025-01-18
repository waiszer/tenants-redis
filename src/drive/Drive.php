<?php
// +----------------------------------------------------------------------
// | h-admin
// +----------------------------------------------------------------------
// | Redis 驱动
// +----------------------------------------------------------------------
// | Author: waiszer <waiszer@163.com>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Waiszer\TenantsRedis\drive;

use Redis;
use Predis\Client as Predis;

class Drive
{
    /**
     * 存储每个租户的 Redis 连接实例
     *
     * @var array
     */
    protected static $instances = [];

    /**
     * Redis 客户端连接实例
     *
     * @var mixed|null
     */
    protected $client = null;

    /**
     * 当前租户实例名称
     *
     * @var string
     */
    protected $tenantName;

    /**
     * 私有构造函数，防止外部实例化
     */
    private function __construct(string $tenantName, array $config)
    {
        $this->tenantName = $tenantName;
        $this->connect($config);
    }

    /**
     * 单例方法，获取 Redis 驱动实例
     *
     * @param string $tenantName 租户名称
     * @param array $config 配置信息
     * @return Drive
     * @throws \Exception
     */
    public static function instance(string $tenantName, array $config): Drive
    {
        if (!isset(self::$instances[$tenantName])) {
            self::$instances[$tenantName] = new self($tenantName, $config);
        }

        return self::$instances[$tenantName];
    }

    /**
     * 删除指定租户实例
     *
     * @param string $tenantName 租户名称
     * @return void
     */
    public static function removeInstance(string $tenantName)
    {
        if (isset(self::$instances[$tenantName])) {
            unset(self::$instances[$tenantName]);
        }
    }

    /**
     * 魔术方法，用于直接调用 Redis 客户端的方法
     *
     * @param string $method 方法名
     * @param array $arguments 参数
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->client, $method)) {
            throw new \BadMethodCallException("Redis方法{$method}不存在！");
        }
        return call_user_func_array([$this->client, $method], $arguments);
    }

    /**
     * 关闭当前 Redis 连接
     *
     * @return void
     */
    public function close()
    {
        if ($this->client instanceof Redis) {
            // 关闭 phpredis 连接
            $this->client->close();
        } elseif ($this->client instanceof Predis) {
            // Predis 不需要显式关闭连接，销毁实例即可
            $this->client = null;
        }
    }

    /**
     * 连接 Redis 服务
     *
     * @param array $config 配置信息
     * @throws \Exception
     */
    private function connect(array $config)
    {
        // 校验配置项是否完整
        $requiredKeys = ['host', 'port', 'select', 'persistent', 'timeout'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \InvalidArgumentException("Redis配置参数缺失: {$key}！");
            }
        }

        try {
            if (extension_loaded('redis')) {
                $client = new Redis();

                // 是否使用长连接
                if ($config['persistent']) {
                    $client->pconnect(
                        $config['host'],
                        (int)$config['port'],
                        (float)$config['timeout'],
                        'p_connect_' . $this->tenantName,
                        isset($config['retry_interval']) ? (int)$config['retry_interval'] : 0,
                        isset($config['read_timeout']) ? (float)$config['read_timeout'] : 0
                    );
                } else {
                    $client->connect(
                        $config['host'],
                        (int)$config['port'],
                        (float)$config['timeout'],
                        '',
                        isset($config['retry_interval']) ? (int)$config['retry_interval'] : 0,
                        isset($config['read_timeout']) ? (float)$config['read_timeout'] : 0
                    );
                }

                // 设置密码
                if (!empty($config['password'])) {
                    $client->auth($config['password']);
                }

                // 设置数据库
                $client->select((int)$config['select']);
            } elseif (class_exists('\Predis\Client')) {
                // 使用 Predis 连接
                $params = array_diff_key($config, array_flip(['password', 'select']));
                $client = new Predis($params);

                // 设置密码
                if (!empty($config['password'])) {
                    $client->auth($config['password']);
                }

                // 设置数据库
                $client->select((int)$config['select']);
            } else {
                throw new \RuntimeException('未安装Redis扩展！');
            }

            $this->client = $client;

        } catch (\Exception $e) {
            throw new \Exception("Redis连接失败: " . $e->getMessage());
        }
    }
}