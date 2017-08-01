<?php

class RecipeController extends \Beauty\Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 菜谱搜索功能
     */
    public function searchRecipe(\Beauty\Http\Request $request)
    {
        $header = get_common_header();

        $segment = $request->getSegment();
        $offset  = $segment[3];
        $limit   = $segment[4];

        $users = \Beauty\Model\User::orderBy("user_id")->get([30, 10]);

        foreach ($users as $user) {
            echo $user->user_id . "\n";
        }
    }

}