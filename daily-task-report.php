<?php 
    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }
$base_url = $protocol . "://".$_SERVER['SERVER_NAME'].'/' .(explode('/',$_SERVER['PHP_SELF'])[1]).'/';
?>
<?php
require 'authentication.php'; // admin authentication check 

// auth check
$user_id = $_SESSION['admin_id'];
$user_name = $_SESSION['name'];
$security_key = $_SESSION['security_key'];
if ($user_id == NULL || $security_key == NULL) {
    header('Location: index.php');
}

// check admin
$user_role = $_SESSION['user_role'];


if(isset($_GET['delete_task'])){
  $action_id = $_GET['task_id'];
  
  $sql = "DELETE FROM task_info WHERE task_id = :id";
  $sent_po = "task-info.php";
  $obj_admin->delete_data_by_this_method($sql,$action_id,$sent_po);
}

if(isset($_POST['add_task_post'])){
    $obj_admin->add_new_task($_POST);
}

$page_name="Task_Info";
include("include/sidebar.php");
// include('ems_header.php');


?>
<?php $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="row">
      <div class="col-md-12">
        <div class="well well-custom rounded-0">
          <div class="gap"></div>
          <div class="row">
            <div class="row-md-4">
                <input type="text" id="dateRange" class="form-control rounded-0">
            </div>
            <div class="row-md-4">
                 <button class="btn btn-primary btn-sm btn-menu" type="button" id="filter"><i class="glyphicon glyphicon-filter"></i> Filter</button>
                 <button class="btn btn-success btn-sm btn-menu" type="button" id="print"><i class="glyphicon glyphicon-print"></i> Print</button>
            </div>            
          </div>
          <center ><h3>Task Report</h3></center>

          <div class="gap"></div>

          <div class="table-responsive" id="printout">
            <table class="table table-codensed table-custom">
              <thead>
                <tr>
                  <th>S.N</th>
                  <th>Task Title</th>
                  <th>Assigned To</th>
                  <th>Start Time</th>
                  <th>End Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
             
              <?php 
              // PHP script starts here
              
              // Get the start and end dates from the query parameters
              $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
              $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
              
              // SQL query to fetch tasks between the selected dates
              if ($user_role == 1) {
                  $sql = "SELECT a.*, b.fullname 
                          FROM task_info a
                          INNER JOIN tbl_admin b ON (a.t_user_id = b.user_id) 
                          WHERE date(a.t_start_time) BETWEEN :start_date AND :end_date
                          ORDER BY a.task_id DESC";
              } else {
                  $sql = "SELECT a.*, b.fullname 
                          FROM task_info a
                          INNER JOIN tbl_admin b ON (a.t_user_id = b.user_id)
                          WHERE a.t_user_id = $user_id AND date(a.t_start_time) BETWEEN :start_date AND :end_date
                          ORDER BY a.task_id DESC";
              }
              
              // Prepare and execute the SQL query with parameters
              $stmt = $obj_admin->db->prepare($sql);
              $stmt->execute(array(
                  ':start_date' => $start_date,
                  ':end_date' => $end_date
              ));
              
              // Fetch the results
              $info = $stmt->fetchAll(PDO::FETCH_ASSOC);
              $serial = 1;
              $num_row = count($info);
              
              // Displaying the tasks
              if ($num_row == 0) {
                  echo '<tr><td colspan="7">No Data found</td></tr>';
              } else {
                  foreach ($info as $row) {
              ?>
              <tr>
                  <td><?= $serial ?></td>
                  <td><?= $row['t_title'] ?></td>
                  <td><?= $row['fullname'] ?></td>
                  <td><?= $row['t_start_time'] ?></td>
                  <td><?= $row['t_end_time'] ?></td>
                  <td>
                      <?php if ($row['status'] == 1) {
                          echo '<small class="label label-warning px-3">In Progress <span class="glyphicon glyphicon-refresh"></small>';
                      } elseif ($row['status'] == 2) {
                          echo '<small class="label label-success px-3">Completed <span class="glyphicon glyphicon-ok"></small>';
                      } else {
                          echo '<small class="label label-default border px-3">In Completed <span class="glyphicon glyphicon-remove"></small>';
                      } ?>
                  </td>
              </tr>
              <?php
                      $serial++;
                  }
              }              
              ?> 
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>


<?php

include("include/footer.php");



?>
<noscript>
    <div>
        <style>
            body{
                background-color:#ffff !important;
            }
            .mb-0{
                margin:0px;
            }
        </style>
        <div style="line-height:1em">
        <h4 class="mb-0 text-center"><b>Employee Task Managament System</b></h4>
        <h4 class="mb-0 text-center"><b>Task Report</b></h4>
        <div class="mb-0 text-center"><b>as of</b></div>
        <div class="mb-0 text-center"><b><?= date("F d, Y", strtotime($date)) ?></b></div>
        </div>
        <hr>
    </div>
</noscript>
<script type="text/javascript">
$(function(){
    flatpickr('#dateRange', {
        mode: 'range', // Enables selecting a range of dates
        dateFormat: 'Y-m-d' // Sets the date format to 'YYYY-MM-DD'
    });

    $('#filter').click(function(){
        var dates = $('#dateRange').val().split(' to ');
        var startDate = dates[0];
        var endDate = dates[1];

        location.href = "./daily-task-report.php?start_date=" + startDate + "&end_date=" + endDate;
    });

    $('#print').click(function(){
        var dates = $('#dateRange').val().split(' to ');
        var startDate = dates[0];
        var endDate = dates[1];

        var url = "<?= $base_url ?>";
        var newUrl = url + "daily-task-report.php?start_date=" + startDate + "&end_date=" + endDate;
        h.find('script').each(function(){
            if($(this).attr('src') != "")
            $(this).attr('src', base + $(this).attr('src'))
        })
        p.find('.table').addClass('table-bordered')
        var nw = window.open("", "_blank","width:"+($(window).width() * .8)+",left:"+($(window).width() * .1)+",height:"+($(window).height() * .8)+",top:"+($(window).height() * .1))
            nw.document.querySelector('head').innerHTML = h.html()
            nw.document.querySelector('body').innerHTML = ns[0].outerHTML
            nw.document.querySelector('body').innerHTML += p[0].outerHTML
            nw.document.close()
            setTimeout(() => {
                nw.print()
                setTimeout(() => {
                    nw.close()
                }, 200);
            }, 200);

    })
})
        // Your existing print logic here
        // (Ensure to use startDate and endDate in the query string)
</script>