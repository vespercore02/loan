<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Models\Announcement;
use \App\Models\loan;
use \App\Models\Contribution;
use \App\Models\User;
use \App\Models\Group;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Home extends \Core\Controller
{

    /**
     * Show the index page
     *
     * @return void
     */
    public function indexAction()
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

                    View::renderTemplate('Home/index-member.html');

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
                        $total_contri_w_int = $contri_info[0]['total_contri_w_int'];


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
