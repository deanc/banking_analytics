<?php

namespace DC\BankingAnalytics\Importer;

class Nordea extends Importer {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    protected function parseTransaction($row) {

        if(preg_match('/^\d{2}\.\d{2}\.\d{4}/i', $row)) {

            $bits = explode("\t", $row);

            $date = date('y-m-d', strtotime($bits[0]));
            $amount = str_replace(',', '.', $bits[3]);

            $pi = $bits[4];
            if(empty($pi)) {
                $pi = $bits[7];
            }

            $place = 0;
            if($getPlace = $this->db->fetchColumn("SELECT place_id FROM place WHERE place = ?", array($pi))) {
                $place = $getPlace;
            } else {
                $this->db->insert('place', array(
                   'place' => $pi
                    ,'category_id' => 0
                ));
            }

            $hash = md5($row);

            $this->db->insert('transaction', array(
                'transaction_date' => $date
                ,'amount' => $amount
                ,'place_id' => $place
            ));

        }
    }
}