# conversion
type conversion for php response format

## **安装**

```ini
composer require godjarvis/conversion
```



## **更新**

```ini
composer update godjarvis/conversion
```



## **使用**

包含3个类型解析器：PathAdapter, JsonSchemaAdapter, ObjectAdapter

### 用法

继承或实例化对应的解析器后，设置对应的目标格式到 `$targetConversion` 中，调用convert方法即可完成类型转换。例如：

### 各解析器示例

#### PathAdapter

直接定义字段和类型映射，例如存在以下json返回数据：

```json
{
    "name": "godjarvis",
    "department": [
        1,
        2,
        3
    ],
    "mobile": "15900739964",
    "extattr": {
        "attrs": [
            {
                "name": "godjarvis",
                "age": "36",
                "male": 1
            },
            {
                "name": "Bob",
                "age": "17",
                "male": 0
            }
        ]
    }
}
```

如果要求：

- department中整型转换为字符串
- 手机号转换为整型
- age字段转换为整型
- male字段转换为bool

转换处理伪代码如下：

```php
class UserInfoConversion extends PathAdapter
{
    public $targetConversion = [
        'department.*'         => 'string',
        'mobile'               => 'integer',
        'extattr.attrs.*.age'  => 'int',
        'extattr.attrs.*.male' => 'boolean',
    ];
}

//转换处理
$newData = (new UserInfoConversion($oldData))->convert();
```

转换后效果：

```json
{
    "name": "godjarvis",
    "department": [
        "1",
        "2",
        "3"
    ],
    "mobile": 15900739964,
    "extattr": {
        "attrs": [
            {
                "name": "godjarvis",
                "age": 36,
                "male": true
            },
            {
                "name": "Bob",
                "age": 17,
                "male": false
            }
        ]
    }
}
```



#### JsonSchemaAdapter

使用标准的 jsonSchema 格式定义各字段类型，数据会按照定义的类型进行转换。

同样处理上面例子中 json 需求的伪代码如下：

```php
//使用jsonSchema压缩后的json字符串
class UserInfoConversion extends JsonSchemaAdapter
{
    public $targetConversion = '{"type":"object","properties":{"name":{"type":"string"},"department":{"type":"array","items":{"type":"string"}},"mobile":{"type":"number"},"extattr":{"type":"object","properties":{"attrs":{"type":"array","items":{"type":"object","properties":{"name":{"type":"string"},"age":{"type":"number"},"male":{"type":"boolean"}}}}}}}}';

}

//或者使用jsonSchema格式的数组
class UserInfoConversion extends JsonSchemaAdapter
{
    public $targetConversion = [
        'type'         => 'object',
        'properties'   => [
            .......
        ],
        ........
    ];
}

//转换处理
$newData = (new UserInfoConversion($oldData))->convert();
```



#### ObjectAdapter

利用类属性在注释中定义的类型来控制返回的字段类型，上面例子中返回的数据可抽象出3个对象类如下：

```php
class UserInfo
{
    /** @var string */
    public $name;
    /** @var string[] */
    public $department;
    /** @var int */
    public $mobile;
    /** @var ExtattrInfo */
    public $extattr;
}

class ExtattrInfo
{
    /** @var array<AttrInfo> */
    public $attrs;
}

class AttrInfo
{
    /** @var string */
    public $name;
    /** @var int */
    public $age;
    /** @var bool */
    public $male;
}
```

定义对应的转换器如下：

```php
class UserInfoConversion extends ObjectAdapter
{
    public $targetConversion = UserInfo::class;
}

//转换处理
$newData = (new UserInfoConversion($oldData))->convert();
```

