<?php

namespace Beauty\Model;

class User extends Model
{
    protected $dbTable    = 'dg_user';
    protected $primaryKey = "user_id";
    protected $connection = "default";
    protected $dbFields   = array(
        'nickname' => ['text', 'required'],
        'pwd'      => ['text'],
        'user_id'  => ['int'],
    );

    /**
     * 新增和更新不允许更新该字段，单条获取无法获取该内容
     * @var array
     */
    protected $hidden = [
        'pwd'
    ];

    protected static function booting()
    {
        User::updated(function ($user) {
            var_dump($user);
        });
    }

    public function getuser()
    {
        return $this->find("1000010");
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}