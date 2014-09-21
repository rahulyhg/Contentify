<?php namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Models\Tournament;
use Hover, BackController;

class AdminTournamentsController extends BackController {

    protected $icon = 'joystick.png';

    public function __construct()
    {
        $this->modelName = 'Tournament';

        parent::__construct();
    }

    public function index()
    {
        $this->indexPage([
            'tableHead' => [
                trans('app.id')     => 'id', 
                trans('app.title')  => 'title'
            ],
            'tableRow' => function($tournament)
            {
                $hover = new Hover();
                if ($tournament->icon) { 
                    $hover->image($tournament->uploadPath().$tournament->icon);
                }

                return [
                    $tournament->id,
                    $hover.$tournament->title,
                ];            
            }
        ]);
    }

}