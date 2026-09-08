<?php 

class HomePlate {

  private $id; 

  public function __construct($id = 'New')
  {
    $this->id = $id; 
  }

  public function SetupForm(){ 
    $content = '
    <div class="content">
      <div class="container-fluid">
        <div class="row">

          <div class="col-md-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user">
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-info">
                <h3 class="widget-user-username">Collection & Investment</h3>
                <h5 class="widget-user-desc">Summery</h5>
              </div>
              <div class="widget-user-image">
                <img class="img-circle elevation-2" src="dist/img/inv.png" alt="User Avatar">
              </div>
              <div class="card-footer">
                <div class="row">
                  <div class="col-sm-4 border-right">
                    <div class="description-block">
<h5 class="description-header" id="summary-collection">0</h5>
                      <span class="description-text">COLLECTION</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-4 border-right">
                    <div class="description-block">
<h5 class="description-header" id="summary-investment">0</h5>
                      <span class="description-text">INVESTMENT</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-4">
                    <div class="description-block">
<h5 class="description-header" id="summary-left">0</h5>
                      <span class="description-text">HOLD</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                </div>
                <!-- /.row -->
              </div>
            </div>
            <!-- /.widget-user -->
          </div>


          <div class="col-md-12">
                   <!-- DONUT CHART -->
            <div class="card card-danger">
              <div class="card-header">
                <h3 class="card-title">Sector Wise Investment</h3>
              </div>
              <div class="card-body">
                <canvas id="donutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
              </div>
              <!-- /.card-body -->
            </div>
          </div>
        </div>
      </div>
    </div>



    ';

    print $content;
  }

}
