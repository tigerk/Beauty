<?php

namespace Beauty\Model;

class User extends Model
{
    protected $dbTable = 'dg_user';

    protected $primaryKey = "user_id";

    protected $connection = "default";

    protected $dbFields = array(
        'nickname' => array('text', 'required'),
        'pwd' => array('text', 'required'),
    );

    private $id;
    private $name;

    public function getuser()
    {
        $updatedrow = $this->update([
            "nickname" => "kimhwawoon"
        ], [
            "user_id = " => "1000001"
        ]);

        return $this->find(["user_id"], [
            "user_id =" => "1000001"
        ]);
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