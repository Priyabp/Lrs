<?php
namespace App\Http\Controllers;

use App\Locker\Data\Dashboards\AdminDashboard;
use App\Locker\Helpers\User;
use App\Locker\Repository\Site\SiteRepository as SiteRepo;
use App\Locker\Repository\Lrs\Repository as LrsRepo;
use App\Locker\Repository\Statement\Repository as StatementRepo;
use App\Locker\Repository\User\UserRepository as UserRepo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

class SiteController extends BaseController
{

    protected $site, $lrs, $user, $statement;

    /**
     * Constructs a new SiteController.
     */
    public function __construct(SiteRepo $site, LrsRepo $lrs, UserRepo $user, StatementRepo $statement)
    {
        $this->site = $site;
        $this->lrs = $lrs;
        $this->statement = $statement;
        $this->user = $user;

        $this->middleware('auth');
        $this->middleware('auth.super', array('except' => array('inviteUsers')));
        $this->middleware('csrf', array('only' => array('update', 'verifyUser', 'inviteUsers')));
    }

    /**
     * Display a listing of statements for a user.
     *
     * @return View
     */
    public function index()
    {
        $site = $this->site->all();
        $opts = ['user' => Auth::user()];
        $list = $this->lrs->index($opts);
        $admin_dashboard = new AdminDashboard();

        return view('partials.site.dashboard', [
            'site' => $site,
            'list' => $list,
            'stats' => $admin_dashboard->getFullStats(),
            'dash_nav' => true
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     * @param String $id
     * @return View
     */
    public function edit($id)
    {
        $site = $this->site->find($id);
        return view('partials.site.edit', [
            'site' => $site,
            'settings_nav' => true
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param String $id
     * @return View
     */
    public function update($id)
    {
        $s = $this->site->update($id, Input::all());

        if ($s) {
            return Redirect::back()->with('success', trans('site.updated'));
        }

        return Redirect::back()
            ->withInput()
            ->withErrors($user->errors());
    }

    /**
     * Display the super admin settings.
     * @return Response
     */
    public function settings()
    {
        return Response::json($this->site->all());
    }

    /**
     * Grab site stats
     * @return Response
     **/
    public function getStats()
    {
        $startDate = \LockerRequest::getParam('graphStartDate');
        $endDate = \LockerRequest::getParam('graphEndDate');

        $startDate = !$startDate ? null : new \Carbon\Carbon($startDate);
        $endDate = !$endDate ? null : new \Carbon\Carbon($endDate);
        $admin_dashboard = new AdminDashboard();
        $stats = $admin_dashboard->getFullStats();

        return Response::json($stats);
    }


    /**
     * Grab site stats
     * @return Response
     **/
    public function getGraphData()
    {
        $startDate = \LockerRequest::getParam('graphStartDate');
        $endDate = \LockerRequest::getParam('graphEndDate');

        $startDate = !$startDate ? null : new \Carbon\Carbon($startDate);
        $endDate = !$endDate ? null : new \Carbon\Carbon($endDate);
        $admin_dashboard = new AdminDashboard();
        $graph_data = $admin_dashboard->getGraphData($startDate, $endDate);
        return Response::json($graph_data);
    }

    /**
     * Display the super admin lrs view.
     * @return Response
     */
    public function lrs()
    {
        $opts = ['user' => Auth::user()];
        $lrss = $this->lrs->index($opts);
        $lrs_repo = $this->lrs;

        return Response::json(array_map(function ($lrs) use ($lrs_repo) {
            $lrs->statement_total = $lrs_repo->getStatementCount($lrs->_id);
            return $lrs;
        }, $lrss));
    }

    public function apps()
    {
        return OAuthApp::all();
    }

    /**
     * Display the super admin user list view.
     * @return Response
     */
    public function users()
    {
        return Response::json($this->user->all()->map(function ($user) {
            $user->lrs_owned = $this->lrs->getLrsOwned($user->_id);
            $user->lrs_member = $this->lrs->getLrsMember($user->_id);
            return $user;
        }));
    }

    /**
     * Display the invite user page
     * @return Response
     */
    public function inviteUsersForm()
    {
        return view('partials.site.invite', [
            'users_nav' => true,
            'admin_dash' => true
        ]);
    }

    /**
     * Invite in the users
     **/
    public function inviteUsers()
    {
        $tokens = User::inviteUser(Input::all());

        return Redirect::back()->with('success', trans('users.invite.invited', [
            'tokens' => array_reduce($tokens, function ($carry, $item) {
                return $carry .= '</br>' . $item['email'] . ' must <a href="' . $item['url'] . '">reset their password</a>.';
            }, '')
        ]));
    }

    /**
     * Verify a user.
     **/
    public function verifyUser($id)
    {
        $verify = $this->site->verifyUser($id);
        return Response::json($verify);
    }
}
