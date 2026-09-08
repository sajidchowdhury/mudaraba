<?php 


class SiteStructure {
    

    private $description ;
    private $og_title ;
    private $title ;
    private $og_url ;
    private $og_site_name ;
    private $canonical ;
    private $SCRIPT_LINKS ;
    private $activePage ;
    private $breadcrumb ;
    private $logo ;

 public function __construct($SCRIPT_LINKS,$activePage,$breadcrumb){

       $this->title =  'MUDARABA | ' . $activePage;
       $this->logo = 'dist/img/logo.png';

       $this->description = '';

       $this->og_title = '';
       $this->activePage = $activePage;
       $this->breadcrumb = $breadcrumb;
       $this->og_url = '';
       $this->og_site_name ='MUDARABA';
       $this->canonical = '';
       $this->SCRIPT_LINKS = $SCRIPT_LINKS;

 }
 



    public function head() {
       

        return '<head>
        <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">

  <title>'.$this->title.'</title>
  <link href="img/icon.png" rel="icon">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Theme style -->
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Bootstrap Color Picker -->
  <link rel="stylesheet" href="plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- Bootstrap4 Duallistbox -->
  <link rel="stylesheet" href="plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
     <!-- DataTables -->
  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
  <style>
    /* .typeahead{
      top: 140px !important;
    left: auto !important;
    position: sticky !important;
    } */

  .uppercase {
    text-transform: uppercase;
  }

  @media print {
    tfoot {
        display: table-footer-group !important;
    }
}

  </style>
</head>


       ';
    }




    public function TopNav(){


        $content = '<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
  </ul>


  <!-- SEARCH FORM -->
 

  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto"> '; 


   
    $content .= '<!-- Direct Call Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="fas fa-phone"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">Call Us</span>
        <div class="dropdown-divider"></div>
        <a href="tel:+8801787492561" class="dropdown-item">
          <i class="fas fa-phone-alt"></i> +880 1787492561
        </a>
      </div>
    </li>
    <li class="nav-item">
      <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#logout-pop">
        <i class="fas fa-power-off"></i>
      </button>
    </li>
  </ul>
</nav>';

return $content ; 

    }


private function buildMenuTree($menus, $parentId = null) {
    $branch = [];

    foreach ($menus as $menu) {
        if ($menu['parent_id'] == $parentId) {
            $children = $this->buildMenuTree($menus, $menu['id']);
            if ($children) {
                $menu['children'] = $children;
            } else {
                $menu['children'] = [];
            }
            $branch[] = $menu;
        }
    }

    return $branch;
}


private function renderMenuItems($menus, $activePage, $level = 0) {
    $html = '';
    foreach ($menus as $menu) {
        $hasChildren = !empty($menu['children']);

$menuPage = '';
if (!empty($menu['menu_link'])) {
    $urlParts = parse_url($menu['menu_link']);
    if (!empty($urlParts['query'])) {
        parse_str($urlParts['query'], $queryParams);
        $menuPage = $queryParams['page'] ?? '';
    }
}

$isActive = ($activePage === $menuPage);

        $menuOpenClass = '';
        $linkClass = $isActive ? 'active' : '';

        // Check if any child is active
        if ($hasChildren) {
            foreach ($menu['children'] as $child) {
                if (strpos($this->renderMenuItems([$child], $activePage, $level + 1), 'active') !== false) {
                    $menuOpenClass = 'menu-open';
                    $linkClass = 'active';
                    break;
                }
            }
        }

        // Indent based on level
        $indentStyle = 'style="margin-left: ' . ($level * 15) . 'px;"';

        $html .= '<li class="nav-item ' . ($hasChildren ? 'has-treeview ' . $menuOpenClass : '') . '">
            <a href="' . ($menu['menu_link'] ?? '#') . '" class="nav-link ' . $linkClass . '" ' . $indentStyle . '>
                <i class="nav-icon ' . ($menu['icon'] ?? 'far fa-circle') . '"></i>
                <p>' . $menu['menu_name'] . ($hasChildren ? '<i class="fas fa-angle-left right"></i>' : '') . '</p>
            </a>';

        if ($hasChildren) {
            $html .= '<ul class="nav nav-treeview">';
            $html .= $this->renderMenuItems($menu['children'], $activePage, $level + 1); // ⬅ increment level
            $html .= '</ul>';
        }

        $html .= '</li>';
    }

    return $html;
}



    public function Setbreadcrumb() {


       $content = '   <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 bg-Disabled">
          <div class="col-sm-12">
            <ol class="breadcrumb float-sm-left">';

                $a = explode(',', $this->breadcrumb); 
                foreach($a  as $names){
                $content .= ' <li class="breadcrumb-item">'.$names.'</li>';

                }

               
           $content .= '  </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>' ;

       return $content ; 

     }



    public function PageSidebar() {

       $menu_data = new User();  
       $menus = $menu_data->getUserMenus(149);
       
       $menuTree = $this->buildMenuTree($menus);



      $content = '<aside class="main-sidebar elevation-4 sidebar-light-primary">
    <a href="dynamic-page.php?page=Home" class="brand-link navbar-white">
        <img src="' . $this->logo . '" alt="Admin Logo" class="brand-image img-circle elevation-3">
        <span class="brand-text font-weight-light">' . $this->og_site_name . '</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="dist/img/avatar.png" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="dynamic-page.php?page=Profit" class="d-block">' . ($_SESSION['admin_access_name'] ?? 'Guest') . '</a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';

$content .= $this->renderMenuItems($menuTree, $this->activePage);


$content .= '</ul>
        </nav>
    </div>
</aside>';

  
      return $content;
  }
  


    public function footer(){

        return '<div class="modal fade" id="logout-pop">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><b>Log Out ?</b></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to log out? <br> Press <b>No</b> if youwant to continue work. Press <b>Yes</b> to logout current user.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger" onclick="location.href=\'logout.php\';">Yes</button>
              <button type="button" class="btn btn-primary" data-dismiss="modal">No</button>
            </div>
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
        <!-- END MESSAGE BOX-->
        <div class="modal fade" id="modal-xl">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Extra Large Modal</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                             <div id="dash"></div> 

            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>


';
    }


    public  function includeScripts() {

        $result = '';

 $result .= '       <!-- START PRELOADS -->

 <!-- END PRELOADS -->  ';


        $result .= '<script>var hostUrl = "";</script>' ; 
        $result .= '<script src="plugins/jquery/jquery.min.js"></script>' ; 
        $result .= '<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>' ; 

        foreach ($this->SCRIPT_LINKS as $script) {
            $result .= '<script type="text/javascript" src="' . $script . '"></script>';
        }

      
      $result .= '<script src="dist/js/adminlte.min.js"></script>' ; 
      $result .= '<script src="js/common.js"></script>' ; 
        
        return $result;
    }



}// end of class 
