<h1 align="center">
  TenantsRedis
</h1>

<p align="center">
  <strong> 多租户Redis库 </strong>
</p>

## 安装

最方便的安装方式就是使用Composer ( https://getcomposer.org/ )

```
composer require waiszer/tenants-redis
```

## 使用
```
<?php
use Waiszer\TenantsRedis\Redis;

class Demo {

    public $configs = [
        // 默认租户
        'default' => [
            //地址
            'host'              =>  '127.0.0.1',
            //端口
            'port'              =>  6379,
            //密码
            'password'          =>  '',
            // 使用的数据库
            'select'            =>  0,
            // 连接超时
            'timeout'           =>  2,
            // 读取超时
            'read_timeout'      =>  2,
            // 重试时间（单位：毫秒）
            'retry_interval'    =>  500,
            // 缓存前缀
            'prefix'            =>  '',
            // 缓存有效期 0表示永久缓存
            'expire'            =>  0,
            // 是否选用持久连接
            'persistent'        =>  false,
        ],
        // 其他租户...
    ];
    
    public $redis;

    public function __construct() {
        $this->redis = new Redis($this->configs);
    }
    
    public function test() {
        // 调用默认租户方式一
        $redis->set('key', 'value');
        // 调用默认租户方式二
        $redis->default->set('key', 'value');
        
        // 调用其他租户
        // $redis->[租户标识]->set('key', 'value');
    }
}

```

## 协议

`TenantsRedis` 采用 [LGPL-2.1](LICENSE) 开源协议发布。

## 联系

有问题或者功能建议，请联系我
- waiszer@163.com
