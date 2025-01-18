<?php
// +----------------------------------------------------------------------
// | h-admin
// +----------------------------------------------------------------------
// | Redis 多租户驱动
// +----------------------------------------------------------------------
// | Author: waiszer <waiszer@163.com>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Waiszer\TenantsRedis;

use Waiszer\TenantsRedis\drive\Drive;

class Redis
{
    /**
     * 存储每个租户的 Redis 驱动实例
     *
     * @var array
     */
    protected $tenants = [];

    /**
     * 初始化
     *
     * @param array $configs
     * @throws \Exception
     */
    public function __construct(array $configs = [])
    {
        // 校验配置是否存在
        if (empty($configs)) {
            throw new \RuntimeException("Redis 配置不存在！");
        }

        // 初始化每个租户的 Redis 实例
        foreach ($configs as $tenantName => $config) {
            $this->tenants[$tenantName] = Drive::instance($tenantName, $config);
        }
    }

    /**
     * 获取指定的连接
     *
     * @param string $tenantName
     * @return Drive
     */
    public function tenant(string $tenantName): Drive
    {
        if (!isset($this->tenants[$tenantName])) {
            throw new \RuntimeException("未找到指定Redis连接: {$tenantName}！");
        }
        return $this->tenants[$tenantName];
    }

    /**
     * 动态添加一个新的 Redis 租户实例
     *
     * @param string $tenantName 租户名称
     * @param array $config 配置信息
     * @return Drive
     * @throws \Exception
     */
    public function addTenant(string $tenantName, array $config): Drive
    {
        if (!isset($this->tenants[$tenantName])) {
            $this->tenants[$tenantName] = Drive::instance($tenantName, $config);
        }

        return $this->tenants[$tenantName];
    }

    /**
     * 删除并关闭指定租户的 Redis 实例
     *
     * @param string $tenantName 租户名称
     * @return bool
     */
    public function removeTenant(string $tenantName): bool
    {
        if (isset($this->tenants[$tenantName])) {
            // 关闭 Redis 连接
            $this->tenants[$tenantName]->close();
            // 从 tenants 中移除
            unset($this->tenants[$tenantName]);
            // 从 Drive 管理的实例中移除
            Drive::removeInstance($tenantName);
        }
        return true;
    }

    /**
     * 魔术方法，用于获取指定租户的 Redis 实例
     *
     * @param string $tenantName 租户名称
     * @return Drive
     */
    public function __get(string $tenantName)
    {
        if (!isset($this->tenants[$tenantName])) {
            throw new \RuntimeException("未找到指定Redis连接: {$tenantName}！");
        }
        return $this->tenants[$tenantName];
    }

    /**
     * 默认调用，如果直接调用 set 等方法，则使用 default 租户
     *
     * @param string $method 方法名
     * @param array $arguments 参数
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (!isset($this->tenants['default'])) {
            throw new \RuntimeException("默认Redis连接不存在！");
        }
        return call_user_func_array([$this->tenants['default'], '__call'], [$method, $arguments]);
    }
}