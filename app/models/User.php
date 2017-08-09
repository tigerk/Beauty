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

    /**
     * 设置add and insert hook
     */
    protected static function booting()
    {
        User::updated(function ($user) {

        });
    }
}