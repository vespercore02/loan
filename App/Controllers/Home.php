<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Models\Announcement;
use \App\Models\loan;
use \App\Models\Contribution;
use \App\Models\User;
use \App\Models\Group;
use \App\Models\Borrow;
use \App\Models\Term;
use \App\Flash;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Home extends Authenticated
{

    protected $user_info;
    protected $members_count;
    protected $access_rights_count;
    protected $group_count;
    
    protected function before()
    {
        if (isset($_SESSION['user_id'])) {
            $this->user_info  = User::findByID($_SESSION['user_id']);

            if ($this->user_info['access_rights'] == 9) {
                $this->members_count = User::getMemberCount();
                $this->access_rights_count = User::getAccessRights();
                $this->group_count = User::getAccessRights();
            }else {
                $this->members_count = "";
                $this->access_rights_count = "";
                $this->group_count = "";
            }
        } else {
            # code...
            $this->redirect('/login');
        }
    }
    /**
     * Show the index page
     *
     * @return void
     */
    public function indexAction()
    {
        
        $term_info         = Term::viewTerm();
        $member_info       = User::findByID($this->user_info['id']);
        $contribution_list = Contribution::viewMember($this->user_info['id']);
        $contribution_term = Contribution::viewMemberTermContri($this->user_info['id']);
        $borrow_list       = Borrow::viewMember($this->user_info['id']);

        View::renderTemplate('Home/index.html', [
                        'user_info'         => $this->user_info,
                        'contribution_list' => $contribution_list,
                        'contribution_term' => $contribution_term,
                        'borrow_list'       => $borrow_list,
                        'members_count'     => $this->members_count,
                        'access_rights_count'     => $this->access_rights_count,
                        'group_count'     => $this->group_count,
                        'term_list'       => $term_info,
                    ]);
    }

    public function myContributionAction()
    {
        $member_Contribution = Contribution::viewMember($_SESSION['user_id']);
        //$memberTerm_Contribution = Contribution::viewMemberTerm($_SESSION['user_id'], $this->route_params['id']);
        $borrow_list       = Borrow::viewMember($this->user_info['id']);
        $term_info         = Term::viewTerm();
        

        View::renderTemplate('/contribution/profile.html', [
            //'term' => $this->route_params['id'],
            'borrow_list'       => $borrow_list,
            'contribution_list' => $member_Contribution,
            'term_list'       => $term_info
        ]);
    }

    public function myBorrowAction()
    {
        $member_Borrow = Borrow::viewMember($_SESSION['user_id']);
        

        View::renderTemplate('/borrow/profile.html', [
            
            'borrow_list' => $member_Borrow
        ]);
    }

    public function mygroupAction()
    {
        $user_info  = User::findByID($_SESSION['user_id']);

        if ($user_info['access_rights'] == 2) {
            # code...
            $group_info     = Contribution::viewGroupTotalContri($user_info['belonging_group']);
            $group_members     = Group::groupMembers($user_info['belonging_group']);

            $members_info = array();
            foreach ($group_members as $key => $value) {
                $id     = $value['id'];
                $name   = $value['name'];

                $contri_info = Contribution::viewMemberTotalContri($value['id']);
                    
                $total_contri = $contri_info[0]['total_contri'];
                $total_month_int = $contri_info[0]['total_month_int'];
                $total_contri_w_int = $contri_info[0]['total_contri'] + $contri_info[0]['total_month_int'];


                $members_info[] = ['id' => $id,
                    'name' => $name,
                    'contri' => $total_contri,
                    'month_int' => $total_month_int,
                    'contri_w_int' => $total_contri_w_int];
            }
                
            View::renderTemplate('Home/index-groupleader.html', [
                    
                    'group_info' => $group_info,
                    'group_members' => $group_members,
                    
                    'members_info' => $members_info
                ]);
        }else {
            # code...

            Flash::addMessage('Unauthorized to access', Flash::WARNING);

            $this->redirect(Auth::getReturnToPage());
        }
    }

    public function index1Action()
    {
        if (isset($_SESSION['user_id'])) {
            # code...
            $user_info  = User::findByID($_SESSION['user_id']);

            switch ($user_info['access_rights']) {
                case '0':
                    # code...

                    View::renderTemplate('/Home/index-borrower.html');

                    break;

                case '1':
                    # code...

                    $member_info       = User::findByID($user_info['id']);
                    $contribution_list = Contribution::viewMember($user_info['id']);
                    $borrow_list       = Borrow::viewMember($user_info['id']);

                    View::renderTemplate('Home/index-member.html', [
                        'user_info'         => $user_info,
                        'contribution_list' => $contribution_list,
                        'borrow_list'       => $borrow_list
                    ]);

                    break;

                case '2':
                    # code...

                    $group_info     = Contribution::viewGroupTotalContri($user_info['belonging_group']);
                    $group_members     = Group::groupMembers($user_info['belonging_group']);
                    
                    
                    $members_info = array();
                    foreach ($group_members as $key => $value) {
                        # <code class="">
                        //echo $value['id']."<br>";
                        //echo $value['name']."<br>";

                        $id     = $value['id'];
                        $name   = $value['name'];

                        $contri_info = Contribution::viewMemberTotalContri($value['id']);
                        
                        $total_contri = $contri_info[0]['total_contri'];
                        $total_month_int = $contri_info[0]['total_month_int'];
                        $total_contri_w_int = $contri_info[0]['total_contri'] + $contri_info[0]['total_month_int'];


                        $members_info[] = ['id' => $id,
                        'name' => $name,
                        'contri' => $total_contri,
                        'month_int' => $total_month_int,
                        'contri_w_int' => $total_contri_w_int];
                    }
                    
                    View::renderTemplate('Home/index-groupleader.html', [
                        
                        'group_info' => $group_info,
                        'group_members' => $group_members,
                        
                        'members_info' => $members_info
                    ]);

                    break;

                case '9':
                    # code...

                    $members_count = User::getMemberCount();

                    View::renderTemplate('Home/index-admin.html', [
                        'members_count' => $members_count
                    ]);

                    break;

                default:
                    # code...
                    break;
            }
        } else {
            # code...
            View::renderTemplate('Login/new.html');
        }
    }

    public function groupmonthdetails()
    {
        if (isset($_GET['month']) and isset($_GET['group'])) {
            # code...
            
            $contri =  Contribution::getGroupMonthlyContri($_GET['month'], $_GET['group']);

            echo json_encode($contri);

            //print_r($contri);
        }
    }
}
