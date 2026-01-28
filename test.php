<?php
$startDate = date('d-M-Y');
            $endDate = date('d-M-Y', strtotime($startDate . ' + 4 weeks'));
            echo "Start Date: $startDate, End Date: $endDate\n";
?>