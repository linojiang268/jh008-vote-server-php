<?php
namespace Jihe\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TeamComposer
{
    /**
     * Create a new profile composer.
     *
     * @param  UserRepository  $users
     * @return void
     */
    public function __construct(Request $request)
    {
        // Dependencies automatically resolved by service container...
        $this->request = $request;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (null != $team = $this->request->input('team')) {
            $view->with('team', $team);
        }
    }
}