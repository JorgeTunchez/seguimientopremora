<?php
require_once("core/core.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
error_reporting(E_ALL);
ini_set('memory_limit', '1024M'); // or you could use 1G
ini_set('display_errors', 0);
session_start();
if (isset($_SESSION['user_id'])) {
  $strRolUserSession = getRolUserSession($_SESSION['user_id']);
  $intIDUserSession = getIDUserSession($_SESSION['user_id']);

  if ($strRolUserSession != '') {
    $arrRolUser["ID"] = $intIDUserSession;
    $arrRolUser["NAME"] = $_SESSION['user_id'];

    if ($strRolUserSession == "master") {
      $arrRolUser["MASTER"] = true;
    } elseif ($strRolUserSession == "normal") {
      $arrRolUser["NORMAL"] = true;
      header("Location: dashboard.php");
    } elseif ($strRolUserSession == "mora") {
      $arrRolUser["MORA"] = true;
      header("Location: dashboard.php");
    }
  }
} else {
  header("Location: index.php");
}

$objController = new validador_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class validador_controller
{

  private $objModel;
  private $objView;
  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->objModel = new validador_model();
    $this->objView = new validador_view($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContentController()
  {
    $this->objView->drawContent();
  }

  public function runAjax()
  {
    $this->ajaxDestroySession();
    $this->ajaxActivarValidador();
  }

  public function ajaxDestroySession()
  {
    if (isset($_POST["destroSession"])) {
      header("Content-Type: application/json;");
      session_destroy();
      $arrReturn["Correcto"] = "Y";
      print json_encode($arrReturn);
      exit();
    }
  }

  public function ajaxActivarValidador()
  {
    if (isset($_POST["activarValidador"]) && $_POST["activarValidador"] == "true") {
      header("Content-Type: application/json;");
      $arrReturn["Correcto"] = $this->objModel->validarestadopretamos();
      print json_encode($arrReturn);
      exit();
    }
  }
}

class validador_model
{

  public function validarestadopretamos()
  {

    $conn = getConexion();
    $arrPrestamosApp = array();
    $strQuery = "SELECT DISTINCT numero FROM prestamo";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrPrestamosApp[$row["numero"]] = $row["numero"];
      }
    }

    reset($arrPrestamosApp);
    while ($rTMP = each($arrPrestamosApp)) {
      $intPrestamo = $rTMP["key"];
      $strEstado = "";
      $strQuery1 = "SELECT 
                    CASE
                        WHEN dias_mora_capital = 0 THEN 'Al dia'
                        WHEN dias_mora_capital >0 AND dias_mora_capital <=30 THEN 'Premora'
                        WHEN dias_mora_capital >30  THEN 'En mora'
                        ELSE 'Cancelado'
                    END AS estado_actual
                    FROM listado_condiciones
                    WHERE numero_prestamo = {$intPrestamo}";
      $result1 = mysqli_query($conn, $strQuery1);
      if (!empty($result1)) {
        while ($row1 = mysqli_fetch_assoc($result1)) {
          $strEstado = $row1["estado_actual"];
        }
      }

      $strQuery2 = "UPDATE prestamo SET estado_actual = '{$strEstado}' WHERE numero = {$intPrestamo}";
      mysqli_query($conn, $strQuery2);
    }

    mysqli_close($conn);
    return "Y";
  }
}

class validador_view
{

  private $objModel;
  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->objModel = new validador_model();
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContent()
  {
?>
    <!DOCTYPE html>
    <html>

    <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <title>Premora Agencias</title>
      <!-- Tell the browser to be responsive to screen width -->
      <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
      <!-- Bootstrap 3.3.7 -->
      <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
      <!-- Ionicons -->
      <link rel="stylesheet" href="bower_components/Ionicons/css/ionicons.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
      <!-- Material Design -->
      <link rel="stylesheet" href="dist/css/bootstrap-material-design.min.css">
      <link rel="stylesheet" href="dist/css/ripples.min.css">
      <link rel="stylesheet" href="dist/css/MaterialAdminLTE.min.css">
      <!-- AdminLTE Skins. Choose a skin from the css/skins
          folder instead of downloading all of them to reduce the load. -->
      <link rel="stylesheet" href="dist/css/skins/all-md-skins.min.css">
      <!-- Morris chart -->
      <link rel="stylesheet" href="bower_components/morris.js/morris.css">
      <!-- jvectormap -->
      <link rel="stylesheet" href="bower_components/jvectormap/jquery-jvectormap.css">
      <!-- Date Picker -->
      <link rel="stylesheet" href="bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
      <!-- Daterange picker -->
      <link rel="stylesheet" href="bower_components/bootstrap-daterangepicker/daterangepicker.css">
      <!-- bootstrap wysihtml5 - text editor -->
      <link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">

      <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
      <![endif]-->

      <!-- Google Font -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
      <style>
        .centrar {
          position: absolute;
          /*nos posicionamos en el centro del navegador*/
          top: 50%;
          left: 50%;
          float: none;
          /*determinamos una anchura*/
          width: 400px;
          /*indicamos que el margen izquierdo, es la mitad de la anchura*/
          margin-left: -130px;
          /*determinamos una altura*/
          height: 300px;
          /*indicamos que el margen superior, es la mitad de la altura*/
          margin-top: -150px;
          padding: 5px;
          z-index: 1;
        }

        @media only screen and (max-width: 800px) {

          /* Force table to not be like tables anymore */
          #no-more-tables table,
          #no-more-tables thead,
          #no-more-tables tbody,
          #no-more-tables th,
          #no-more-tables td,
          #no-more-tables tr {
            display: block;
          }

          /* Hide table headers (but not display: none;, for accessibility) */
          #no-more-tables thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
          }

          #no-more-tables tr {
            border: 1px solid #ccc;
          }

          #no-more-tables td {
            /* Behave like a "row" */
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 50%;
            white-space: normal;
            text-align: left;
          }

          #no-more-tables td:before {
            /* Now like a table header */
            position: absolute;
            /* Top/left values mimic padding */
            top: 6px;
            left: 6px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            text-align: left;
            font-weight: bold;
          }

          /*
                Label the data
                */
          #no-more-tables td:before {
            content: attr(data-title);
          }
        }
      </style>
    </head>

    <body class="hold-transition skin-blue sidebar-mini">
      <div class="wrapper">

        <header class="main-header">
          <!-- Logo -->
          <a href="dashboard.php" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini">P<b>A</b></span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg">Premora Agencias</span>
          </a>
          <!-- Header Navbar: style can be found in header.less -->
          <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
              <span class="sr-only">Toggle navigation</span>
            </a>
            <div class="navbar-custom-menu">
              <ul class="nav navbar-nav">
                <!-- User Account: style can be found in dropdown.less -->
                <li class="dropdown user user-menu">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="images/user.png" class="user-image" alt="User Image">
                    <span class="hidden-xs"><?php print $this->arrRolUser["NAME"]; ?></span>
                  </a>
                  <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header">
                      <img src="images/user.png" class="img-circle" alt="User Image">
                      <p>
                        <?php print $this->arrRolUser["NAME"]; ?>
                      </p>
                    </li>
                    <!-- Menu Footer-->
                    <li class="user-footer">
                      <div class="pull-center" style="cursor:pointer;">
                        <a href="#" class="btn btn-default btn-flat btn-block" onclick="destroSession()">Cerrar Sesion</a>
                      </div>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
        </header>
        <!-- Left side column. contains the logo and sidebar -->
        <aside class="main-sidebar">
          <!-- sidebar: style can be found in sidebar.less -->
          <section class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel">
              <div class="pull-left image">
                <img src="images/user.png" class="img-circle" alt="User Image">
              </div>
              <div class="pull-left info">
                <p><?php print $this->arrRolUser["NAME"]; ?></p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
              </div>
            </div>
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <?php draMenu($this->arrRolUser, 'validar_estado', 2); ?>
          </section>
          <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
          <!-- Content Header (Page header) -->
          <br>
          <section class="content-header">
            <h1>Validador de Estado</h1>
          </section>
          <!-- Main content -->
          <section class="content">
            <!-- Small boxes (Stat box) -->
            <div class="row">
              <div class="box box-info">
                <div class="box-header">
                </div>
                <div class="box-body">
                  <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/loading.gif" height="300px" width="300px"></div>
                  <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                    <table class="table table-sm table-borderless">
                      <tr>
                        <td>
                          <h5>Este modulo permite validar el estado de cada uno de los prestamos en base al listado de condiciones. </h5><br><br>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <button id="btnValidador" name="btnValidador" class="btn btn-success btn-raised btn-block" onclick="activarValidador()">
                            <i class="fa fa-refresh" aria-hidden="true"></i> Validador de Estado
                          </button>
                        </td>
                      </tr>
                    </table>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                      <div class="alert alert-success alert-dismissible" id="divAlertValidacionExitosa" style="display:none;">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h4><i class="icon fa fa-check"></i> Validacion Exitosa!</h4>
                        La validacion de datos ha sido exitosa.
                      </div>
                    </div>
                  </div>
                  <br><br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                      <div id="divDetalle">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
          <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        <footer class="main-footer">
          <div class="pull-right hidden-xs">
            <b>Version</b> 1.0
          </div>
          <strong>Copyright &copy; <?php print date('Y')?>
        </footer>
        <!-- Add the sidebar's background. This div must be placed
          immediately after the control sidebar -->
        <div class="control-sidebar-bg"></div>
      </div>
      <!-- ./wrapper -->

      <!-- jQuery 3 -->
      <script src="bower_components/jquery/dist/jquery.min.js"></script>
      <!-- jQuery UI 1.11.4 -->
      <script src="bower_components/jquery-ui/jquery-ui.min.js"></script>
      <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
      <script>
        $.widget.bridge('uibutton', $.ui.button);
      </script>
      <!-- Bootstrap 3.3.7 -->
      <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
      <!-- Material Design -->
      <script src="dist/js/material.min.js"></script>
      <script src="dist/js/ripples.min.js"></script>
      <script>
        $.material.init();

        function destroSession() {
          if (confirm("¿Desea salir de la aplicación?")) {
            $.ajax({
              url: "validador_estado.php",
              data: {
                destroSession: true
              },
              type: "post",
              dataType: "json",
              beforeSend: function() {
                $("#divShowLoadingGeneralBig").css("z-index", 1050);
                $("#divShowLoadingGeneralBig").show();
              },
              success: function(data) {
                if (data.Correcto == "Y") {
                  $("#divShowLoadingGeneralBig").hide();
                  alert("Usted ha cerrado sesión");
                  location.href = "index.php";
                }
              }
            });
          }
        }

        function activarValidador() {

          $.ajax({
            url: "validador_estado.php",
            data: {
              activarValidador: true
            },
            type: "post",
            dataType: "json",
            beforeSend: function() {
              $("#divShowLoadingGeneralBig").css("z-index", 1050);
              $("#divShowLoadingGeneralBig").show();
              $("#btnValidador").prop('disabled', true);
            },
            success: function(data) {
              if (data.Correcto == "Y") {
                $("#divShowLoadingGeneralBig").hide();
                $("#divAlertValidacionExitosa").show();
                $("#btnValidador").prop('disabled', false);
              }
            }
          });


        }
      </script>
      <!-- Morris.js charts -->
      <script src="bower_components/raphael/raphael.min.js"></script>
      <script src="bower_components/morris.js/morris.min.js"></script>
      <!-- Sparkline -->
      <script src="bower_components/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
      <!-- jvectormap -->
      <script src="plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
      <script src="plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
      <!-- jQuery Knob Chart -->
      <script src="bower_components/jquery-knob/dist/jquery.knob.min.js"></script>
      <!-- daterangepicker -->
      <script src="bower_components/moment/min/moment.min.js"></script>
      <script src="bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
      <!-- datepicker -->
      <script src="bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
      <!-- Bootstrap WYSIHTML5 -->
      <script src="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
      <!-- Slimscroll -->
      <script src="bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
      <!-- FastClick -->
      <script src="bower_components/fastclick/lib/fastclick.js"></script>
      <!-- AdminLTE App -->
      <script src="dist/js/adminlte.min.js"></script>
      <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
      <script src="dist/js/pages/dashboard.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="dist/js/demo.js"></script>
    </body>

    </html>
<?php
  }
}

?>