<?php

class User extends \Beauty\Model
{
    protected $table = 'dg_user';

    public function getuser()
    {
        $updatedrow = $this->update([
            "nickname" => "kimhwawoon"
        ], [
            "user_id = " => "1000001"
        ]);
        return $this->find(["user_id"],[
            "user_id =" => "1000001"
        ]);
    }
}