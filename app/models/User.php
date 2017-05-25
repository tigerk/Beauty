<?php

class User extends \Beauty\Model
{
    protected $table = 'user';

    public function find()
    {
        return $this->query("select * from user");
    }
}