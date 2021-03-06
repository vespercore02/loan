<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Models\User;
use \App\Models\Borrow;
use \App\Models\Payment;
use \App\Models\Term;
use \App\Models\Summary;
use \App\Flash;

class Borrows extends Authenticated
{
    /**
     * Items index
     *
     * @return void
     */
    public function indexAction()
    {
        $terms = new Term();

        $terms_list = $terms->view();
        
        View::renderTemplate('/borrow/index.html', [
            'terms' => $terms_list
        ]);
    }

    /**
     * Items index
     *
     * @return void
     */
    public function viewAction()
    {
        if (isset($this->route_params['id'])) {
            $borrow_info = Borrow::borrowInfo($this->route_params['id']);
            $paymentlist = Payment::paymentlist($this->route_params['id']);
            $userInfo   = User::searchById($borrow_info[0]['user_id']);
            $viewer     = $_SESSION['user_id'];

            View::renderTemplate('/borrow/view.html', [
                'viewer'    => $viewer,
                'userInfo' => $userInfo,
                'borrow_info' => $borrow_info,
                'payment_list' => $paymentlist
            ]);
        }
        /*
        echo "<pre>";
        print_r($paymentlist);
        echo "</pre>";

        */
    }

    public function myAction()
    {
        if (isset($this->route_params['id'])) {
            $session_info = User::findByID($_SESSION['user_id']);
            //print_r($session_info);
            $borrowInfo = Borrow::borrowInfo($this->route_params['id']);
            $paymentlist = Payment::paymentlist($this->route_params['id']);
            $userInfo   = User::searchById($borrowInfo[0]['user_id']);

            //print_r($userInfo);

            if ($borrowInfo[0]['user_id'] == $_SESSION['user_id']) {
                # code...
                $viewer = "";
            }  elseif ($session_info['belonging_group'] == $session_info['belonging_group'] and $session_info['access_rights'] == 2) {
                # code...
                $viewer = 1;
            }else {
                # code...
                Flash::addMessage('Unauthorized to access', Flash::WARNING);

                $this->redirect(Auth::getReturnToPage());
            }

            View::renderTemplate('payment/index.html', [
                'viewer'    => $viewer,
                'userInfo' => $userInfo,
                'borrowInfo' => $borrowInfo,
                'paymentlist' => $paymentlist
            ]);
       
        } else {
            # code...
            $this->redirect(Auth::getReturnToPage());
        }
    }


    public function monthAction()
    {
        $month = $this->route_params['id'];
        $month_borrow   = Borrow::getMonthBorrowRecords($this->route_params['id']);
                
        View::renderTemplate('/borrow/month.html', [
            'month' => $month,
            'month_borrow' => $month_borrow
        ]);
    }

    public function termAction()
    {
        $term = $this->route_params['id'];
        $borrow   = Borrow::viewTerm($this->route_params['id']);
        $term_months    = Term::months($this->route_params['id']);
        
        $borrow_months = array();

        foreach ($term_months as $month) {
            $borrow_months[]  = Borrow::getMonthBorrowRecords($month['month_start']);
        }

        View::renderTemplate('/borrow/term.html', [
            'term' => $term,
            'term_months' => $term_months,
            'borrow_months' => $borrow_months
        ]);
    }

    public function termMonths()
    {
        if (isset($_GET['term'])) {
            # code...

            $term_months          = Term::months($_GET['term']);

            echo json_encode($term_months);
        }
    }

    public function borrowMonth()
    {
        if (isset($_GET['month'])) {
            # code...

            $borrow          = Borrow::getMonthBorrowRecords($_GET['month']);

            echo json_encode($borrow);
        }
    }

    public function addBorrower()
    {
        print_r($_POST);
        /*
        $BorrowerInfo = explode(" - ", $_POST['borrow_by']);

        $_POST['user_id'] = $BorrowerInfo[0];
        $_POST['group'] = $BorrowerInfo[3];
        */
        $_POST['cut_off'] = self::getContriDate($_POST['borrow_date']);
        $_POST['interest'] = self::getInterest($_POST['borrow_interest'], $_POST['borrow_amount']);
        $_POST['remaining'] = self::getRemaining($_POST['borrow_interest'], $_POST['borrow_amount'], $_POST['months_to_pay']);
        $term      = Term::term($_POST['cut_off']);

        //echo $term;

        if (empty($term)) {
            # code...
            Flash::addMessage('Term for date '.$_POST['cut_off'].' is not yet set, please contact your administrator.', "warning");

            View::renderTemplate('/borrow/index.html');
        } else {
            # code...

            $_POST['term'] = $term['term'];

            //echo $_POST['cut_off'];
        
            $borrow = new Borrow($_POST);
            $result = $borrow->save();
        
            if ($result > 0) {
                $borrow->updateSummaryBorrow();

                $_POST['borrow_id'] = $result;

                $payment_list = new Payment($_POST);
            
                echo $payment_list->constructPaymentList();

                self::getEstEarned($_POST['cut_off']);

                Flash::addMessage('Borrow '.$_POST['borrow_amount'].' successful.');

                $this->redirect('/members/view/'.$_POST['user_id']);
            } else {
                Flash::addMessage('Term for date '.$_POST['cut_off'].' is not yet set, please contact your administrator.');

                View::renderTemplate('/borrows/index.html');
            }
        }
    }

    public function getEstEarned($date)
    {
        $summary = new Summary();
        $fetch_data = $summary->getEstEarned($date);

        if (empty($fetch_data['payment_rcv'])) {
            # code...
            $fetch_data['payment_rcv'] = 0;
        }

        if (empty($fetch_data['interest_earned'])) {
            # code...
            $fetch_data['interest_earned'] = 0;
        }

        $est_earned = $fetch_data['payment_rcv'] + $fetch_data['deficit'] - $fetch_data['amount_borrow'] - $fetch_data['interest_earned'];
        echo "<pre>";
        print_r($summary->getEstEarned($date));
        echo "</pre>";

        echo $est_earned;

        $fetch_data = $summary->updateEstEarned($date, $est_earned);

    }

    
    public function getRemaining($borrow_interest, $borrow_amount, $months_to_pay)
    {
        $int = $borrow_amount  *  ($borrow_interest/100);
        $int_monthly = $int * $months_to_pay;
        $int_acquired = $borrow_amount + $int_monthly;

        return $int_acquired;
    }

    public function getInterest($borrow_interest, $borrow_amount)
    {
        $int = $borrow_amount  *  ($borrow_interest/100);

        return $int;
    }

    public function getContriDate($borrow_date)
    {
        $month = explode("-", $borrow_date);

        $set_month = $month[1]."-".$month[2];

        //print_r($month);
        
        if ($set_month > '10-15' and $set_month < '10-31' or $set_month == '10-15') {
            # code...
            return $month[0]."-10-15";
        }

        if ($set_month > '10-31' and $set_month < '11-15' or $set_month == '10-31') {
            # code...
            return $month[0]."-10-31";
        }

        if ($set_month > '11-15' and $set_month < '11-30' or $set_month == '11-15') {
            # code...
            return $month[0]."-11-15";
        }

        if ($set_month > '11-30' and $set_month < '12-15' or $set_month == '11-30') {
            # code...
            return $month[0]."-11-30";
        }

        if ($set_month > '12-15' and $set_month < '12-31' or $set_month == '12-15') {
            # code...
            return $month[0]."-12-15";
        }

        if ($set_month > '12-31' and $set_month < '01-15' or $set_month == '12-31') {
            # code...
            return $month[0]."-12-31";
        }

        if ($set_month > '01-15' and $set_month < '01-31' or $set_month == '01-15') {
            # code...
            return $month[0]."-01-15";
        }

        if ($set_month > '01-31' and $set_month < '02-15' or $set_month == '01-31') {
            # code...
            return $month[0]."-01-31";
        }

        if ($set_month > '02-15' and $set_month < '02-28' or $set_month == '02-15') {
            # code...
            return $month[0]."-02-15";
        }

        if ($set_month > '02-28' and $set_month < '03-15' or $set_month == '02-28') {
            # code...

            $term      = Term::term($month[0]."-02-28");

            if ($term) {
                # code...
                
                return $month[0]."-02-28";
            }else {
                # code...
                $term      = Term::term($month[0]."-02-29");

                if ($term) {
                    # code...
                    
                    return $month[0]."-02-29";
                }
            }
        }

        if ($set_month > '02-29' and $set_month < '03-15' or $set_month == '02-29') {
            # code...
            return $month[0]."-02-29";
        }

        if ($set_month > '03-15' and $set_month < '03-31' or $set_month == '03-15') {
            # code...
            return $month[0]."-03-15";
        }

        if ($set_month > '03-31' and $set_month < '04-15' or $set_month == '03-31') {
            # code...
            return $month[0]."-03-31";
        }

        if ($set_month > '04-15' and $set_month < '04-30' or $set_month == '04-15') {
            # code...
            return $month[0]."-04-15";
        }

        if ($set_month > '04-30' and $set_month < '05-15' or $set_month == '04-30') {
            # code...
            return $month[0]."-04-30";
        }

        if ($set_month > '05-15' and $set_month < '05-31' or $set_month == '05-15') {
            # code...
            return $month[0]."-05-15";
        }

        if ($set_month > '05-31' and $set_month < '06-15' or $set_month == '05-31') {
            # code...
            return $month[0]."-05-31";
        }

        if ($set_month > '06-15' and $set_month < '06-30' or $set_month == '06-15') {
            # code...
            return $month[0]."-06-15";
        }

        if ($set_month > '06-30' and $set_month < '07-15' or $set_month == '06-30') {
            # code...
            return $month[0]."-06-30";
        }

        if ($set_month > '07-15' and $set_month < '07-31' or $set_month == '07-15') {
            # code...
            return $month[0]."-07-15";
        }

        if ($set_month > '07-31' and $set_month < '08-15' or $set_month == '07-31') {
            # code...
            return $month[0]."-07-31";
        }

        if ($set_month > '08-15' and $set_month < '08-31' or $set_month == '08-15') {
            # code...
            return $month[0]."-08-15";
        }

        if ($set_month > '08-31' and $set_month < '09-15' or $set_month == '08-31') {
            # code...
            return $month[0]."-08-31";
        }

        if ($set_month > '09-15' and $set_month < '09-30' or $set_month == '09-15') {
            # code...
            return $month[0]."-09-15";
        }

        if ($set_month > '09-30' and $set_month < '10-15' or $set_month == '09-30') {
            # code...
            return $month[0]."-09-30";
        }
    }
}
