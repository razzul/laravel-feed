<?php
namespace App\Http\Controllers\Feed;

use App\Services\Feed\FeedBuilder;
use App\Http\Controllers\Controller;

class FeedsController extends Controller
{
    private $builder;

    public function __construct(FeedBuilder $builder)
    {
        $this->builder = $builder;
    }

    //We're making atom default type
    public function getFeed($type = "atom")
    {
        if ($type === "rss" || $type === "atom") {
            return $this->builder->render($type);
        }
        
        //If invalid feed requested, redirect home
        return redirect()->home();
    }
}